<?php
/**
 * SQL implementation of SMW's storage abstraction layer.
 * @file
 * @ingroup SMWStore
 * @author Markus Krötzsch
 */

/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 */
class SMWSQLStore extends SMWStore {

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC". Used during query
	 * processing (where these property names are searched while building the query
	 * conditions).
	 */
	protected $m_sortkeys;
	/**
	 * Array of database field names by which results during query processing should
	 * be ordered, if any. Format "Property_name" => "DB field name". Entries default
	 * to false when no appropriate field was found yet.
	 */
	protected $m_sortfields;
	/**
	 * Global counter to prevent clashes between table aliases.
	 */
	static protected $m_tablenum = 0;
	/**
	 * Array of names of virtual tables that hold the lower closure of certain
	 * categories wrt. hierarchy.
	 */
	static protected $m_categorytables = array();
	/**
	 * Array of names of virtual tables that hold the lower closure of certain
	 * categories wrt. hierarchy.
	 */
	static protected $m_propertytables = array();
	/**
	 * Record all virtual tables used for a single operation (especially query) to produce debug output.
	 */
	protected $m_usedtables;


///// Reading methods /////

	function getSemanticData($subject, $filter = false) {
		wfProfileIn("SMWSQLStore::getSemanticData (SMW)");
		$db =& wfGetDB( DB_SLAVE );

		if ( $subject instanceof Title ) {
			$subjectid = $subject->getArticleID(); // avoid queries for nonexisting pages
			$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
			$dv->setValues($subject->getDBkey(), $subject->getNamespace());
			$result = new SMWSemanticData($dv);
		} elseif ($subject instanceof SMWWikiPageValue) {
			$subjectid = $subject->getArticleID(); // avoid queries for nonexisting pages
			$result = new SMWSemanticData($subject);
		} else {
			$subjectid = 0;
			$result = NULL;
		}
		if ($subjectid <= 0) {
			wfProfileOut("SMWSQLStore::getSemanticData (SMW)");
			return $result;
		}
		$subjectcond = 'subject_id=' . $db->addQuotes($subjectid);

		if ($filter !== false) { //array as described in docu for SMWStore
			$do_rels = false;
			$do_text = false;
			$do_cats = false;
			$do_redirects = false;
			$do_subprops = false;
			$do_specs = false;
			$do_atts = false;
			$do_nary = false;
			foreach ($filter as $value) {
				switch ($value) {
					case '_wpg':
						$do_rels = true;
					break;
					case '_txt': case '_cod':
						$do_text = true;
					break;
					case '__nry':
						$do_nary = true;
					break;
					case SMW_SP_INSTANCE_OF: case SMW_SP_SUBCLASS_OF:
						$do_cats = true;
					break;
					case SMW_SP_REDIRECTS_TO:
						$do_redirects = true;
					break;
					case SMW_SP_SUBPROPERTY_OF:
						$do_subprops = true;
					break;
					default:
						if (is_numeric($value)) { // some special property
							$do_specs = true;
						} else { // some other "attribute"
							$do_atts = true;
						}
				}
			}
		} else {
			$do_rels = true;
			$do_text = true;
			$do_cats = true;
			$do_redirects = true;
			$do_subprops = true;
			$do_specs = true;
			$do_atts = true;
			$do_nary = true;
		}

		// "relations"
		if ($do_rels) {
			$res = $db->select( $db->tableName('smw_relations'),
								'relation_title, object_title, object_namespace, object_id',
								$subjectcond,
								'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				$property = Title::makeTitle(SMW_NS_PROPERTY, $row->relation_title);
				$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				if ($dv instanceof SMWWikiPagevalue) { // may fail if type was changed!
					$dv->setValues($row->object_title, $row->object_namespace, $row->object_id);
					$result->addPropertyObjectValue($property, $dv);
				}
			}
			$db->freeResult($res);
		}

		// "attributes"
		if ($do_atts) {
			$res = $db->select( $db->tableName('smw_attributes'),
								'attribute_title, value_unit, value_xsd',
								$subjectcond, 'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				$property = Title::makeTitle(SMW_NS_PROPERTY, $row->attribute_title);
				$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				$dv->setXSDValue($row->value_xsd, $row->value_unit);
				$result->addPropertyObjectValue($property, $dv);
			}
			$db->freeResult($res);
		}

		// long strings
		if ($do_text) {
			$res = $db->select( $db->tableName('smw_longstrings'),
								'attribute_title, value_blob',
								$subjectcond, 'SMW::getSemanticData');
			while($row = $db->fetchObject($res)) {
				$property = Title::makeTitle(SMW_NS_PROPERTY, $row->attribute_title);
				$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				$dv->setXSDValue($row->value_blob, '');
				$result->addPropertyObjectValue($property, $dv);
			}
			$db->freeResult($res);
		}

		// nary values
		if ($do_nary) {
			$res = $db->select( $db->tableName('smw_nary'),
								'attribute_title, nary_key',
								$subjectcond, 'SMW::getSemanticData');
			///TODO: presumably slow. Try to do less SQL queries by making a join with smw_nary
			while($row = $db->fetchObject($res)) {
				$property = Title::makeTitle(SMW_NS_PROPERTY, $row->attribute_title);
				$type = SMWDataValueFactory::getPropertyObjectTypeValue($property);
				$subtypes = $type->getTypeValues();
				$values = array();
				for ($i=0; $i < count($subtypes); $i++) { // init array
					$values[$i] = NULL;
				}
				$res2 = $db->select( $db->tableName('smw_nary_attributes'),
								'nary_pos, value_unit, value_xsd',
								$subjectcond .
								' AND nary_key=' . $db->addQuotes($row->nary_key),
								'SMW::getPropertyValues');
				while($row2 = $db->fetchObject($res2)) {
					if ($row2->nary_pos < count($subtypes)) {
						$dv = SMWDataValueFactory::newTypeObjectValue($subtypes[$row2->nary_pos]);
						$dv->setXSDValue($row2->value_xsd, $row2->value_unit);
						$values[$row2->nary_pos] = $dv;
					}
				}
				$db->freeResult($res2);
				$res2 = $db->select( $db->tableName('smw_nary_longstrings'),
								'nary_pos, value_blob',
								$subjectcond .
								' AND nary_key=' . $db->addQuotes($row->nary_key),
								'SMW::getPropertyValues');
				while($row2 = $db->fetchObject($res2)) {
					if ( $row2->nary_pos < count($subtypes) ) {
						$dv = SMWDataValueFactory::newTypeObjectValue($subtypes[$row2->nary_pos]);
						$dv->setXSDValue($row2->value_blob, '');
						$values[$row2->nary_pos] = $dv;
					}
				}
				$db->freeResult($res2);
				$res2 = $db->select( $db->tableName('smw_nary_relations'),
								'nary_pos, object_title, object_namespace, object_id',
								$subjectcond .
								' AND nary_key=' . $db->addQuotes($row->nary_key),
								'SMW::getPropertyValues');
				while($row2 = $db->fetchObject($res2)) {
					if ( ($row2->nary_pos < count($subtypes)) &&
							($subtypes[$row2->nary_pos]->getXSDValue() == '_wpg') ) {
						$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
						$dv->setValues($row2->object_title, $row2->object_namespace, $row2->object_id);
						$values[$row2->nary_pos] = $dv;
					}
				}
				$db->freeResult($res2);
				$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				$dv->setDVs($values);
				$result->addPropertyObjectValue($property, $dv);
			}
			$db->freeResult($res);
		}

		// simple special properties
		if ($do_specs) {
			$res = $db->select( 'smw_specialprops',
								'property_id, value_string',
								$subjectcond, 'SMW::getSemanticData');
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newSpecialValue($row->property_id);
				$dv->setXSDValue($row->value_string);
				$result->addSpecialValue($row->property_id, $dv);
			}
			$db->freeResult($res);
		}

		// categories
		if ($do_cats) {
			$res = $db->select( 'categorylinks',
								'DISTINCT cl_to',
								'cl_from=' . $db->addQuotes($subjectid), 'SMW::getSemanticData');
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->cl_to, NS_CATEGORY);
				if ($subject->getNamespace() == NS_CATEGORY) {
					$result->addSpecialValue(SMW_SP_SUBCLASS_OF, $dv);
				} else {
					$result->addSpecialValue(SMW_SP_INSTANCE_OF, $dv);
				}
			}
			$db->freeResult($res);
		}

		// properties
		if ( ($do_subprops) && ($subject->getNamespace() == SMW_NS_PROPERTY) ) {
			$sql = 'subject_title=' . $db->addQuotes($subject->getDBkey());
			$res = $db->select( 'smw_subprops', 'object_title',
								'subject_title=' . $db->addQuotes($subject->getDBkey()), 'SMW::getSemanticData');
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->object_title, SMW_NS_PROPERTY);
				$result->addSpecialValue(SMW_SP_SUBPROPERTY_OF, $dv);
			}
			$db->freeResult($res);
		}

		// redirects
		if ($do_redirects) {
			$sql = 'rd_from=' . $db->addQuotes($subjectid);
			$res = $db->select( 'redirect', 'rd_namespace,rd_title',
								'rd_from=' . $db->addQuotes($subjectid), 'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->rd_title, $row->rd_namespace);
				$result->addSpecialValue(SMW_SP_REDIRECTS_TO, $dv);
			}
			$db->freeResult($res);
		}

		wfProfileOut("SMWSQLStore::getSemanticData (SMW)");
		return $result;
	}

	function getSpecialValues(Title $subject, $specialprop, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getSpecialValues-$specialprop (SMW)");
		// NOTE: this method currently supports no ordering or boundary. This is probably best anyway ...
		if ($specialprop !== SMW_SP_SUBPROPERTY_OF) {
			$subjectid = $subject->getArticleID(); // avoid queries for nonexisting pages
			if ($subjectid <= 0) {
				wfProfileOut("SMWSQLStore::getSpecialValues-$specialprop (SMW)");
				return array();
			}
		}

		$db =& wfGetDB( DB_SLAVE ); // TODO: Is '=&' needed in PHP5?
		$result = array();
		if ( ($specialprop === SMW_SP_INSTANCE_OF) ||  ($specialprop === SMW_SP_SUBCLASS_OF)) { // category membership
			$sql = 'cl_from=' . $db->addQuotes($subjectid);
			$res = $db->select( 'categorylinks',
								'DISTINCT cl_to',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			while($row = $db->fetchObject($res)) {
				$v = SMWDataValueFactory::newTypeIDValue('_wpg');
				$v->setValues($row->cl_to, NS_CATEGORY);
				$result[] = $v;
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirects
			$sql = 'rd_from=' . $db->addQuotes($subjectid);
			$res = $db->select( 'redirect',
								'rd_namespace,rd_title',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->rd_title, $row->rd_namespace);
				$result[] = $dv;
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_SUBPROPERTY_OF) { // subproperty
			$sql = 'subject_title=' . $db->addQuotes($subject->getDBkey());
			$res = $db->select( 'smw_subprops',
								'object_title',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->object_title, SMW_NS_PROPERTY);
				$result[] = $dv;
			}
			$db->freeResult($res);
		} else { // "normal" special property
			$sql = 'subject_id=' . $db->addQuotes($subjectid) .
				'AND property_id=' . $db->addQuotes($specialprop);
			$res = $db->select( 'smw_specialprops',
								'value_string',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
// 			case SMW_SP_HAS_TYPE: case SMW_SP_POSSIBLE_VALUE: case SMW_SP_DISPLAY_UNITS:
			while($row = $db->fetchObject($res)) {
				$v = SMWDataValueFactory::newSpecialValue($specialprop);
				$v->setXSDValue($row->value_string);
				$result[] = $v;
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore::getSpecialValues-$specialprop (SMW)");
		return $result;
	}

	function getSpecialSubjects($specialprop, SMWDataValue $value, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getSpecialSubjects-$specialprop (SMW)");
		$db =& wfGetDB( DB_SLAVE );

		$result = array();

		if ( ($specialprop === SMW_SP_INSTANCE_OF) || ($specialprop === SMW_SP_SUBCLASS_OF) ) { // category membership
			if ( !($value instanceof SMWWikiPageValue) || ($value->getNamespace() != NS_CATEGORY) ) {
				wfProfileOut("SMWSQLStore::getSpecialSubjects-$specialprop (SMW)");
				return array();
			}
			$sql = 'cl_to=' . $db->addQuotes($value->getDBkey());
			$res = $db->select( 'categorylinks',
								'DISTINCT cl_from',
								$sql, 'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
			// rewrite result as array
			while($row = $db->fetchObject($res)) {
				$t = Title::newFromID($row->cl_from);
				if ($t !== NULL) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($t->getDBkey(), NS_CATEGORY, $row->cl_from);
					$result[] = $dv;
				}
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$sql = 'rd_title=' . $db->addQuotes($value->getDBkey())
					. ' AND rd_namespace=' . $db->addQuotes($value->getNamespace());
			$res = $db->select( 'redirect',
								'rd_from',
								$sql, 'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
			// reqrite results as array
			while($row = $db->fetchObject($res)) {
				$t = Title::newFromID($row->rd_from);
				if ($t !== NULL) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($t->getDBkey(), NS_CATEGORY, $row->rd_from);
					$result[] = $dv;
				}
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_SUBPROPERTY_OF) { // subproperties
			$sql = 'object_title=' . $db->addQuotes($value->getDBkey());
			$res = $db->select( 'smw_subprops',
								'subject_title',
								$sql, 'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
			// reqrite results as array
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->subject_title, SMW_NS_PROPERTY);
				$result[] = $dv;
			}
			$db->freeResult($res);
		} else {
			if ($value->getXSDValue() !== false) { // filters out error-values etc.
				$stringvalue = $value->getXSDValue();
			} else {
				wfProfileOut("SMWSQLStore::getSpecialSubjects-$specialprop (SMW)");
				return array();
			}

			$sql = 'property_id=' . $db->addQuotes($specialprop) .
			       ' AND value_string=' . $db->addQuotes($stringvalue) . ' AND page_id=subject_id' .
			       $this->getSQLConditions($requestoptions,'page_title','page_title');
			$res = $db->select( array('smw_specialprops','page'),
			                    'DISTINCT subject_id,page_title,page_namespace',
			                    $sql, 'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions,'page_title') );
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->page_title, $row->page_namespace, $row->subject_id);
				$result[] = $dv;
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore::getSpecialSubjects-$specialprop (SMW)");
		return $result;
	}


	function getPropertyValues($subject, $property, $requestoptions = NULL, $outputformat = '') {
		wfProfileIn("SMWSQLStore::getPropertyValues (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		if ($subject !== NULL) {
			$subjectid = $subject->getArticleID(); // avoid queries for nonexisting pages
			if ($subjectid <= 0) {
				wfProfileOut("SMWSQLStore::getPropertyValues (SMW)");
				return array();
			}
			$subjectcond = 'subject_id=' . $db->addQuotes($subjectid) . ' AND ';
		} else { // get all values for this property 
		    ///TODO: shouldn't this rather be ''? Confused by my own code (mak)
		    // dvr: I also think this should be '' -- anyone want to check and remove this comment?
			$subjectcond = ''; // previously: 'subject_id=' . $db->addQuotes(0) . ' AND ';
		}

		$result = array();
		$id = SMWDataValueFactory::getPropertyObjectTypeID($property);
		switch ($id) {
			case '_txt': case '_cod':
				$res = $db->select( $db->tableName('smw_longstrings'),
									'value_blob',
									$subjectcond .
									'attribute_title=' . $db->addQuotes($property->getDBkey()),
									'SMW::getPropertyValues', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setOutputFormat($outputformat);
					$dv->setXSDValue($row->value_blob, '');
					$result[] = $dv;
				}
				$db->freeResult($res);
			break;
			case '_wpg':
				$res = $db->select( $db->tableName('smw_relations'),
									'object_title, object_namespace, object_id',
									$subjectcond .
									'relation_title=' . $db->addQuotes($property->getDBkey()) .
									$this->getSQLConditions($requestoptions,'object_title','object_title'),
									'SMW::getPropertyValues', $this->getSQLOptions($requestoptions,'object_title') );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setOutputFormat($outputformat);
					$dv->setValues($row->object_title, $row->object_namespace, $row->object_id);
					$result[] = $dv;
				}
				$db->freeResult($res);
			break;
			case '__nry':
				$type = SMWDataValueFactory::getPropertyObjectTypeValue($property);
				$subtypes = $type->getTypeValues();
				$res = $db->select( $db->tableName('smw_nary'),
									'nary_key',
									$subjectcond .
									'attribute_title=' . $db->addQuotes($property->getDBkey()),
									'SMW::getPropertyValues', $this->getSQLOptions($requestoptions) );
				///TODO: presumably slow. Try to do less SQL queries by making a join with smw_nary
				while($row = $db->fetchObject($res)) {
					$values = array();
					for ($i=0; $i < count($subtypes); $i++) { // init array
						$values[$i] = NULL;
					}
					$res2 = $db->select( $db->tableName('smw_nary_attributes'),
									'nary_pos, value_unit, value_xsd',
									$subjectcond .
									'nary_key=' . $db->addQuotes($row->nary_key),
									'SMW::getPropertyValues');
					while($row2 = $db->fetchObject($res2)) {
						if ($row2->nary_pos < count($subtypes)) {
							$dv = SMWDataValueFactory::newTypeObjectValue($subtypes[$row2->nary_pos]);
							$dv->setXSDValue($row2->value_xsd, $row2->value_unit);
							$values[$row2->nary_pos] = $dv;
						}
					}
					$db->freeResult($res2);
					$res2 = $db->select( $db->tableName('smw_nary_longstrings'),
									'nary_pos, value_blob',
									$subjectcond .
									'nary_key=' . $db->addQuotes($row->nary_key),
									'SMW::getPropertyValues');
					while($row2 = $db->fetchObject($res2)) {
						if ( $row2->nary_pos < count($subtypes) ) {
							$dv = SMWDataValueFactory::newTypeObjectValue($subtypes[$row2->nary_pos]);
							$dv->setXSDValue($row2->value_blob, '');
							$values[$row2->nary_pos] = $dv;
						}
					}
					$db->freeResult($res2);
					$res2 = $db->select( $db->tableName('smw_nary_relations'),
									'nary_pos, object_title, object_namespace, object_id',
									$subjectcond .
									'nary_key=' . $db->addQuotes($row->nary_key),
									'SMW::getPropertyValues');
					while($row2 = $db->fetchObject($res2)) {
						if ( ($row2->nary_pos < count($subtypes)) &&
						     ($subtypes[$row2->nary_pos]->getXSDValue() == '_wpg') ) {
							$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
							$dv->setValues($row2->object_title, $row2->object_namespace, $row2->object_id);
							$values[$row2->nary_pos] = $dv;
						}
					}
					$db->freeResult($res2);
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setOutputFormat($outputformat);
					$dv->setDVs($values);
					$result[] = $dv;
				}
				$db->freeResult($res);
			break;
			default:
				if ( ($requestoptions !== NULL) && ($requestoptions->boundary !== NULL) &&
				     ($requestoptions->boundary->isNumeric()) ) {
					$value_column = 'value_num';
				} else {
					$value_column = 'value_xsd';
				}
				$sql = $subjectcond . 'attribute_title=' . $db->addQuotes($property->getDBkey()) .
				       $this->getSQLConditions($requestoptions,$value_column,'value_xsd');
				$res = $db->select( $db->tableName('smw_attributes'),
									'value_unit, value_xsd',
									$sql, 'SMW::getPropertyValues', $this->getSQLOptions($requestoptions,$value_column) );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setOutputFormat($outputformat);
					$dv->setXSDValue($row->value_xsd, $row->value_unit);
					$result[] = $dv;
				}
				$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore::getPropertyValues (SMW)");
		return $result;
	}

	function getPropertySubjects(Title $property, $value, $requestoptions = NULL) {
		if ($value === NULL) {
			return $this->getAllPropertySubjects($property,$requestoptions);
		}
		wfProfileIn("SMWSQLStore::getPropertySubjects (SMW)");
		if ( !$value->isValid() ) {
			wfProfileOut("SMWSQLStore::getPropertySubjects (SMW)");
			return array();
		}
		$db =& wfGetDB( DB_SLAVE );

		switch ($value->getTypeID()) {
		case '_txt': case '_cod': // not supported
			wfProfileOut("SMWSQLStore::getPropertySubjects (SMW)");
			return array();
		break;
		case '_wpg': // wikipage
			$sql = 'object_namespace=' . $db->addQuotes($value->getNamespace()) .
			       ' AND object_title=' . $db->addQuotes($value->getDBkey()) .
			       ' AND relation_title=' . $db->addQuotes($property->getDBkey()) .
			       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

			$res = $db->select( 'smw_relations',
			                    'DISTINCT subject_id, subject_title, subject_namespace',
			                    $sql, 'SMW::getPropertySubjects',
			                    $this->getSQLOptions($requestoptions,'subject_title') );
		break;
		case '__nry':
			$values = $value->getDVs();
			$narytable = $db->tableName('smw_nary');
			$where = "$narytable.attribute_title=" . $db->addQuotes($property->getDBkey());
			$from = $narytable;
			$count = 0;
			foreach ($values as $dv) {
				if ( ($dv === NULL) || (!$dv->isValid()) ) {
					$count++;
					continue;
				}
				switch ($dv->getTypeID()) {
				case '_txt': case '_cod': // not supported
				break;
				case '_wpg':
					$from .= ' INNER JOIN ' . $db->tableName('smw_nary_relations') . ' AS nary' . $count .
					         " ON ($narytable.subject_id=nary$count.subject_id AND $narytable.nary_key=nary$count.nary_key)";
					$where .= " AND nary$count.object_title=" . $db->addQuotes($dv->getDBkey()) .
					          " AND nary$count.object_namespace=" . $db->addQuotes($dv->getNamespace());
				break;
				default:
					$from .= ' INNER JOIN ' . $db->tableName('smw_nary_attributes') . ' AS nary' . $count .
					         " ON ($narytable.subject_id=nary$count.subject_id AND $narytable.nary_key=nary$count.nary_key)";
					$where .= " AND nary$count.value_xsd=" . $db->addQuotes($dv->getXSDValue()) .
					          " AND nary$count.value_unit=" . $db->addQuotes($dv->getUnit());
				}
				$count++;
			}
			$res = $db->query("SELECT DISTINCT $narytable.subject_id, $narytable.subject_title, $narytable.subject_namespace FROM $from WHERE $where",
			                  'SMW::getPropertySubjects',
			                  $this->getSQLOptions($requestoptions,'subject_title'));
		break;
		default:
			$sql = 'value_xsd=' . $db->addQuotes($value->getXSDValue()) .
			       ' AND value_unit=' . $db->addQuotes($value->getUnit()) .
			       ' AND attribute_title=' . $db->addQuotes($property->getDBkey()) .
			       $this->getSQLConditions($requestoptions,'subject_title','subject_title');
			$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT subject_id, subject_title, subject_namespace',
		                    $sql, 'SMW::getPropertySubjects',
		                    $this->getSQLOptions($requestoptions,'subject_title') );
		break;
		}
		$result = array();
		while($row = $db->fetchObject($res)) {
			$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
			$dv->setValues($row->subject_title, $row->subject_namespace, $row->subject_id);
			$result[] = $dv;
		}
		$db->freeResult($res);
		wfProfileOut("SMWSQLStore::getPropertySubjects (SMW)");
		return $result;
	}

	function getAllPropertySubjects(Title $property, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getAllPropertySubjects (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$id = SMWDataValueFactory::getPropertyObjectTypeID($property);
		switch ($id) {
			case '_txt': case '_cod':
				$tablename = 'smw_longstrings';
				$pcolumn = 'attribute_title';
				$extraconds = '';
			break;
			case '_wpg':
				$tablename = 'smw_relations';
				$pcolumn = 'relation_title';
				$extraconds = $this->getSQLConditions($requestoptions,'subject_title','subject_title');
			break;
			case '__nry':
				$tablename = 'smw_nary';
				$pcolumn = 'attribute_title';
				$extraconds = $this->getSQLConditions($requestoptions,'subject_title','subject_title');
			break;
			default:
				$tablename = 'smw_attributes';
				$pcolumn = 'attribute_title';
				$extraconds = $this->getSQLConditions($requestoptions,'subject_title','subject_title');
		}

		$res = $db->select( $db->tableName($tablename),
		                    'DISTINCT subject_id,subject_title,subject_namespace',
		                    $pcolumn .'=' . $db->addQuotes($property->getDBkey()) . $extraconds,
		                    'SMW::getAllPropertySubjects',
		                    $this->getSQLOptions($requestoptions,'subject_title') );
		$result = array();
		while($row = $db->fetchObject($res)) {
			$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
			$dv->setValues($row->subject_title, $row->subject_namespace, $row->subject_id);
			$result[] = $dv;
		}
		$db->freeResult($res);
		wfProfileOut("SMWSQLStore::getAllPropertySubjects (SMW)");
		return $result;
	}

	function getProperties(Title $subject, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getProperties (SMW)");
		$subjectid = $subject->getArticleID(); // avoid queries for nonexisting pages
		if ($subjectid <= 0) {
			wfProfileOut("SMWSQLStore::getProperties (SMW)");
			return array();
		}
		
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'subject_id=' . $db->addQuotes($subjectid) . $this->getSQLConditions($requestoptions,'attribute_title','attribute_title');

		$result = array();
		$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->attribute_title);
			}
		}
		$db->freeResult($res);
		$res = $db->select( $db->tableName('smw_longstrings'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->attribute_title);
			}
		}
		$db->freeResult($res);

		$sql = 'subject_id=' . $db->addQuotes($subjectid) .
		       $this->getSQLConditions($requestoptions,'relation_title','relation_title');
		$res = $db->select( $db->tableName('smw_relations'),
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'relation_title') );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->relation_title);
			}
		}
		$db->freeResult($res);
		$res = $db->select( $db->tableName('smw_nary'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->attribute_title);
			}
		}
		$db->freeResult($res);
		wfProfileOut("SMWSQLStore::getProperties (SMW)");
		return $result;
	}

	function getInProperties(SMWDataValue $value, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getInProperties (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$result = array();
		if ($value->getTypeID() == '_wpg') {
			$sql = 'object_namespace=' . $db->addQuotes($value->getNamespace()) .
				' AND object_title=' . $db->addQuotes($value->getDBkey()) .
				$this->getSQLConditions($requestoptions,'relation_title','relation_title');

			$res = $db->select( $db->tableName('smw_relations'),
								'DISTINCT relation_title',
								$sql, 'SMW::getInProperties', $this->getSQLOptions($requestoptions,'relation_title') );
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->relation_title);
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore::getInProperties (SMW)");
		return $result;
	}

///// Writing methods /////

	function deleteSubject(Title $subject) {
		wfProfileIn("SMWSQLStore::deleteSubjects (SMW)");
		$this->deleteSemanticData($subject);
		$db =& wfGetDB( DB_MASTER );
		// also delete any occurence of subject in object positions:
		$db->update('smw_relations', array('object_id' => NULL), array('object_id' => $subject->getArticleID()), 'SMW::deleteSubject::RelationObj');
		wfProfileOut("SMWSQLStore::deleteSubjects (SMW)");
	}

	function updateData(SMWSemanticData $data, $newpage) {
		wfProfileIn("SMWSQLStore::updateData (SMW)");
		$db =& wfGetDB( DB_MASTER );
		$subject = $data->getSubject()->getTitle();
		if ($newpage) { // set new ID in relation table
			$db->update('smw_relations', array('object_id' => $subject->getArticleID()), array('object_title' => $subject->getDBkey(), 'object_namespace' => $subject->getNamespace()), 'SMW::updateData::SetRelIDs');
		} else {
			$this->deleteSemanticData($subject);
		}

		// do bulk updates:
		$up_relations = array();
		$up_attributes = array();
		$up_longstrings = array();
		$up_specials = array();
		$up_subprops = array();
		$up_nary = array();
		$up_nary_relations = array();
		$up_nary_attributes = array();
		$up_nary_longstrings = array();

		$nkey = 0; // "id" for blank node created for naries

		//properties
		foreach($data->getProperties() as $key => $property) {
			$propertyValueArray = $data->getPropertyValues($property);
			if ($property instanceof Title) { // normal property
				foreach($propertyValueArray as $value) {
					if ($value->isValid()) {
						if ( ($value->getTypeID() == '_txt') || ($value->getTypeID() == '_cod') ){
							$up_longstrings[] =
								array( 'subject_id' => $subject->getArticleID(),
								       'subject_namespace' => $subject->getNamespace(),
								       'subject_title' => $subject->getDBkey(),
								       'attribute_title' => $property->getDBkey(),
								       'value_blob' => $value->getXSDValue() );
						} elseif ($value->getTypeID() == '_wpg') {
							$oid = $value->getArticleID();
							if ($oid == 0) { $oid = NULL; }
							$up_relations[] =
								array( 'subject_id' => $subject->getArticleID(),
								       'subject_namespace' => $subject->getNamespace(),
								       'subject_title' => $subject->getDBkey(),
								       'relation_title' => $property->getDBkey(),
								       'object_namespace' => $value->getNamespace(),
								       'object_title' => $value->getDBkey(),
								       'object_id' => $oid );
						} elseif ($value->getTypeID() == '__nry') {
							$up_nary[] =
								array( 'subject_id' => $subject->getArticleID(),
								       'subject_namespace' => $subject->getNamespace(),
								       'subject_title' => $subject->getDBkey(),
								       'attribute_title' => $property->getDBkey(),
								       'nary_key' => $nkey );
							$npos = 0;
							foreach ($value->getDVs() as $dv) {
								if ( ($dv !== NULL) && ($dv->isValid()) ) {
									switch ($dv->getTypeID()) {
									case '_wpg':
										$oid = $dv->getArticleID();
										if ($oid == 0) { $oid = NULL; }
										$up_nary_relations[] =
											array( 'subject_id' => $subject->getArticleID(),
											       'nary_key'   => $nkey,
											       'nary_pos'   => $npos,
											       'object_namespace' => $dv->getNamespace(),
											       'object_title' => $dv->getDBkey(),
											       'object_id' => $oid );
									break;
									case '_txt': case '_cod':
										$up_nary_longstrings[] =
											array( 'subject_id' => $subject->getArticleID(),
											       'nary_key'   => $nkey,
											       'nary_pos'   => $npos,
											       'value_blob' => $dv->getXSDValue() );
									break;
									default:
										$up_nary_attributes[] =
											array( 'subject_id' => $subject->getArticleID(),
											       'nary_key'   => $nkey,
											       'nary_pos'   => $npos,
											       'value_unit' => $dv->getUnit(),
											       'value_xsd' => $dv->getXSDValue(),
											       'value_num' => $dv->getNumericValue() );
									}
								}
								$npos++;
							}
							$nkey++;
						} else {
							$up_attributes[] =
								array( 'subject_id' => $subject->getArticleID(),
								       'subject_namespace' => $subject->getNamespace(),
								       'subject_title' => $subject->getDBkey(),
								       'attribute_title' => $property->getDBkey(),
								       'value_unit' => $value->getUnit(),
								       'value_xsd' => $value->getXSDValue(),
								       'value_num' => $value->getNumericValue() );
						}
					}
				}
			} else { // special property
				switch ($property) {
					case SMW_SP_INSTANCE_OF: case SMW_SP_SUBCLASS_OF: case SMW_SP_REDIRECTS_TO: case SMW_SP_CONCEPT_DESC:
						// don't store this, just used for display;
						/// NOTE: concept descriptions are ignored by that storage implementation
					break;
					case SMW_SP_SUBPROPERTY_OF:
						if ( $subject->getNamespace() != SMW_NS_PROPERTY ) {
							break;
						}
						foreach($propertyValueArray as $value) {
							if ( ($value->isValid()) && ($value->getNamespace() == SMW_NS_PROPERTY) )  {
								$up_subprops[] =
								array('subject_title' => $subject->getDBkey(),
								      'object_title' => $value->getDBkey());
							}
						}
					break;
					default: // normal special value
						foreach($propertyValueArray as $value) {
							if ( $value->isValid() ) { // filters out error-values etc.
								$stringvalue = $value->getXSDValue();
								$up_specials[] =
								array('subject_id' => $subject->getArticleID(),
								      'property_id' => $property,
								      'value_string' => $stringvalue);
							}
						}
					break;
				}
			}
		}

		// write to DB:
		if (count($up_relations) > 0) {
			$db->insert( 'smw_relations', $up_relations, 'SMW::updateRelData');
		}
		if (count($up_attributes) > 0) {
			$db->insert( 'smw_attributes', $up_attributes, 'SMW::updateAttData');
		}
		if (count($up_longstrings) > 0) {
			$db->insert( 'smw_longstrings', $up_longstrings, 'SMW::updateLongData');
		}
		if (count($up_specials) > 0) {
			$db->insert( 'smw_specialprops', $up_specials, 'SMW::updateSpecData');
		}
		if (count($up_subprops) > 0) {
			$db->insert( 'smw_subprops', $up_subprops, 'SMW::updateSubPropData');
		}
		if (count($up_nary) > 0) {
			$db->insert( 'smw_nary', $up_nary, 'SMW::updateNAryData');
		}
		if (count($up_nary_relations) > 0) {
			$db->insert( 'smw_nary_relations', $up_nary_relations, 'SMW::updateNAryRelData');
		}
		if (count($up_nary_attributes) > 0) {
			$db->insert( 'smw_nary_attributes', $up_nary_attributes, 'SMW::updateNAryAttData');
		}
		if (count($up_nary_longstrings) > 0) {
			$db->insert( 'smw_nary_longstrings', $up_nary_longstrings, 'SMW::updateNAryLongData');
		}

		if ($subject->getNamespace() == SMW_NS_PROPERTY) { // be sure that this is not invalid after update
			SMWDataValueFactory::clearTypeCache($subject);
		}

		wfProfileOut("SMWSQLStore::updateData (SMW)");
	}

	function changeTitle(Title $oldtitle, Title $newtitle, $pageid, $redirid=0) {
		wfProfileIn("SMWSQLStore::changeTitle (SMW)");
		$db =& wfGetDB( DB_MASTER );

		// First change all data for the old subject to its new name
		// (data moves with the subject, as it is part of the page)
		$cond_array = array( 'subject_title' => $oldtitle->getDBkey(),
		                     'subject_namespace' => $oldtitle->getNamespace() );
		$val_array  = array( 'subject_title' => $newtitle->getDBkey(),
		                     'subject_namespace' => $newtitle->getNamespace(),
		                     'subject_id' => $pageid );
		// Note on the above: usually the ID does not change, but setting it does not hurt either

		$db->update('smw_relations', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_attributes', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_longstrings', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_nary', $val_array, $cond_array, 'SMW::changeTitle');

		// properties need special treatment (special table layout)
		if ( $oldtitle->getNamespace() == SMW_NS_PROPERTY ) {
			if ( $newtitle->getNamespace() == SMW_NS_PROPERTY ) {
				$db->update('smw_subprops', array('subject_title' => $newtitle->getDBkey()), array('subject_title' => $oldtitle->getDBkey()), 'SMW::changeTitle');
			} else {
				$db->delete('smw_subprops', array('subject_title' => $oldtitle->getDBkey()), 'SMW::changeTitle');
			}
		}

		// Second change all objects referring to the old page
		// (objects are bound to the old name and do not point to the new page)
		if ($redirid == 0) $redirid = NULL; // use NULL in DB to unset id
		$cond_array = array( 'object_title' => $oldtitle->getDBkey(),
		                     'object_namespace' => $oldtitle->getNamespace() );
		$val_array  = array( 'object_id' => $redirid );

		$db->update('smw_relations', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_nary_relations', $val_array, $cond_array, 'SMW::changeTitle');

		// Third change all objects referring to the new page
		// (objects are bound to the old name and do not point to the new page)
		$cond_array = array( 'object_title' => $newtitle->getDBkey(),
		                     'object_namespace' => $newtitle->getNamespace() );
		$val_array  = array( 'object_id' => $pageid );

		$db->update('smw_relations', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_nary_relations', $val_array, $cond_array, 'SMW::changeTitle');

		wfProfileOut("SMWSQLStore::changeTitle (SMW)");
	}

///// Query answering /////

	/**
	 * The SQL store's implementation of query answering.
	 *
	 * TODO: we now have sorting even for subquery conditions. Does this work? Is it slow/problematic?
	 * NOTE: we do not support category wildcards, as they have no useful semantics in OWL/RDFS/LP/whatever
	 */
	function getQueryResult(SMWQuery $query) {
		wfProfileIn('SMWSQLStore::getQueryResult (SMW)');
		global $smwgQSortingSupport;
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deeper levels
		if ($query->querymode == SMWQuery::MODE_NONE) { // don't query, but return something to printer
			$result = new SMWQueryResult($prs, $query, true);
			wfProfileOut('SMWSQLStore::getQueryResult (SMW)');
			return $result;
		}

		$db =& wfGetDB( DB_SLAVE );

		// Build main query
		$this->m_usedtables = array();
		$this->m_sortkeys = $query->sortkeys;
		$this->m_sortfields = array();
		foreach ($this->m_sortkeys as $key => $order) {
			$this->m_sortfields[$key] = false; // no field found yet
		}

		$pagetable = $db->tableName('page');
		$from = $pagetable;
		$where = '';
		$curtables = array('PAGE' => $from);
		$this->createSQLQuery($query->getDescription(), $from, $where, $db, $curtables);

		// Prepare SQL options
		$sql_options = array();
		$sql_options['LIMIT'] = $query->getLimit() + 1;
		$sql_options['OFFSET'] = $query->getOffset();
		if ( $smwgQSortingSupport ) {
			$extraproperties = array(); // collect required extra property descriptions
			foreach ($this->m_sortkeys as $key => $order) {
				if ($this->m_sortfields[$key] == false) { // find missing property to sort by
					if ($key == '') { // sort by first column (page titles)
						$this->m_sortfields[$key] = "$pagetable.page_title";
					} else { // try to extend query
						$extrawhere = '';
						$sorttitle = Title::newFromText($key, SMW_NS_PROPERTY);
						if ($sorttitle !== NULL) { // careful, Title creation might still fail!
							$extraproperties[] = new SMWSomeProperty($sorttitle, new SMWThingDescription());
						}
					}
				}
			}
			if (count($extraproperties) > 0) {
				if (count($extraproperties) == 1) {
					$desc = end($extraproperties);
				} else {
					$desc = new SMWConjunction($extraproperties);
				}
				$this->createSQLQuery($desc, $from, $extrawhere, $db, $curtables);
				if ($extrawhere != '') {
					if ($where != '') {
						$where = "($where) AND ";
					}
					$where .= "($extrawhere)";
				}
			}
			foreach ($this->m_sortkeys as $key => $order) {
				if ($this->m_sortfields[$key] != false) { // field successfully added
					if (!array_key_exists('ORDER BY', $sql_options)) {
						$sql_options['ORDER BY'] = '';
					} else {
						$sql_options['ORDER BY'] .= ', ';
					}
					$sql_options['ORDER BY'] .= $this->m_sortfields[$key] . " $order ";
				}
			}
		}

		// Execute query and format result as array
		if ($query->querymode == SMWQuery::MODE_COUNT) {
			$res = $db->select($from,
			       "COUNT(DISTINCT $pagetable.page_id) AS count",
			        $where,
			        'SMW::getQueryResult',
			        $sql_options );
			$row = $db->fetchObject($res);
			$count = $row->count;
			$db->freeResult($res);
			wfProfileOut('SMWSQLStore::getQueryResult (SMW)');
			return $count;
			// TODO: report query errors?
		} elseif ($query->querymode == SMWQuery::MODE_DEBUG) { /// TODO: internationalise
			list( $startOpts, $useIndex, $tailOpts ) = $db->makeSelectOptions( $sql_options );
			$result = '<div style="border: 1px dotted black; background: #A1FB00; padding: 20px; ">' .
			          '<b>Generated Wiki-Query</b><br />' .
			          str_replace('[', '&#x005B;', $query->getDescription()->getQueryString()) . '<br />' .
			          '<b>Query-Size: </b>' . $query->getDescription()->getSize() . '<br />' .
			          '<b>Query-Depth: </b>' . $query->getDescription()->getDepth() . '<br />' .
			          '<b>SQL-Query</b><br />' .
			          "SELECT DISTINCT $pagetable.page_title as title, $pagetable.page_namespace as namespace" .
			          ' FROM ' . $from . ' WHERE ' . $where . $tailOpts . '<br />' .
			          '<b>SQL-Query options</b><br />';
			foreach ($sql_options as $key => $value) {
				$result .= "  $key=$value";
			}
			$result .= '<br /><b>Errors and Warnings</b><br />';
			foreach ($query->getErrors() as $error) {
				$result .= $error . '<br />';
			}
			$result .= '<br /><b>Auxilliary tables used</b><br />';
			foreach ($this->m_usedtables as $tablename) {
				$result .= $tablename . ': ';
				$res = $db->query( "SELECT title FROM $tablename", 'SMW::getQueryResult:DEBUG');
				while ( $row = $db->fetchObject($res) ) {
					$result .= $row->title . ', ';
				}
				$result .= '<br />';
			}
			$result .= '</div>';
			wfProfileOut('SMWSQLStore::getQueryResult (SMW)');
			return $result;
		} // else: continue

		$res = $db->select($from,
		       "DISTINCT $pagetable.page_title as title, $pagetable.page_namespace as namespace, $pagetable.page_id as id",
		        $where,
		        'SMW::getQueryResult',
		        $sql_options );

		$qr = array();
		$count = 0;
		while ( ($count<$query->getLimit()) && ($row = $db->fetchObject($res)) ) {
			$count++;
			//$qr[] = Title::newFromText($row->title, $row->namespace);
			$v = SMWDataValueFactory::newTypeIDValue('_wpg');
			$v->setValues($row->title, $row->namespace, $row->id);
			$qr[] = $v;
		}
		if ($db->fetchObject($res)) {
			$count++;
		}
		$db->freeResult($res);

		// Create result by executing print statements for everything that was fetched
		///TODO: use limit (and offset?) values for printouts?
		$result = new SMWQueryResult($prs, $query, ($count > $query->getLimit()) );
		foreach ($qr as $qt) {
			$row = array();
			foreach ($prs as $pr) {
				switch ($pr->getMode()) {
					case SMWPrintRequest::PRINT_THIS:
						$row[] = new SMWResultArray(array($qt), $pr);
						break;
					case SMWPrintRequest::PRINT_CATS:
						$row[] = new SMWResultArray($this->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF), $pr);
						break;
					case SMWPrintRequest::PRINT_PROP:
						$row[] = new SMWResultArray($this->getPropertyValues($qt->getTitle(),$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
						break;
					case SMWPrintRequest::PRINT_CCAT:
						$cats = $this->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF);
						$found = '0';
						foreach ($cats as $cat) {
							if ($cat->getDBkey() == $pr->getTitle()->getDBkey()) {
								$found = '1';
								break;
							}
						}
						$dv = SMWDataValueFactory::newTypeIDValue('_boo');
						$dv->setOutputFormat($pr->getOutputFormat());
						$dv->setXSDValue($found);
// 						$dv = SMWDataValueFactory::newTypeIDValue('_str',$found . ' Format:' . $pr->getOutputFormat() . '!');
						$row[] = new SMWResultArray(array($dv), $pr);
						break;
				}
			}
			$result->addRow($row);
		}
		wfProfileOut('SMWSQLStore::getQueryResult (SMW)');
		return $result;
	}

///// Special page functions /////

	function getPropertiesSpecial($requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getPropertiesSpecial (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$options = ' ORDER BY title';
		if ($requestoptions->limit > 0) {
			$options .= ' LIMIT ' . $requestoptions->limit;
		}
		if ($requestoptions->offset > 0) {
			$options .= ' OFFSET ' . $requestoptions->offset;
		}
		$res = $db->query('(SELECT relation_title as title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_relations') . ' GROUP BY relation_title) UNION ' .
		                  '(SELECT attribute_title as title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_attributes') . ' GROUP BY attribute_title) UNION ' .
		                  '(SELECT attribute_title as title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_longstrings') . ' GROUP BY attribute_title) UNION ' .
		                  '(SELECT attribute_title as title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_nary') . ' GROUP BY attribute_title)' . $options,
		                  'SMW::getPropertySubjects');
		$result = array();
		while($row = $db->fetchObject($res)) {
			$title = Title::makeTitle(SMW_NS_PROPERTY, $row->title);
			$result[] = array($title, $row->count);
		}
		$db->freeResult($res);
		wfProfileOut("SMWSQLStore::getPropertiesSpecial (SMW)");
		return $result;
	}

	function getUnusedPropertiesSpecial($requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getUnusedPropertiesSpecial (SMW)");
		/// FIXME filter out the builtin properties!
		$db =& wfGetDB( DB_SLAVE );
		$options = ' ORDER BY title';
		if ($requestoptions->limit > 0) {
			$options .= ' LIMIT ' . $requestoptions->limit;
		}
		if ($requestoptions->offset > 0) {
			$options .= ' OFFSET ' . $requestoptions->offset;
		}

		// Originally this was implemented as a set of left joins, but the performance was horrible.
		// It has been re-implemented using a scratch table
		
		/// Get a scratch table to delete from
		extract( $db->tableNames('page', 'smw_relations', 'smw_attributes', 'smw_longstrings', 'smw_nary', 'smw_subprops') );

		$db->query( 'CREATE TEMPORARY TABLE smw_unusedProps' .
		            ' ( title VARCHAR(255)) TYPE=MEMORY', 'SMW::getUnusedPropertiesSpecial' );

		$db->query( "INSERT INTO smw_unusedProps SELECT page_title FROM $page" .
		            " WHERE page_namespace=" . SMW_NS_PROPERTY , 'SMW::getUnusedPropertySubjects');
		$db->query( "DELETE smw_unusedProps.* FROM smw_unusedProps, $smw_relations" .
		            " WHERE title=relation_title" , 'SMW::getUnusedPropertySubjects');
		$db->query( "DELETE smw_unusedProps.* FROM smw_unusedProps, $smw_attributes" .
		            " WHERE title=attribute_title" , 'SMW::getUnusedPropertySubjects');
		$db->query( "DELETE smw_unusedProps.* FROM smw_unusedProps, $smw_longstrings" .
		            " WHERE title=attribute_title" , 'SMW::getUnusedPropertySubjects');
		$db->query( "DELETE smw_unusedProps.* FROM smw_unusedProps, $smw_nary" .
		            " WHERE title=attribute_title" , 'SMW::getUnusedPropertySubjects');
		$db->query( "DELETE smw_unusedProps.* FROM smw_unusedProps, $smw_subprops" .
		            " WHERE title=object_title" , 'SMW::getUnusedPropertySubjects');	

		$res = $db->query("SELECT title from smw_unusedProps " . $options, 'SMW::getUnusedPropertySubjects');
		$result = array();
		while($row = $db->fetchObject($res)) {
			$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->title);
		}
		$db->freeResult($res);
		$db->query("DROP table smw_unusedProps", 'SMW::getUnusedPropertySubjects');
		wfProfileOut("SMWSQLStore::getUnusedPropertiesSpecial (SMW)");
		return $result;
	}

	function getWantedPropertiesSpecial($requestoptions = NULL) {
		wfProfileIn("SMWSQLStore::getWantedPropertiesSpecial (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$options = ' ORDER BY count DESC';
		if ($requestoptions->limit > 0) {
			$options .= ' LIMIT ' . $requestoptions->limit;
		}
		if ($requestoptions->offset > 0) {
			$options .= ' OFFSET ' . $requestoptions->offset;
		}
		$res = $db->query('SELECT relation_title as title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_relations') . ' LEFT JOIN ' . $db->tableName('page') .
		                  ' ON (page_namespace=' . SMW_NS_PROPERTY .
		                  ' AND page_title=relation_title) WHERE page_id IS NULL GROUP BY relation_title' . $options,
		                  'SMW::getPropertySubjects');
		$result = array();
		while($row = $db->fetchObject($res)) {
			$title = Title::makeTitle(SMW_NS_PROPERTY, $row->title);
			$result[] = array($title, $row->count);
		}
		wfProfileOut("SMWSQLStore::getWantedPropertiesSpecial (SMW)");
		return $result;
	}

	function getStatistics() {
		wfProfileIn("SMWSQLStore::getStatistics (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$result = array();
		extract( $db->tableNames('smw_relations', 'smw_attributes', 'smw_longstrings', 'smw_nary', 'smw_specialprops') );

		$res = $db->query("SELECT COUNT(subject_id) AS count FROM $smw_relations", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$propuses = $row->count;
		$db->freeResult( $res );
		$res = $db->query("SELECT COUNT(subject_id) AS count FROM $smw_attributes", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$propuses += $row->count;
		$db->freeResult( $res );
		$res = $db->query("SELECT COUNT(subject_id) AS count FROM $smw_longstrings", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$propuses += $row->count;
		$db->freeResult( $res );
		$res = $db->query("SELECT COUNT(subject_id) AS count FROM $smw_nary", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$propuses += $row->count;
		$db->freeResult( $res );
		$result['PROPUSES'] = $propuses;

		$res = $db->query("SELECT COUNT(DISTINCT(relation_title)) AS count FROM $smw_relations", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$usedprops = $row->count;
		$db->freeResult( $res );
		$res = $db->query("SELECT COUNT(DISTINCT(attribute_title)) AS count FROM $smw_attributes", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$usedprops += $row->count;
		$db->freeResult( $res );
		$res = $db->query("SELECT COUNT(DISTINCT(attribute_title)) AS count FROM $smw_longstrings", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$usedprops += $row->count;
		$db->freeResult( $res );
		$res = $db->query("SELECT COUNT(DISTINCT(attribute_title)) AS count FROM $smw_nary", 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$usedprops += $row->count;
		$db->freeResult( $res );
		$result['USEDPROPS'] = $usedprops;

		$res = $db->query("SELECT COUNT(subject_id) AS count FROM $smw_specialprops WHERE property_id=" . $db->addQuotes(SMW_SP_HAS_TYPE), 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$result['DECLPROPS'] = $row->count;
		$db->freeResult( $res );

		wfProfileOut("SMWSQLStore::getStatistics (SMW)");
		return $result;
	}

///// Setup store /////

	function setup($verbose = true) {
		global $wgDBtype;
		$this->reportProgress("Setting up database configuration for SMW ...\n\n",$verbose);
		$this->reportProgress("Selected storage engine is \"SMWSQLStore\" (or an extension thereof).\n\n",$verbose);
		$this->reportProgress("============================================================================\n", $verbose);
		$this->reportProgress("WARNING: This store is deprecated and will loose support in future versions!\n", $verbose);
		$this->reportProgress("Please consider switching your wiki to SQLStore2! For details, see\n",$verbose);
		$this->reportProgress("http://semantic-mediawiki.org/wiki/Help:Installation_1.2#Notes_on_Upgrading\n",$verbose);
		$this->reportProgress("============================================================================\n\n", $verbose);

		if ($wgDBtype === 'postgres') {
			$this->reportProgress("For Postgres, please import the file SMW_Postgres_Schema.sql manually\n",$verbose);
			return;
		}

		$db =& wfGetDB( DB_MASTER );

		extract( $db->tableNames('smw_relations', 'smw_attributes', 'smw_longstrings', 'smw_specialprops', 'smw_subprops', 'smw_nary', 'smw_nary_attributes', 'smw_nary_longstrings', 'smw_nary_relations') );

		// create relation table
		$this->setupTable($smw_relations,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'relation_title'    => 'VARCHAR(255) binary NOT NULL',
		                    'object_namespace'  => 'INT(11) NOT NULL',
		                    'object_title'      => 'VARCHAR(255) binary NOT NULL',
		                    'object_id'        => 'INT(8) UNSIGNED'), $db, $verbose);
		$this->setupIndex($smw_relations, array('subject_id','relation_title','object_title,object_namespace','object_id'), $db);

		// create attribute table
		$this->setupTable($smw_attributes,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'attribute_title'   => 'VARCHAR(255) binary NOT NULL',
		                    'value_unit'        => 'VARCHAR(63) binary',
		                    'value_xsd'         => 'VARCHAR(255) binary NOT NULL',
		                    'value_num'         => 'DOUBLE'), $db, $verbose);
		$this->setupIndex($smw_attributes, array('subject_id','attribute_title','value_num','value_xsd'), $db);

		// create table for long string attributes
		$this->setupTable($smw_longstrings,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'attribute_title'   => 'VARCHAR(255) binary NOT NULL',
		                    'value_blob'        => 'MEDIUMBLOB'), $db, $verbose);
		$this->setupIndex($smw_longstrings, array('subject_id','attribute_title'), $db);

		// set up according tables for nary properties
		$this->setupTable($smw_nary,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'attribute_title'   => 'VARCHAR(255) binary NOT NULL',
		                    'nary_key'          => 'INT(8) UNSIGNED NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_nary, array('subject_id','attribute_title','subject_id,nary_key'), $db);
		$this->setupTable($smw_nary_relations,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'nary_key'          => 'INT(8) UNSIGNED NOT NULL',
		                    'nary_pos'          => 'INT(8) UNSIGNED NOT NULL',
		                    'object_namespace'  => 'INT(11) NOT NULL',
		                    'object_title'      => 'VARCHAR(255) binary NOT NULL',
		                    'object_id'         => 'INT(8) UNSIGNED'), $db, $verbose);
		$this->setupIndex($smw_nary_relations, array('subject_id,nary_key','object_title,object_namespace','object_id'), $db);
		$this->setupTable($smw_nary_attributes,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'nary_key'          => 'INT(8) UNSIGNED NOT NULL',
		                    'nary_pos'          => 'INT(8) UNSIGNED NOT NULL',
		                    'value_unit'        => 'VARCHAR(63) binary',
		                    'value_xsd'         => 'VARCHAR(255) binary NOT NULL',
		                    'value_num'         => 'DOUBLE'), $db, $verbose);
		$this->setupIndex($smw_nary_attributes, array('subject_id,nary_key','value_num','value_xsd'), $db);
		$this->setupTable($smw_nary_longstrings,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'nary_key'          => 'INT(8) UNSIGNED NOT NULL',
		                    'nary_pos'          => 'INT(8) UNSIGNED NOT NULL',
		                    'value_blob'        => 'MEDIUMBLOB'), $db, $verbose);
		$this->setupIndex($smw_nary_longstrings, array('subject_id,nary_key'), $db);

		// create table for special properties
		$this->setupTable($smw_specialprops,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'property_id'       => 'SMALLINT(6) NOT NULL',
		                    'value_string'      => 'VARCHAR(255) binary NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_specialprops, array('subject_id', 'property_id', 'subject_id,property_id'), $db);

		// create table for subproperty relationships
		$this->setupTable($smw_subprops,
		              array('subject_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'object_title'      => 'VARCHAR(255) binary NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_subprops, array('subject_title', 'object_title'), $db);

		$this->reportProgress("Database initialised successfully.\n",$verbose);
		return true;
	}

	function drop($verbose = true) {
		$this->reportProgress("deleting all database content and tables generated by SMW ...\n\n",$verbose);
		$db =& wfGetDB( DB_MASTER );
		$tables = array('smw_relations', 'smw_attributes', 'smw_longstrings', 'smw_specialprops', 'smw_subprops', 'smw_nary', 'smw_nary_attributes', 'smw_nary_longstrings', 'smw_nary_relations');

		foreach ($tables as $table) {
			$name = $db->tableName($table);
			$db->query("DROP TABLE IF EXISTS $name", 'SMWSQLStore::drop');
			$this->reportProgress(" ... dropped table $name.\n", $verbose);
		}
		$this->reportProgress("All data removed successfully.\n",$verbose);
		return true;
	}


///// Private methods /////

	/**
	 * Transform input parameters into a suitable array of SQL options.
	 * The parameter $valuecol defines the string name of the column to which
	 * sorting requests etc. are to be applied.
	 */
	protected function getSQLOptions($requestoptions, $valuecol = NULL) {
		$sql_options = array();
		if ($requestoptions !== NULL) {
			if ($requestoptions->limit > 0) {
				$sql_options['LIMIT'] = $requestoptions->limit;
			}
			if ($requestoptions->offset > 0) {
				$sql_options['OFFSET'] = $requestoptions->offset;
			}
			if ( ($valuecol !== NULL) && ($requestoptions->sort) ) {
				$sql_options['ORDER BY'] = $requestoptions->ascending ? $valuecol : $valuecol . ' DESC';
			}
		}
		return $sql_options;
	}

	/**
	 * Transform input parameters into a suitable string of additional SQL conditions.
	 * The parameter $valuecol defines the string name of the column to which
	 * value restrictions etc. are to be applied.
	 * @param $requestoptions object with options
	 * @param $valuecol name of SQL column to which conditions apply
	 * @param $labelcol name of SQL column to which string conditions apply, if any
	 */
	protected function getSQLConditions($requestoptions, $valuecol, $labelcol = NULL) {
		$sql_conds = '';
		if ($requestoptions !== NULL) {
			$db =& wfGetDB( DB_SLAVE );
			if ($requestoptions->boundary !== NULL) { // apply value boundary
				if ($requestoptions->ascending) {
					if ($requestoptions->include_boundary) {
						$op = ' >= ';
					} else {
						$op = ' > ';
					}
				} else {
					if ($requestoptions->include_boundary) {
						$op = ' <= ';
					} else {
						$op = ' < ';
					}
				}
				$sql_conds .= ' AND ' . $valuecol . $op . $db->addQuotes($requestoptions->boundary);
			}
			if ($labelcol !== NULL) { // apply string conditions
				foreach ($requestoptions->getStringConditions() as $strcond) {
					if ($labelcol == 'value_xsd') {
						$string = str_replace('_', '\_', $strcond->string);
					} else { // equate ' ' to '_' for title strings
						$string = str_replace(array('_', ' '), array('\_', '\_'), $strcond->string);
					}
					switch ($strcond->condition) {
						case SMWStringCondition::STRCOND_PRE:
							$string .= '%';
							break;
						case SMWStringCondition::STRCOND_POST:
							$string = '%' . $string;
							break;
						case SMWStringCondition::STRCOND_MID:
							$string = '%' . $string . '%';
							break;
					}
					$sql_conds .= ' AND ' . $labelcol . ' LIKE ' . $db->addQuotes($string);
				}
			}
		}
		return $sql_conds;
	}

	/**
	 * Delete all semantic data stored for the given subject.
	 * Used for update purposes.
	 */
	protected function deleteSemanticData(Title $subject) {
		$db =& wfGetDB( DB_MASTER );
		$db->delete('smw_relations',
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Relations');
		$db->delete('smw_attributes',
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Attributes');
		$db->delete('smw_longstrings',
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Longstrings');
		$db->delete('smw_specialprops',
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Specialprops');
		$db->delete('smw_nary',
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::NAry');
		if ($db->affectedRows() != 0) {
			$db->delete('smw_nary_relations',
			            array('subject_id' => $subject->getArticleID()),
			            'SMW::deleteSubject::NAryRelations');
			$db->update('smw_nary_relations', array('object_id' => NULL), array('object_id' => $subject->getArticleID()), 'SMW::deleteSubject::NAryRelationsObj');
			$db->delete('smw_nary_attributes',
			            array('subject_id' => $subject->getArticleID()),
			            'SMW::deleteSubject::NAryAttributes');
			$db->delete('smw_nary_longstrings',
			            array('subject_id' => $subject->getArticleID()),
			            'SMW::deleteSubject::NaryLongstrings');
		}
		if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
			$db->delete('smw_subprops',
			            array('subject_title' => $subject->getDBkey()),
			            'SMW::deleteSubject::Subprops');
		}
	}

	/**
	 * Find out if the given page is a redirect and determine its target.
	 * Return the target or the page itself if it is not redirect.
	 */
	protected function getRedirectTarget($page, &$db) {
		$options = array('LIMIT' => '1');
		$id = $page->getArticleID();
		if ($id == 0) { // page not existing, return
			return $page;
		}
		$res = $db->select($db->tableName('redirect'), 'rd_namespace, rd_title', 'rd_from=' . $id, 'SMW::getRedirectTarget', $options);
		if ($row = $db->fetchObject($res)) {
			$result = SMWDataValueFactory::newTypeIDValue('_wpg');
			$result->setValues($row->rd_title, $row->rd_namespace);
			if ($result->isValid()) {
				return $result;
			}
		}
		return $page;
	}

	/**
	 * Find a suitable table field name in the currently available tables that holds the
	 * relevant page id. This can be a field from a (possibly auxilliary) page table or
	 * from one of SMW's relation tables. If no field is available, return false.
	 */
	protected function getCurrentIDField(&$from, &$db, &$curtables, $nary_pos = '') {
		$id = false;
		if ($this->addJoin('pRELS', $from, $db, $curtables, $nary_pos)) {
			$id = $curtables['pRELS'] . '.object_id';
		} elseif ($this->addJoin('PAGE', $from, $db, $curtables, $nary_pos)) { // fallback
			$id = $curtables['PAGE'] . '.page_id';
		}
		return $id;
	}

	/**
	 * Make a (temporary) table that contains the lower closure of the given category
	 * wrt. the category table.
	 */
	protected function getCategoryTable($cats, &$db) {
		wfProfileIn("SMWSQLStore::getCategoryTable (SMW)");
		global $smwgQSubcategoryDepth;

		$sqlvalues = '';
		$hashkey = '';
		foreach ($cats as $cat) {
			if ($sqlvalues != '') {
				$sqlvalues .= ', ';
			}
			$sqlvalues .= '(' . $db->addQuotes($cat->getDBkey()) . ')';
			$hashkey .= ']' . $cat->getDBkey();
		}

		$tablename = 'cats' . SMWSQLStore::$m_tablenum++;
		$this->m_usedtables[] = $tablename;
		// TODO: unclear why this commit is needed -- is it a MySQL 4.x problem?
		$db->query("COMMIT");
		$db->query( 'CREATE TEMPORARY TABLE ' . $tablename .
		            '( title VARCHAR(255) binary NOT NULL PRIMARY KEY)
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		if (array_key_exists($hashkey, SMWSQLStore::$m_categorytables)) { // just copy known result
			$db->query("INSERT INTO $tablename (title) SELECT " .
			            SMWSQLStore::$m_categorytables[$hashkey] .
			            '.title FROM ' . SMWSQLStore::$m_categorytables[$hashkey],
			           'SMW::getCategoryTable');
			wfProfileOut("SMWSQLStore::getCategoryTable (SMW)");
			return $tablename;
		}

		// Create multiple temporary tables for recursive computation
		$db->query( 'CREATE TEMPORARY TABLE smw_newcats
		             ( title VARCHAR(255) binary NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		$db->query( 'CREATE TEMPORARY TABLE smw_rescats
		             ( title VARCHAR(255) binary NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		$tmpnew = 'smw_newcats';
		$tmpres = 'smw_rescats';

		$pagetable = $db->tableName('page');
		$cltable = $db->tableName('categorylinks');
		$db->query("INSERT INTO $tablename (title) VALUES " . $sqlvalues, 'SMW::getCategoryTable');
		$db->query("INSERT INTO $tmpnew (title) VALUES " . $sqlvalues, 'SMW::getCategoryTable');

		for ($i=0; $i<$smwgQSubcategoryDepth; $i++) {
			$db->query("INSERT INTO $tmpres (title) SELECT $pagetable.page_title
			            FROM $cltable,$pagetable,$tmpnew WHERE
			            $cltable.cl_to=$tmpnew.title AND
			            $pagetable.page_namespace=" . NS_CATEGORY . " AND
			            $pagetable.page_id=$cltable.cl_from", 'SMW::getCategoryTable');
			$db->query("INSERT IGNORE INTO $tablename (title) SELECT $tmpres.title
			            FROM $tmpres", 'SMW::getCategoryTable');
			if ($db->affectedRows() == 0) { // no change, exit loop
				break;
			}
			$db->query('TRUNCATE TABLE ' . $tmpnew, 'SMW::getCategoryTable'); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		SMWSQLStore::$m_categorytables[$hashkey] = $tablename;
		$db->query('DROP TEMPORARY TABLE smw_newcats', 'SMW::getCategoryTable');
		$db->query('DROP TEMPORARY TABLE smw_rescats', 'SMW::getCategoryTable');
		wfProfileOut("SMWSQLStore::getCategoryTable (SMW)");
		return $tablename;
	}

	/**
	 * Make a (temporary) table that contains the lower closure of the given property
	 * wrt. the subproperty relation.
	 */
	protected function getPropertyTable($propname, &$db) {
		wfProfileIn("SMWSQLStore::getPropertyTable (SMW)");
		global $smwgQSubpropertyDepth;

		$tablename = 'prop' . SMWSQLStore::$m_tablenum++;
		$this->m_usedtables[] = $tablename;
		$db->query( 'CREATE TEMPORARY TABLE ' . $tablename .
		            '( title VARCHAR(255) binary NOT NULL PRIMARY KEY)
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		if (array_key_exists($propname, SMWSQLStore::$m_propertytables)) { // just copy known result
			$db->query("INSERT INTO $tablename (title) SELECT " .
			            SMWSQLStore::$m_propertytables[$propname] .
			            '.title FROM ' . SMWSQLStore::$m_propertytables[$propname],
			           'SMW::getPropertyTable');
			wfProfileOut("SMWSQLStore::getPropertyTable (SMW)");
			return $tablename;
		}

		// Create multiple temporary tables for recursive computation
		$db->query( 'CREATE TEMPORARY TABLE smw_new
		             ( title VARCHAR(255) binary NOT NULL )
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		$db->query( 'CREATE TEMPORARY TABLE smw_res
		             ( title VARCHAR(255) binary NOT NULL )
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$sptable = $db->tableName('smw_subprops');
		$db->query("INSERT INTO $tablename (title) VALUES (" . $db->addQuotes($propname) . ')', 'SMW::getPropertyTable');
		$db->query("INSERT INTO $tmpnew (title) VALUES (" . $db->addQuotes($propname) . ')', 'SMW::getPropertyTable');

		for ($i=0; $i<$smwgQSubpropertyDepth; $i++) {
			$db->query("INSERT INTO $tmpres (title) SELECT $sptable.subject_title
			            FROM $sptable,$tmpnew WHERE
			            $sptable.object_title=$tmpnew.title", 'SMW::getPropertyTable');
			if ($db->affectedRows() == 0) { // no change, exit loop
				break;
			}
			$db->query("INSERT IGNORE INTO $tablename (title) SELECT $tmpres.title
			            FROM $tmpres", 'SMW::getPropertyTable');
			if ($db->affectedRows() == 0) { // no change, exit loop
				break;
			}
			$db->query('TRUNCATE TABLE ' . $tmpnew, 'SMW::getPropertyTable'); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		SMWSQLStore::$m_propertytables[$propname] = $tablename;
		$db->query('DROP TEMPORARY TABLE smw_new', 'SMW::getPropertyTable');
		$db->query('DROP TEMPORARY TABLE smw_res', 'SMW::getPropertyTable');
		wfProfileOut("SMWSQLStore::getPropertyTable (SMW)");
		return $tablename;
	}

	/**
	 * Add the table $tablename to the $from condition via an inner join,
	 * using the tables that are already available in $curtables (and extending
	 * $curtables with the new table). Return the table name if successful or false
	 * if it wasn't possible to make a suitable inner join.
	 *
	 * The method in fact is very simple: since queries are tree-shaped, there is
	 * always some "current node" (most often some wikipage). To add new conditions
	 * to this node, joins must be created. These are possible with all basic
	 * semantic tables. In recursive calls, further conditions for subqueries might
	 * be added: in this case the subject changes, the existing table list is cleared
	 * by the query creation method, and only a single table (the "path" along which the
	 * descent into the query structure happened) is kept. Such tables are prefixed
	 * whith 'p' to distinguish them from yet to follow new condition tables for the
	 * new node.
	 *
	 * In rare cases, the condition on a subnode actually introduces the
	 * 'p'-table: this happens when naries are evaluated, and replaces the nary's
	 * simulated intermediate node with its subquery condition. Note that those joins
	 * do not require redirect(equality) handling as they do not involve actual pages.
	 *
	 * Finally, one can add a redirect table (this is a LEFT JOIN in order not to make
	 * the existence of a redirect a new condition) and possibly an additional pagetable
	 * to resolve redirect target ids.
	 *
	 * That's all. The method can be read and modified case by case, each of which is
	 * rather short and completely independent from the other cases.
	 */
	protected function addJoin($tablename, &$from, &$db, &$curtables, $nary_pos = '') {
		global $smwgQEqualitySupport;
		if (array_key_exists($tablename, $curtables)) { // table already present
			return $curtables[$tablename];
		}

		if ($tablename == 'PAGE') {
			if ($this->addJoin('pRELS', $from, $db, $curtables, $nary_pos)) {
				$curtables['PAGE'] = 'p' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('page') . ' AS ' . $curtables['PAGE'] . ' ON (' .
				           $curtables['PAGE'] . '.page_title=' . $curtables['pRELS'] . '.object_title AND ' .
				           $curtables['PAGE'] . '.page_namespace=' . $curtables['pRELS'] . '.object_namespace)';
				return $curtables['PAGE'];
			}
		} elseif ($tablename == 'CATS') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['CATS'] = 'cl' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['CATS'] . '.cl_from=' . $id;
				if ( ($smwgQEqualitySupport === SMW_EQ_FULL) && (array_key_exists('pRELS', $curtables))) {
					// only do this at inner queries (pRELS set)
					$this->addJoin('REDIPAGE', $from, $db, $curtables, $nary_pos);
					$cond = '((' . $cond . ') OR (' .
					  $curtables['REDIPAGE'] . '.page_id=' . $curtables['CATS'] . '.cl_from))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('categorylinks') . ' AS ' . $curtables['CATS'] . ' ON ' . $cond;
				return $curtables['CATS'];
			}
		} elseif ($tablename == 'RELS') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['RELS'] = 'rel' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['RELS'] . '.subject_id=' . $id;
				if ( ($smwgQEqualitySupport === SMW_EQ_FULL) && (array_key_exists('pRELS', $curtables))) {
					// only do this at inner queries (pRELS set)
					$this->addJoin('REDIRECT', $from, $db, $curtables, $nary_pos);
					$cond = '((' . $cond . ') OR (' .
					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['RELS'] . '.subject_title AND ' .
					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['RELS'] . '.subject_namespace))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('smw_relations') . ' AS ' . $curtables['RELS'] . ' ON ' . $cond;
				return $curtables['RELS'];
			}
		} elseif ($tablename == 'ATTS') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['ATTS'] = 'att' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['ATTS'] . '.subject_id=' . $id;
				if ( ($smwgQEqualitySupport === SMW_EQ_FULL) && (array_key_exists('pRELS', $curtables))) {
					// only do this at inner queries (pREL set)
					$this->addJoin('REDIRECT', $from, $db, $curtables, $nary_pos);
					$cond = '((' . $cond . ') OR (' .
					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['ATTS'] . '.subject_title AND ' .
					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['ATTS'] . '.subject_namespace))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('smw_attributes') . ' AS ' . $curtables['ATTS'] . ' ON ' . $cond;
				return $curtables['ATTS'];
			}
		} elseif ($tablename == 'TEXT') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['TEXT'] = 'txt' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_longstrings') . ' AS ' . $curtables['TEXT'] . ' ON ' . $curtables['TEXT'] . '.subject_id=' . $id;
				return $curtables['TEXT'];
			}
		} elseif ($tablename == 'NARY') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['NARY'] = 'nary' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['NARY'] . '.subject_id=' . $id;
				if ( ($smwgQEqualitySupport === SMW_EQ_FULL) && (array_key_exists('pRELS', $curtables))) {
					// only do this at inner queries (pRELS set)
					$this->addJoin('REDIRECT', $from, $db, $curtables, $nary_pos);
					$cond = '((' . $cond . ') OR (' .
					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['RELS'] . '.subject_title AND ' .
					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['RELS'] . '.subject_namespace))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('smw_nary') . ' AS ' . $curtables['NARY'] . ' ON ' . $cond;
				return $curtables['NARY'];
			}
		} elseif ($tablename == 'pATTS') {
			if ( ($nary_pos !== '') && (array_key_exists('pNARY', $curtables)) ) {
				$curtables['pATTS'] = 'natt' . SMWSQLStore::$m_tablenum++;
				$cond = '(' .
				        $curtables['pATTS'] . '.subject_id=' . $curtables['pNARY'] . '.subject_id AND ' .
				        $curtables['pATTS'] . '.nary_key=' . $curtables['pNARY'] . '.nary_key AND ' .
				        $curtables['pATTS'] . '.nary_pos=' . $db->addQuotes($nary_pos) . ')';
				$from .= ' INNER JOIN ' . $db->tableName('smw_nary_attributes') . ' AS ' .
				         $curtables['pATTS'] . ' ON ' . $cond;
				return $curtables['pATTS'];
			}
		} elseif ($tablename == 'pRELS') {
			if ( ($nary_pos !== '') && (array_key_exists('pNARY', $curtables)) ) {
				$curtables['pRELS'] = 'nrel' . SMWSQLStore::$m_tablenum++;
				$cond = '(' .
				        $curtables['pRELS'] . '.subject_id=' . $curtables['pNARY'] . '.subject_id AND ' .
				        $curtables['pRELS'] . '.nary_key=' . $curtables['pNARY'] . '.nary_key AND ' .
				        $curtables['pRELS'] . '.nary_pos=' . $db->addQuotes($nary_pos) . ')';
				$from .= ' INNER JOIN ' . $db->tableName('smw_nary_relations') . ' AS ' . $curtables['pRELS'] . ' ON ' . $cond;
				return $curtables['pRELS'];
			}
		} elseif ($tablename == 'pTEXT') {
			if ( ($nary_pos !== '') && (array_key_exists('pNARY', $curtables)) ) {
				$curtables['pTEXT'] = 'ntxt' . SMWSQLStore::$m_tablenum++;
				$cond = '(' .
				        $curtables['pTEXT'] . '.subject_id=' . $curtables['pNARY'] . '.subject_id AND ' .
				        $curtables['pTEXT'] . '.nary_key=' . $curtables['pNARY'] . '.nary_key AND ' .
				        $curtables['pTEXT'] . '.nary_pos=' . $db->addQuotes($nary_pos) . ')';
				$from .= ' INNER JOIN ' . $db->tableName('smw_nary_longstrings') . ' AS ' . $curtables['pTEXT'] . ' ON ' . $cond;
				return $curtables['pTEXT'];
			}
		} elseif ($tablename == 'REDIRECT') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['REDIRECT'] = 'rd' . SMWSQLStore::$m_tablenum++;
				$from .= ' LEFT JOIN ' . $db->tableName('redirect') . ' AS ' . $curtables['REDIRECT'] . ' ON ' . $curtables['REDIRECT'] . '.rd_from=' . $id;
				return $curtables['REDIRECT'];
			}
		} elseif ($tablename == 'REDIPAGE') { // +another copy of page for getting ids of redirect targets; *ouch*
			if ($this->addJoin('REDIRECT', $from, $db, $curtables, $nary_pos)) {
				$curtables['REDIPAGE'] = 'rp' . SMWSQLStore::$m_tablenum++;
				$from .= ' LEFT JOIN ' . $db->tableName('page') . ' AS ' . $curtables['REDIPAGE'] . ' ON (' .
				         $curtables['REDIRECT'] . '.rd_title=' . $curtables['REDIPAGE'] . '.page_title AND ' .
					     $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['REDIPAGE'] . '.page_namespace)';
				return $curtables['REDIPAGE'];
			}
		}
		return false;
	}

	/**
	 * Create an SQL query for a given description. The query is defined by call-by-ref
	 * parameters for conditions (WHERE) and tables (FROM). Further conditions are not
	 * encoded in the description. Additional conditions refer to tables that are already
	 * used in the query, whose aliases are given in $curtables. It may also happen that
	 * inner joins with existing tables ($curtables) are added to formulate the new condition.
	 * In any case, $curtables should never be completely empty (or otherwise nothing will
	 * be computed).
	 *
	 * Some notes on sorting: sorting is applied only to fields that appear in the query
	 * by verifying conditions, and the sorting conditions thus operate on the values that
	 * satisfy the given conditions. This may have side effects in cases where one property
	 * that shall be sorted has multiple values. If no condition other than existence applies
	 * to such a property, the value that is relevant for sorting is not really determined and
	 * the behaviour of SQL is not clear (I think). If the condition preselects larger or smaller
	 * values, however, then these would probably be used for sorting. Overall this should not
	 * be a problem, since it only occurs in cases where the sort order is not fully specified anyway.
	 *
	 * Also, sorting may impair performance, since SQL needs to keep track of additional values.
	 *
	 * @param $description The SMWDescription to be processed.
	 * @param &$from The string of computed FROM statements (with aliases for tables), appended to supplied string.
	 * @param &$where The string of computed WHERE conditions, appended to supplied string.
	 * @param $db The database object
	 * @param $curtables Array with names of aliases of tables refering to the 'current' element (the one to which the description basically applies).
	 * @param $nary_pos If the subcondition is directly appended to an nary relation, this parameter holds the numerical index of the position in the nary in order to be able to join condition tables to that position.
	 *
	 * @todo Maybe there need to be further optimisations in certain cases (atomic implementation for 
	 * common nestings of descriptions?).
	 */
	protected function createSQLQuery(SMWDescription $description, &$from, &$where, &$db, &$curtables, $nary_pos = '') {
		$subwhere = '';
		if ($description instanceof SMWThingDescription) {
			// nothing to check
		} elseif ($description instanceof SMWClassDescription) {
			if ($table = $this->addJoin('CATS', $from, $db, $curtables, $nary_pos)) {
				global $smwgQSubcategoryDepth;
				if ($smwgQSubcategoryDepth > 0) {
					$ct = $this->getCategoryTable($description->getCategories(), $db);
					$from = '`' . $ct . '`, ' . $from;
					$where = "$ct.title=" . $table . '.cl_to';
				} else {
					foreach ($description->getCategories() as $cat) {
						if ($subwhere != '') {
							$subwhere .= ' OR ';
						}
						$subwhere .= '(' . $table . '.cl_to=' . $db->addQuotes($cat->getDBkey()) . ')';
					}
				if ($where != '') {
					$where .= ' AND ';
				}
				$where .= "($subwhere)";
				}
			}
		} elseif ($description instanceof SMWNamespaceDescription) {
			if ($table = $this->addJoin('pRELS', $from, $db, $curtables, $nary_pos)) {
				$where .=  $table . '.object_namespace=' . $db->addQuotes($description->getNamespace());
			} elseif ($table = $this->addJoin('PAGE', $from, $db, $curtables, $nary_pos)) {
				$where .=  $table . '.page_namespace=' . $db->addQuotes($description->getNamespace());
			}
		} elseif ($description instanceof SMWValueDescription) {
			switch ($description->getDatavalue()->getTypeID()) {
				case '_txt': case '_cod': // possibly pull in longstring table (for naries)
					$this->addJoin('pTEXT', $from, $db, $curtables, $nary_pos);
				break;
				case '_wpg':
					global $smwgQEqualitySupport;
					if ($smwgQEqualitySupport != SMW_EQ_NONE) {
						$page = $this->getRedirectTarget($description->getDatavalue(), $db);
					} else {
						$page = $description->getDatavalue();
					}
					if ($table = $this->addJoin('pRELS', $from, $db, $curtables, $nary_pos)) {
						$cond = $table . '.object_title=' .
						        $db->addQuotes($page->getDBkey()) . ' AND ' .
						        $table . '.object_namespace=' . $page->getNamespace();
						if ( ($smwgQEqualitySupport != SMW_EQ_NONE) &&
						     ($this->addJoin('REDIRECT', $from, $db, $curtables, $nary_pos)) ) {
							$cond = '(' . $cond . ') OR (' .
							   $curtables['REDIRECT'] . '.rd_title=' . $db->addQuotes($page->getDBkey()) . ' AND ' .
							   $curtables['REDIRECT'] . '.rd_namespace=' . $page->getNamespace() . ')';
						}
						$where .= $cond;
					} elseif ($table = $this->addJoin('PAGE', $from, $db, $curtables, $nary_pos)) {
						$where .= $table . '.page_title=' . $db->addQuotes($page->getDBkey()) . ' AND ' .
						          $table . '.page_namespace=' . $page->getNamespace();
					}
				break;
				default:
					if ( $table = $this->addJoin('pATTS', $from, $db, $curtables, $nary_pos) ) {
						if ($description->getDatavalue()->isNumeric()) {
							$valuefield = 'value_num';
							$value = $description->getDatavalue()->getNumericValue();
						} else {
							$valuefield = 'value_xsd';
							$value = $description->getDatavalue()->getXSDValue();
						}
						switch ($description->getComparator()) {
							case SMW_CMP_LEQ: $op = '<='; break;
							case SMW_CMP_GEQ: $op = '>='; break;
							case SMW_CMP_NEQ: $op = '!='; break;
							case SMW_CMP_LIKE:
								if ($description->getDatavalue()->getTypeID() == '_str') {
									$op = ' LIKE ';
									$value =  str_replace(array('%', '_', '*', '?'), array('\%', '\_', '%', '_'), $value);
								} else { // LIKE only works for strings at the moment
									$op = '=';
								}
							break;
							case SMW_CMP_EQ: default: $op = '='; break;
						}
						///TODO: implement check for unit
						$where .= $table . '.' .  $valuefield . $op . $db->addQuotes($value);
					}
			}
		} elseif ($description instanceof SMWValueList) {
			for ($i=0; $i<$description->getCount(); $i++) {
				$desc = $description->getDescription($i);
				if ($desc === NULL) {
					continue;
				}
				$subwhere = '';
				$nexttables = array( 'pNARY' => $curtables['pNARY'] );
				$this->createSQLQuery($desc, $from, $subwhere, $db, $nexttables, $i);
				if ($where != '') {
					$where .= ' AND ';
				}
				if ($subwhere != '') {
					$where .= "($subwhere)";
				}
			}
		} elseif ($description instanceof SMWConjunction) {
			foreach ($description->getDescriptions() as $subdesc) {
				/// TODO: this is not optimal -- we drop more table aliases than needed, but its hard to find out what is feasible in recursive calls ...
				$nexttables = array();
				// pull in page to prevent every child description pulling it separately!
				if ( array_key_exists('PAGE', $curtables) ) {
					$nexttables['PAGE'] = $curtables['PAGE'];
				}
				if ($this->addJoin('pRELS', $from, $db, $curtables, $nary_pos)) {
					$nexttables['pRELS'] = $curtables['pRELS'];
				}
				$this->createSQLQuery($subdesc, $from, $subwhere, $db, $nexttables, $nary_pos);
				if ($subwhere != '') {
					if ($where != '') {
						$where .= ' AND ';
					}
					$where .= '(' . $subwhere . ')';
					$subwhere = '';
				}
			}
		} elseif ($description instanceof SMWDisjunction) {
			foreach ($description->getDescriptions() as $subdesc) {
				/// FIXME: This does not work when disjunctions refer not to values but to property conditions. 
				// The reason is that the WHERE part uses OR (as it should), but new tables are 
				// always added with INNER JOIN to the current base (page of prel table). But 
				// INNER JOINS are like conjunctions and impose unwanted restrictions that reduce 
				// the result size. The only way of solving this without using UNION right away will 
				// be to move join conditions into WHERE parts -- which might hurt performance a lot
				// (using new tables here, as in the case of conjunction, will not do any good)
				$this->createSQLQuery($subdesc, $from, $subwhere, $db, $curtables, $nary_pos);
				if ($subwhere != '') {
					if ($where != '') {
						$where .= ' OR ';
					}
					$where .= '(' . $subwhere . ')';
					$subwhere = '';
				}
			}
		} elseif ($description instanceof SMWSomeProperty) {
			$id = SMWDataValueFactory::getPropertyObjectTypeID($description->getProperty());
			$sortfield = false;
			$sortkey = false;
			switch ($id) {
				case '_wpg':
					$tablename = 'RELS';
					$pcolumn = 'relation_title';
					$sub = true;
					if ( array_key_exists($description->getProperty()->getDBkey(), $this->m_sortkeys) ) {
						$sortkey = 'object_title';
						$sortfield = 'object_title';
					}
				break;
				case '_txt': case '_cod':
					$tablename = 'TEXT';
					$pcolumn = 'attribute_title';
					$sub = false; //no recursion: we do not support further conditions on text-type values
				break;
				case '__nry':
					$tablename = 'NARY';
					$pcolumn = 'attribute_title';
					$sub = true;
				break;
				default:
					$tablename = 'ATTS';
					$pcolumn = 'attribute_title';
					$sub = true;
					if ( array_key_exists($description->getProperty()->getDBkey(), $this->m_sortkeys) ) {
						$sortkey = $description->getProperty()->getDBkey();
						if (SMWDataValueFactory::newTypeIDValue($id)->isNumeric()) {
							$sortfield = 'value_num';
						} else {
							$sortfield = 'value_xsd';
						}
					}
			}
			if ($table = $this->addJoin($tablename, $from, $db, $curtables, $nary_pos)) {
				global $smwgQSubpropertyDepth;
				if ($smwgQSubpropertyDepth > 0) {
					$pt = $this->getPropertyTable($description->getProperty()->getDBkey(), $db);
					$from = '`' . $pt . '`, ' . $from;
					$where = "$pt.title=" . $table . '.' . $pcolumn;
				} else {
					$where .= $table . '.' . $pcolumn . '=' .
					          $db->addQuotes($description->getProperty()->getDBkey());
				}
				if ($sub) {
					$nexttables = array();
					$nexttables['p' . $tablename] = $table; // keep only current table for reference
					$this->createSQLQuery($description->getDescription(), $from, $subwhere, $db, $nexttables, $nary_pos);
					if ($sortfield) {
						$this->m_sortfields[$sortkey] = "$table.$sortfield";
					}
					if ( $subwhere != '') {
						$where .= ' AND (' . $subwhere . ')';
					}
				}
			}
		}

	}

	/**
	 * Make sure the table of the given name has the given fields, provided
	 * as an array with entries fieldname => typeparams. typeparams should be
	 * in a normalised form and order to match to existing values.
	 *
	 * The function returns an array that includes all columns that have been
	 * changed. For each such column, the array contains an entry
	 * columnname => action, where action is one of 'up', 'new', or 'del'
	 * If the table was already fine or was created completely anew, an empty
	 * array is returned (assuming that both cases require no action).
	 *
	 * NOTE: the function partly ignores the order in which fields are set up.
	 * Only if the type of some field changes will its order be adjusted explicitly.
	 */
	protected function setupTable($table, $fields, $db, $verbose) {
		global $wgDBname;
		$this->reportProgress("Setting up table $table ...\n",$verbose);
		if ($db->tableExists($table) === false) { // create new table
			$sql = 'CREATE TABLE `' . $wgDBname . '`.' . $table . ' (';
			$first = true;
			foreach ($fields as $name => $type) {
				if ($first) {
					$first = false;
				} else {
					$sql .= ',';
				}
				$sql .= $name . '  ' . $type;
			}
			$sql .= ') TYPE=innodb';
			$db->query( $sql, 'SMWSQLStore::setupTable' );
			$this->reportProgress("   ... new table created\n",$verbose);
			return array();
		} else { // check table signature
			$this->reportProgress("   ... table exists already, checking structure ...\n",$verbose);
			$res = $db->query( 'DESCRIBE ' . $table, 'SMWSQLStore::setupTable' );
			$curfields = array();
			$result = array();
			while ($row = $db->fetchObject($res)) {
				$type = strtoupper($row->Type);
				if (substr($type,0,8) == 'VARCHAR(') {
					$type .= ' binary'; // just assume this to be the case for VARCHAR, avoid collation checks
				}
				if ($row->Null != 'YES') {
					$type .= ' NOT NULL';
				}
				$curfields[$row->Field] = $type;
			}
			$position = 'FIRST';
			foreach ($fields as $name => $type) {
				if ( !array_key_exists($name,$curfields) ) {
					$this->reportProgress("   ... creating column $name ... ",$verbose);
					$db->query("ALTER TABLE $table ADD `$name` $type $position", 'SMWSQLStore::setupTable');
					$result[$name] = 'new';
					$this->reportProgress("done \n",$verbose);
				} elseif ($curfields[$name] != $type) {
					$this->reportProgress("   ... changing type of column $name from '$curfields[$name]' to '$type' ... ",$verbose);
					$db->query("ALTER TABLE $table CHANGE `$name` `$name` $type $position", 'SMWSQLStore::setupTable');
					$result[$name] = 'up';
					$curfields[$name] = false;
					$this->reportProgress("done.\n",$verbose);
				} else {
					$this->reportProgress("   ... column $name is fine\n",$verbose);
					$curfields[$name] = false;
				}
				$position = "AFTER $name";
			}
			foreach ($curfields as $name => $value) {
				if ($value !== false) { // not encountered yet --> delete
					$this->reportProgress("   ... deleting obsolete column $name ... ",$verbose);
					$db->query("ALTER TABLE $table DROP COLUMN `$name`", 'SMWSQLStore::setupTable');
					$result[$name] = 'del';
					$this->reportProgress("done.\n",$verbose);
				}
			}
			$this->reportProgress("   ... table $table set up successfully.\n",$verbose);
			return $result;
		}
	}

	/**
	 * Make sure that each of the column descriptions in the given array is indexed by *one* index
	 * in the given DB table.
	 */
	protected function setupIndex($table, $columns, $db) {
		$table = $db->tableName($table);
		$res = $db->query( 'SHOW INDEX FROM ' . $table , 'SMW::SetupIndex');
		if ( !$res ) {
			return false;
		}
		$indexes = array();
		while ( $row = $db->fetchObject( $res ) ) {
			if (!array_key_exists($row->Key_name, $indexes)) {
				$indexes[$row->Key_name] = array();
			}
			$indexes[$row->Key_name][$row->Seq_in_index] = $row->Column_name;
		}
		foreach ($indexes as $key => $index) { // clean up existing indexes
			$id = array_search(implode(',', $index), $columns );
			if ( $id !== false ) {
				$columns[$id] = false;
			} else { // duplicate or unrequired index
				$db->query( 'DROP INDEX ' . $key . ' ON ' . $table, 'SMW::SetupIndex');
			}
		}

		foreach ($columns as $column) { // add remaining indexes
			if ($column != false) {
				$db->query( "ALTER TABLE $table ADD INDEX ( $column )", 'SMW::SetupIndex');
			}
		}
		return true;
	}

	/**
	 * Print some output to indicate progress. The output message is given by
	 * $msg, while $verbose indicates whether or not output is desired at all.
	 */
	protected function reportProgress($msg, $verbose) {
		if (!$verbose) {
			return;
		}
		if (ob_get_level() == 0) { // be sure to have some buffer, otherwise some PHPs complain
			ob_start();
		}
		print $msg;
		ob_flush();
		flush();
	}

}


