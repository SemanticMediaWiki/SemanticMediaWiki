<?php
/**
 * SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgIP/includes/SMW_Datatype.php" );
require_once( "$smwgIP/includes/SMW_DataValue.php" );

/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 */
class SMWSQLStore extends SMWStore {

	/**
	 * The (normalised) name of the property by which results during query
	 * processing should be ordered, if any. False otherwise (default from
	 * SMWQuery). Needed during query processing (where this key is searched
	 * while building the query conditions).
	 */
	protected $m_sortkey;
	/**
	 * The database field name by which results during query processing should 
	 * be ordered, if any. False if no $m_sortkey was specified or if the key
	 * did not match any condition.
	 */
	protected $m_sortfield;
	/**
	 * Global counter to prevent clashes between table aliases.
	 */
	static protected $m_tablenum = 0;
	/**
	 * Array of names of virtual tables that hold the upper closure of certain 
	 * categories wrt. hierarchy.
	 */
	static protected $m_categorytables = array();


///// Reading methods /////

	function getSpecialValues(Title $subject, $specialprop, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE ); // TODO: Is '=&' needed in PHP5?

		// TODO: this method currently supports no ordering or boundary. This is probably best anyway ...

		$result = array();
		if ($specialprop === SMW_SP_HAS_CATEGORY) { // category membership
			$sql = 'cl_from=' . $db->addQuotes($subject->getArticleID());
			$res = $db->select( $db->tableName('categorylinks'),
								'DISTINCT cl_to',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite result as array
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::newFromText($row->cl_to, NS_CATEGORY);
				}
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$sql = 'rd_from=' . $db->addQuotes($subject->getArticleID());
			$res = $db->select( $db->tableName('redirect'),
								'rd_namespace,rd_title',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
								
			// reqrite results as array
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::makeTitle($row->rd_namespace, $row->rd_title);
				}
			}
			$db->freeResult($res);
		} else { // "normal" special property
			$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
				'AND property_id=' . $db->addQuotes($specialprop);
			$res = $db->select( $db->tableName('smw_specialprops'),
								'value_string',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite result as array
			switch ($specialprop) {
			case SMW_SP_HAS_TYPE: // type values
				while($row = $db->fetchObject($res)) {
					$result[] = SMWDataValueFactory::newSpecialValue($specialprop,$row->value_string);
				}
			break;
			default: // plain strings
			///TODO: this should also be handled by the appropriate special handlers
				while($row = $db->fetchObject($res)) {
					$result[] = $row->value_string;
				}
			}
			$db->freeResult($res);
		}
		return $result;
	}

	function getSpecialSubjects($specialprop, $value, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		
		$result = array();
		
		if ($specialprop === SMW_SP_HAS_CATEGORY) { // category membership
			if ( !($value instanceof Title) || ($value->getNamespace() != NS_CATEGORY) ) {
				return array();
			}
			$sql = 'cl_to=' . $db->addQuotes($value->getDBKey());
			$res = $db->select( $db->tableName('categorylinks'),
								'DISTINCT cl_from',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
								
			// rewrite result as array
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::newFromID($row->cl_from);
				}
			}
			$db->freeResult($res);
			
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
		
			$sql = 'rd_title=' . $db->addQuotes($value->getDBKey())
					. ' AND rd_namespace=' . $db->addQuotes($value->getNamespace());
			$res = $db->select( $db->tableName('redirect'),
								'rd_from',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
								
			// reqrite results as array
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::newFromID($row->rd_from);
				}
			}
			$db->freeResult($res);
		
		} else {
		
			if ($value instanceof SMWDataValue) {
				if ($value->getXSDValue() !== false) { // filters out error-values etc.
					$stringvalue = $value->getXSDValue();
				} else {
					return array();
				}
			} elseif ($value instanceof Title) {
				if ( $specialprop == SMW_SP_HAS_TYPE ) { // special handling, TODO: change this to use type ids
					$stringvalue = $value->getText();
				} else {
					$stringvalue = $value->getPrefixedText();
				}
			} else {
				$stringvalue = $value;
			}

			$sql = 'property_id=' . $db->addQuotes($specialprop) .
			       ' AND value_string=' . $db->addQuotes($stringvalue) .
		    	   $this->getSQLConditions($requestoptions,'subject_title','subject_title');

			$res = $db->select( $db->tableName('smw_specialprops'),
			                    'DISTINCT subject_id',
			                    $sql, 'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions,'subject_title') );

			// rewrite result as array
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::newFromID($row->subject_id);
				}
			}
			$db->freeResult($res);
		}

		return $result;
	}


	function getAttributeValues(Title $subject, Title $attribute, $requestoptions = NULL, $outputformat = '') {
		$db =& wfGetDB( DB_SLAVE );
		$result = array();

		$id = SMWDataValueFactory::getAttributeObjectTypeID($attribute);
		switch ($id) {
			case 'text': // long text attribute
				$res = $db->select( $db->tableName('smw_longstrings'),
									'value_blob',
									'subject_id=' . $db->addQuotes($subject->getArticleID()) .
									' AND attribute_title=' . $db->addQuotes($attribute->getDBkey()),
									'SMW::getAttributeValues', $this->getSQLOptions($requestoptions) );
				if($db->numRows( $res ) > 0) {
					while($row = $db->fetchObject($res)) {
						$dv = SMWDataValueFactory::newAttributeObjectValue($attribute);
						$dv->setOutputFormat($outputformat);
						$dv->setXSDValue($row->value_blob, '');
						$result[] = $dv;
					}
				}
				$db->freeResult($res);
			break;
			default: // all others
				if ( ($requestoptions !== NULL) && ($requestoptions->boundary !== NULL) &&
				     ($requestoptions->boundary->isNumeric()) ) {
					$value_column = 'value_num';
				} else {
					$value_column = 'value_xsd';
				}
				$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
					' AND attribute_title=' . $db->addQuotes($attribute->getDBkey()) .
					$this->getSQLConditions($requestoptions,$value_column,'value_xsd');
				$res = $db->select( $db->tableName('smw_attributes'),
									'value_unit, value_xsd',
									$sql, 'SMW::getAttributeValues', $this->getSQLOptions($requestoptions,$value_column) );
				if($db->numRows( $res ) > 0) {
					while($row = $db->fetchObject($res)) {
						$dv = SMWDataValueFactory::newAttributeObjectValue($attribute);
						$dv->setOutputFormat($outputformat);
						$dv->setXSDValue($row->value_xsd, $row->value_unit);
						$result[] = $dv;
					}
				}
				$db->freeResult($res);
		}
		return $result;
	}

	function getAttributeSubjects(Title $attribute, SMWDataValue $value, $requestoptions = NULL) {
		if ( !$value->isValid() ) {
			return array();
		}

		$db =& wfGetDB( DB_SLAVE );
		$sql = 'value_xsd=' . $db->addQuotes($value->getXSDValue()) .
		       ' AND value_unit=' . $db->addQuotes($value->getUnit()) .
		       ' AND attribute_title=' . $db->addQuotes($attribute->getDBKey()) .
		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

		$result = array();
		$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getAttributeSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->subject_id);
			}
		}
		$db->freeResult($res);
		// long strings not supported for this operation

		return $result;
	}

	function getAllAttributeSubjects(Title $attribute, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'attribute_title=' . $db->addQuotes($attribute->getDBkey()) .
		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

		$result = array();
		$id = SMWDataValueFactory::getAttributeObjectTypeID($attribute);
		switch ($id) {
			case 'text':
				$res = $db->select( $db->tableName('smw_longstrings'),
				                    'DISTINCT subject_id',
				                    $sql, 'SMW::getAllAttributeSubjects', 
				                    $this->getSQLOptions($requestoptions,'subject_title') );
				if($db->numRows( $res ) > 0) {
					while($row = $db->fetchObject($res)) {
						$result[] = Title::newFromId($row->subject_id);
					}
				}
				$db->freeResult($res);
			break;
			default:
				$res = $db->select( $db->tableName('smw_attributes'),
				                    'DISTINCT subject_id',
				                    $sql, 'SMW::getAllAttributeSubjects', 
				                    $this->getSQLOptions($requestoptions,'subject_title') );
				if($db->numRows( $res ) > 0) {
					while($row = $db->fetchObject($res)) {
						$result[] = Title::newFromId($row->subject_id);
					}
				}
				$db->freeResult($res);
		}
		return $result;
	}

	function getAttributes(Title $subject, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) . $this->getSQLConditions($requestoptions,'attribute_title','attribute_title');

		$result = array();
		$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getAttributes', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);
		$res = $db->select( $db->tableName('smw_longstrings'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getAttributes', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getRelationObjects(Title $subject, Title $relation, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey()) .
		       $this->getSQLConditions($requestoptions,'object_title','object_title');

		$res = $db->select( $db->tableName('smw_relations'),
		                    'object_title, object_namespace',
		                    $sql, 'SMW::getRelationObjects', $this->getSQLOptions($requestoptions,'object_title') );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->object_title, $row->object_namespace);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getRelationSubjects(Title $relation, Title $object, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) .
		       ' AND object_title=' . $db->addQuotes($object->getDBKey()) .
		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey()) .
		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

		$res = $db->select( $db->tableName('smw_relations'),
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getRelationSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->subject_id);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getAllRelationSubjects(Title $relation, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'relation_title=' . $db->addQuotes($relation->getDBkey()) .
		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

		$res = $db->select( $db->tableName('smw_relations'),
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getAllRelationSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromId($row->subject_id);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getOutRelations(Title $subject, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       $this->getSQLConditions($requestoptions,'relation_title','relation_title');

		$res = $db->select( $db->tableName('smw_relations'),
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getOutRelations', $this->getSQLOptions($requestoptions,'relation_title') );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getInRelations(Title $object, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) .
		       ' AND object_title=' . $db->addQuotes($object->getDBKey()) .
		       $this->getSQLConditions($requestoptions,'relation_title','relation_title');

		$res = $db->select( $db->tableName('smw_relations'),
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getInRelations', $this->getSQLOptions($requestoptions,'relation_title') );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			}
		}
		$db->freeResult($res);

		return $result;
	}

///// Writing methods /////

	function deleteSubject(Title $subject) {
		$db =& wfGetDB( DB_MASTER );
		$db->delete($db->tableName('smw_relations'),
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Relations');
		$db->delete($db->tableName('smw_attributes'),
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Attributes');
		$db->delete($db->tableName('smw_longstrings'),
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Longstrings');
		$db->delete($db->tableName('smw_specialprops'),
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Specialprops');
	}

	function updateData(SMWSemanticData $data) {
		$db =& wfGetDB( DB_MASTER );
		$subject = $data->getSubject();
		$this->deleteSubject($subject);

		// do bulk updates:
		$up_relations = array();
		$up_attributes = array();
		$up_longstrings = array();
		$up_specials = array();

		// relations
		foreach($data->getRelations() as $relation) {
			foreach($data->getRelationObjects($relation) as $object) {
				$up_relations[] =
				     array( 'subject_id' => $subject->getArticleID(),
				            'subject_namespace' => $subject->getNamespace(),
				            'subject_title' => $subject->getDBkey(),
				            'relation_title' => $relation->getDBkey(),
				            'object_namespace' => $object->getNamespace(),
				            'object_title' => $object->getDBkey() );
			}
		}

		//attributes
		foreach($data->getAttributes() as $attribute) {
			$attributeValueArray = $data->getAttributeValues($attribute);
			foreach($attributeValueArray as $value) {
				if ($value->isValid()) {
					if ($value->getTypeID() !== 'text') {
						$up_attributes[] =
						      array( 'subject_id' => $subject->getArticleID(),
						             'subject_namespace' => $subject->getNamespace(),
						             'subject_title' => $subject->getDBkey(),
						             'attribute_title' => $attribute->getDBkey(),
						             'value_unit' => $value->getUnit(),
						             'value_datatype' => $value->getTypeID(),
						             'value_xsd' => $value->getXSDValue(),
						             'value_num' => $value->getNumericValue() );
					} else {
						$up_longstrings[] =
						      array( 'subject_id' => $subject->getArticleID(),
						             'subject_namespace' => $subject->getNamespace(),
						             'subject_title' => $subject->getDBkey(),
						             'attribute_title' => $attribute->getDBkey(),
						             'value_blob' => $value->getXSDValue() );
					}
				}
			}
		}

		//special properties
		foreach ($data->getSpecialProperties() as $special) {
			if ($special == SMW_SP_IMPORTED_FROM) { // don't store this, just used for display; TODO: filtering it here is bad
				continue;
			}
			$valueArray = $data->getSpecialValues($special);
			foreach($valueArray as $value) {
				if ($value instanceof SMWDataValue) {
					if ($value->getXSDValue() !== false) { // filters out error-values etc.
						$stringvalue = $value->getXSDValue();
					}
				} elseif ($value instanceof Title) {
					if ( $special == SMW_SP_HAS_TYPE ) { // special handling, TODO: change this to use type ids
						$stringvalue = $value->getText();
					} else {
						$stringvalue = $value->getPrefixedText();
					}
				} else {
					$stringvalue = $value;
				}
				$up_specials[] =
				      array('subject_id' => $subject->getArticleID(),
				            'subject_namespace' => $subject->getNamespace(),
				            'subject_title' => $subject->getDBkey(),
				            'property_id' => $special,
				            'value_string' => $stringvalue);
			}
		}

		// write to DB:
		if (count($up_relations) > 0) {
			$db->insert( $db->tableName('smw_relations'), $up_relations, 'SMW::updateRelData');
		}
		if (count($up_attributes) > 0) {
			$db->insert( $db->tableName('smw_attributes'), $up_attributes, 'SMW::updateAttData');
		}
		if (count($up_longstrings) > 0) {
			$db->insert( $db->tableName('smw_longstrings'), $up_longstrings, 'SMW::updateLongData');
		}
		if (count($up_specials) > 0) {
			$db->insert( $db->tableName('smw_specialprops'), $up_specials, 'SMW::updateSpecData');
		}
	}

	function changeTitle(Title $oldtitle, Title $newtitle, $keepid = true) {
		$db =& wfGetDB( DB_MASTER );

		$cond_array = array( 'subject_title' => $oldtitle->getDBkey(),
		                     'subject_namespace' => $oldtitle->getNamespace() );
		$val_array  = array( 'subject_title' => $newtitle->getDBkey(),
		                     'subject_namespace' => $newtitle->getNamespace() );

		// don't do this by default, since the ids you get when moving articles
		// are not the ones from the old article and the new one (in reality, the
		// $old_title refers to the newly generated redirect article, which does
		// not have the old id that was stored in the database):
		if (!$keepid) {
			$old_id = $old_title->getArticleID();
			$new_id = $new_title->getArticleID();
			if ($old_id != 0) {
				$cond_array['subject_id'] = $old_id;
			}
			if ($new_id != 0) {
				$val_array['subject_id'] = $new_id;
			}
		}

		$db->update($db->tableName('smw_relations'), $val_array, $cond_array, 'SMW::changeTitle');
		$db->update($db->tableName('smw_attributes'), $val_array, $cond_array, 'SMW::changeTitle');
		$db->update($db->tableName('smw_longstrings'), $val_array, $cond_array, 'SMW::changeTitle');
		$db->update($db->tableName('smw_specialprops'), $val_array, $cond_array, 'SMW::changeTitle');
	}

///// Query answering /////

	/**
	 * The SQL store's implementation of query answering.
	 *
	 * TODO: decide who respects which global query settings: the query parser or the query execution?
	 * Probably the query parser (e.g. it can distinguish subqueries from other nested constructs that
	 * are not "subqueries" from a user perspective, it also has a good insight in the query structure for
	 * applying structural limits)
	 * TODO: we now have sorting even for subquery conditions. Does this work? Is it slow/problematic?
	 * NOTE: we do not support category wildcards, as they have no useful semantics in OWL/RDFS/LP/whatever
	 */
	function getQueryResult(SMWQuery $query) {
		global $smwgIQSortingEnabled;

		$db =& wfGetDB( DB_SLAVE );
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deeper levels

		// Build main query
		$this->m_sortkey = $query->sortkey;
		$this->m_sortfield = false;

		$pagetable = $db->tableName('page');
		$from = $pagetable;
		$where = '';
		$curtables = array('PAGE' => $from);
		$this->createSQLQuery($query->getDescription(), $from, $where, $db, $curtables);

		// Prepare SQL options
		$sql_options = array();
		if ($query->limit >= 0) {
			$sql_options['LIMIT'] = $query->limit + 1;
		}
		$sql_options['OFFSET'] = $query->offset;
		if ( $smwgIQSortingEnabled ) {
			$order = $query->ascending ? 'ASC' : 'DESC';
			if ( ($this->m_sortfield == false) && ($this->m_sortkey == false) ) {
				$sql_options['ORDER BY'] = "$pagetable.page_title $order "; // default
			} elseif ($this->m_sortfield != false) {
				$sql_options['ORDER BY'] = $this->m_sortfield . " $order ";
			} // else: sortkey given but not found: do not sort
		}

		// Execute query and format result as array
		if ($query->querymode == SMWQuery::MODE_COUNT) {
			$res = $db->select($from,
			       "COUNT(DISTINCT $pagetable.page_id) AS count",
			        $where,
			        'SMW::getQueryResult',
			        $sql_options );
			$row = $db->fetchObject($res);
			return $row->count;
			// TODO: report query errors?
		} elseif ($query->querymode == SMWQuery::MODE_DEBUG) { /// TODO: internationalise
			list( $startOpts, $useIndex, $tailOpts ) = $db->makeSelectOptions( $sql_options );
			$result = '<div style="border: 1px dotted black; background: #A1FB00; padding: 20px; ">' .
			          '<b>Generated Wiki-Query</b><br />' .
			          htmlspecialchars($query->getDescription()->getQueryString()) . '<br />' .
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
			$result .= '</div>';
			/// TODO: report query errors!
			return $result;
		} // else: continue

		$res = $db->select($from,
		       "DISTINCT $pagetable.page_title as title, $pagetable.page_namespace as namespace",
		        $where,
		        'SMW::getQueryResult',
		        $sql_options );

		$qr = array();
		$count = 0;
		while ( ( ($count<$query->limit) || ($query->limit < 0) ) && ($row = $db->fetchObject($res)) ) {
			$count++;
			$qr[] = Title::newFromText($row->title, $row->namespace);
		}
		if ($db->fetchObject($res)) {
			$count++;
		}
		$db->freeResult($res);

		// Create result by executing print statements for everything that was fetched
		///TODO: use limit (and offset?) values for printouts?
		$result = new SMWQueryResult($prs, $query, ( ($count > $query->limit) && ($query->limit >= 0) ) );
		foreach ($qr as $qt) {
			$row = array();
			foreach ($prs as $pr) {
				switch ($pr->getMode()) {
					case SMW_PRINT_THIS:
						$row[] = new SMWResultArray(array($qt), $pr);
						break;
					case SMW_PRINT_RELS:
						$row[] = new SMWResultArray($this->getRelationObjects($qt,$pr->getTitle()), $pr);
						break;
					case SMW_PRINT_CATS:
						$row[] = new SMWResultArray($this->getSpecialValues($qt,SMW_SP_HAS_CATEGORY), $pr);
						break;
					case SMW_PRINT_ATTS:
						$row[] = new SMWResultArray($this->getAttributeValues($qt,$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
						break;
				}
			}
			$result->addRow($row);
		}

		return $result;
	}

///// Setup store /////

	function setup() {
		global $wgDBname;

		$fname = 'SMW::setupDatabase';
		$db =& wfGetDB( DB_MASTER );

		extract( $db->tableNames('smw_relations','smw_attributes','smw_longstrings','smw_specialprops') );

		// create relation table
		if ($db->tableExists('smw_relations') === false) {
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_relations . '
					( subject_id         INT(8) UNSIGNED NOT NULL,
					subject_namespace  INT(11) NOT NULL,
					subject_title      VARCHAR(255) NOT NULL,
					relation_title     VARCHAR(255) NOT NULL,
					object_namespace   INT(11) NOT NULL,
					object_title       VARCHAR(255) NOT NULL
					) TYPE=innodb';
			$res = $db->query( $sql, $fname );
		}

		$this->setupIndex($smw_relations, array('subject_id','relation_title','object_title,object_namespace'), $db);

		// create attribute table
		if ($db->tableExists('smw_attributes') === false) {
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_attributes . '
					( subject_id INT(8) UNSIGNED NOT NULL,
					subject_namespace  INT(11) NOT NULL,
					subject_title      VARCHAR(255) NOT NULL,
					attribute_title    VARCHAR(255) NOT NULL,
					value_unit         VARCHAR(63),
					value_datatype     VARCHAR(31) NOT NULL,
					value_xsd          VARCHAR(255) NOT NULL,
					value_num          DOUBLE
					) TYPE=innodb';  /// TODO: remove value_datatype column completely
			$res = $db->query( $sql, $fname );
		}

		$this->setupIndex($smw_attributes, array('subject_id','attribute_title','value_num','value_xsd'), $db);

		// create table for long string attributes
		if ($db->tableExists('smw_longstrings') === false) {
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_longstrings . '
					( subject_id INT(8) UNSIGNED NOT NULL,
					subject_namespace  INT(11) NOT NULL,
					subject_title      VARCHAR(255) NOT NULL,
					attribute_title    VARCHAR(255) NOT NULL,
					value_blob         MEDIUMBLOB
					) TYPE=innodb';
			$res = $db->query( $sql, $fname );
		}

		$this->setupIndex($smw_longstrings, array('subject_id','attribute_title'), $db);

		// create table for special properties
		if ($db->tableExists('smw_specialprops') === false) {
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_specialprops . '
					( subject_id       INT(8) UNSIGNED NOT NULL,
					subject_namespace  INT(11) NOT NULL,
					subject_title      VARCHAR(255) NOT NULL,
					property_id        SMALLINT NOT NULL,
					value_string       VARCHAR(255) NOT NULL
					) TYPE=innodb'; /// TODO: remove subject_namespace and subject_title columns completely
			$res = $db->query( $sql, $fname );
		}

		$this->setupIndex($smw_specialprops, array('subject_id', 'property_id'), $db);

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
			if ($requestoptions->limit >= 0) {
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
					$string = str_replace(array('_', ' '), array('\_', '\_'), $strcond->string);
					switch ($strcond->condition) {
						case SMW_STRCOND_PRE:
							$string .= '%';
							break;
						case SMW_STRCOND_POST:
							$string = '%' . $string;
							break;
						case SMW_STRCOND_MID:
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
			$result = Title::newFromText($row->rd_title, $row->rd_namespace);
			if ($result !== NULL) {
				return $result;
			}
		}
		return $page;
	}

	/**
	 * Make a (temporary) table that contains the upper closure of the given category
	 * wrt. the category table.
	 */
	protected function getCategoryTable($catname, &$db) {
		global $wgDBname, $smwgIQSubcategoryInclusions;

		$tablename = 'cats' . SMWSQLStore::$m_tablenum++;
		$db->query( 'CREATE TEMPORARY TABLE ' . $wgDBname . '.' . $tablename .
		            '( cat_name VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		if (array_key_exists($catname, SMWSQLStore::$m_categorytables)) { // just copy known result
			$db->insertSelect($tablename, array(SMWSQLStore::$m_categorytables[$catname]), 
			                  array('cat_name' => 'cat_name'),'*', 'SMW::getCategoryTable');
			return $tablename;
		}

		// Create multiple temporary tables for recursive computation
		$db->query( 'CREATE TEMPORARY TABLE smw_newcats
		             ( cat_name VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		$db->query( 'CREATE TEMPORARY TABLE smw_rescats
		             ( cat_name VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );

		$pagetable = $db->tableName('page');
		$cltable = $db->tableName('categorylinks');
		$db->insert($tablename, array('cat_name' => $catname), 'SMW::getCategoryTable');
		$db->insert('smw_newcats', array('cat_name' => $catname), 'SMW::getCategoryTable');
		$tmpnew='smw_newcats';
		$tmpres='smw_rescats';

		/// TODO: avoid duplicate results?
		for ($i=0; $i<$smwgIQSubcategoryInclusions; $i++) {
			$db->insertSelect($tmpres,
			                  array($cltable,$pagetable,$tmpnew),
			                  array('cat_name' => 'page_title'),
			                  array(
			                  "$cltable.cl_to=$tmpnew.cat_name AND
			                   $pagetable.page_namespace=" . NS_CATEGORY . " AND 
			                   $pagetable.page_id=$cltable.cl_from"), 'SMW::getCategoryTable');
			if ($db->affectedRows() == 0) { // no change, exit loop
				continue;
			}
			$db->insertSelect($tablename, array($tmpres), array('cat_name' => 'cat_name'),
			                  '*', 'SMW::getCategoryTable');
			$db->query('TRUNCATE TABLE ' . $tmpnew, 'SMW::getCategoryTable'); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		SMWSQLStore::$m_categorytables[$catname] = $tablename;
		$db->query('DROP TABLE smw_newcats', 'SMW::getCategoryTable');
		$db->query('DROP TABLE smw_rescats', 'SMW::getCategoryTable');
		return $tablename;
	}

	/**
	 * Add the table $tablename to the $from condition via an inner join,
	 * using the tables that are already available in $curtables (and extending 
	 * $curtables with the new table). Return true if successful or false if it
	 * wasn't possible to make a suitable inner join.
	 */
	protected function addInnerJoin($tablename, &$from, &$db, &$curtables) {
		global $smwgIQRedirectNormalization;
		if (array_key_exists($tablename, $curtables)) { // table already present
			return true;
		}
		if ($tablename == 'PAGE') {
			if (array_key_exists('PREVREL', $curtables)) { // PREVREL cannot be added if not present
				$curtables['PAGE'] = 'p' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('page') . ' AS ' . $curtables['PAGE'] . ' ON (' .
				           $curtables['PAGE'] . '.page_title=' . $curtables['PREVREL'] . '.object_title AND ' .
				           $curtables['PAGE'] . '.page_namespace=' . $curtables['PREVREL'] . '.object_namespace)';
				return true;
			}
		} elseif ($tablename == 'CATS') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['CATS'] = 'cl' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['CATS'] . '.cl_from=' . $curtables['PAGE'] . '.page_id';
// 				if ($smwgIQRedirectNormalization) {
// 					$this->addInnerJoin('REDIPAGE', $from, $db, $curtables);
// 					$cond = '((' . $cond . ') OR (' .
// 					  $curtables['PAGE'] . '.page_id=' . $curtables['REDIRECT'] . '.rd_from AND ' .
// 					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['REDIPAGE'] . '.page_title AND ' .
// 					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['REDIPAGE'] . '.page_namespace AND ' .
// 					  $curtables['REDIPAGE'] . '.page_id=' . $curtables['CATS'] . '.cl_from))';
// 				}
				$from .= ' INNER JOIN ' . $db->tableName('categorylinks') . ' AS ' . $curtables['CATS'] . ' ON ' . $cond;
				return true;
			}
		} elseif ($tablename == 'RELS') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['RELS'] = 'rel' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['RELS'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
// 				if ($smwgIQRedirectNormalization) {
// 					$this->addInnerJoin('REDIRECT', $from, $db, $curtables);
// 					$cond = '((' . $cond . ') OR (' .
// 					  $curtables['PAGE'] . '.page_id=' . $curtables['REDIRECT'] . '.rd_from AND ' .
// 					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['RELS'] . '.subject_title AND ' .
// 					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['RELS'] . '.subject_namespace))';
// 				}
				$from .= ' INNER JOIN ' . $db->tableName('smw_relations') . ' AS ' . $curtables['RELS'] . ' ON ' . $cond;
				return true;
			}
		} elseif ($tablename == 'ATTS') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['ATTS'] = 'att' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_attributes') . ' AS ' . $curtables['ATTS'] . ' ON ' . $curtables['ATTS'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
				return true;
			}
		} elseif ($tablename == 'TEXT') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['TEXT'] = 'txt' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_longstrings') . ' AS ' . $curtables['TEXT'] . ' ON ' . $curtables['TEXT'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
				return true;
			}
		} elseif ($tablename == 'REDIRECT') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['REDIRECT'] = 'rd' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('redirect') . ' AS ' . $curtables['REDIRECT'];
				return true;
			}
		} elseif ($tablename == 'REDIPAGE') { // add another copy of page for getting ids of redirect targets
			if ($this->addInnerJoin('REDIRECT', $from, $db, $curtables)) { 
				$curtables['REDIPAGE'] = 'rp' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('page') . ' AS ' . $curtables['REDIPAGE'];
				return true;
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
	 * by verifying conditions and the sorting conditions thus operate on the values that
	 * satisfy the given conditions. This may have side effects in cases where one porperty
	 * that shall be sorted has multiple values. If no condition other than existence applies
	 * to such a property, the value that is relevant for sorting is not really dertermined and
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
	 * @param $sort True if the subcondition should be used for sorting. This is only meaningful for queries that are below some relation or attribute statement.
	 *
	 * @TODO: The extra table for long string attributes should be supported for checking existence of such an attribute (but not for comparing values, which is not needed for blobs).
	 * @TODO: Maybe there need to be optimisations in certain cases (atomic implementation for common nestings of descriptions?)
	 */
	protected function createSQLQuery(SMWDescription $description, &$from, &$where, &$db, &$curtables, $sort = false) {
		$subwhere = '';
		if ($description instanceof SMWThingDescription) {
			// nothin to check
		} elseif ($description instanceof SMWClassDescription) {
			if ($this->addInnerJoin('CATS', $from, $db, $curtables)) {
				global $smwgIQSubcategoryInclusions;
				if ($smwgIQSubcategoryInclusions > 0) {
					$ct = $this->getCategoryTable($description->getCategory()->getDBKey(), $db);
					$from .= " INNER JOIN $ct ON $ct.cat_name=" . $curtables['CATS'] . '.cl_to';
				} else {
					$where .=  $curtables['CATS'] . '.cl_to=' . $db->addQuotes($description->getCategory()->getDBKey());
				}
			}
		} elseif ($description instanceof SMWNamespaceDescription) {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) {
				$where .=  $curtables['PAGE'] . '.page_namespace=' . $db->addQuotes($description->getNamespace());
			}
		} elseif ($description instanceof SMWNominalDescription) {
			global $smwgIQRedirectNormalization;
			if ($smwgIQRedirectNormalization) {
				$page = $this->getRedirectTarget($description->getIndividual(), $db);
			} else {
				$page = $description->getIndividual();
			}
			if (array_key_exists('PREVREL', $curtables)) {
				$cond = $curtables['PREVREL'] . '.object_title=' .
				        $db->addQuotes($page->getDBKey()) . ' AND ' .
				        $curtables['PREVREL'] . '.object_namespace=' .
				        $page->getNamespace();
				if ( $smwgIQRedirectNormalization && ($this->addInnerJoin('REDIRECT', $from, $db, $curtables)) ) {
					$cond = '(' . $cond . ') OR (' . 
					        $curtables['REDIRECT'] . '.rd_from=' .
					        $curtables['PAGE'] . '.page_id AND ' .
					        $curtables['REDIRECT'] . '.rd_title=' .
					        $db->addQuotes($page->getDBKey()) . ' AND ' .
					        $curtables['REDIRECT'] . '.rd_namespace=' .
					        $page->getNamespace() . ')';
				}
				$where .= $cond;
			} elseif ($this->addInnerJoin('PAGE', $from, $db, $curtables)) {
				$where .= $curtables['PAGE'] . '.page_title=' .
				          $db->addQuotes($page->getDBKey()) . ' AND ' .
				          $curtables['PAGE'] . '.page_namespace=' .
				          $page->getNamespace();
			}
		} elseif ($description instanceof SMWValueDescription) {
			switch ($description->getDatavalue()->getTypeID()) {
				case 'text': // actually this should not happen; we cannot do anything here 
				break;
				default:
					if ( $this->addInnerJoin('ATTS', $from, $db, $curtables) ) {
						switch ($description->getComparator()) {
							case SMW_CMP_EQ: $op = '='; break;
							case SMW_CMP_LEQ: $op = '<='; break;
							case SMW_CMP_GEQ: $op = '>='; break;
							case SMW_CMP_NEQ: $op = '!='; break;
							case SMW_CMP_ANY: default: $op = NULL; break;
						}
						if ($op !== NULL) {
							if ($description->getDatavalue()->isNumeric()) {
								$valuefield = 'value_num';
								$value = $description->getDatavalue()->getNumericValue();
							} else {
								$valuefield = 'value_xsd';
								$value = $description->getDatavalue()->getXSDValue();
							}
							///TODO: implement check for unit
							$where .= $curtables['ATTS'] . '.' .  $valuefield . $op . $db->addQuotes($value);
							if ($sort != '') {
								$this->m_sortfield = $curtables['ATTS'] . '.' . $valuefield;
							}
						}
					}
			}
		} elseif ($description instanceof SMWConjunction) {
			foreach ($description->getDescriptions() as $subdesc) {
				/// TODO: this is not optimal -- we drop more table aliases than needed, but its hard to find out what is feasible in recursive calls ...
				$nexttables = array();
				// pull in page to prevent every child description pulling it seperately!
				/// TODO: willl be obsolete when PREVREL provides page indices
				if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) {
					$nexttables['PAGE'] = $curtables['PAGE'];
				}
				if (array_key_exists('PREVREL',$curtables)) {
					$nexttables['PREVREL'] = $curtables['PREVREL'];
				}
				$this->createSQLQuery($subdesc, $from, $subwhere, $db, $nexttables);
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
				$this->createSQLQuery($subdesc, $from, $subwhere, $db, $curtables);
				if ($subwhere != '') {
					if ($where != '') {
						$where .= ' OR ';
					}
					$where .= '(' . $subwhere . ')';
					$subwhere = '';
				}
			}
		} elseif ($description instanceof SMWSomeRelation) {
			if ($this->addInnerJoin('RELS', $from, $db, $curtables)) {
				$where .= $curtables['RELS'] . '.relation_title=' . 
				          $db->addQuotes($description->getRelation()->getDBKey());
				$nexttables = array( 'PREVREL' => $curtables['RELS'] );
				$this->createSQLQuery($description->getDescription(), $from, $subwhere, $db, $nexttables, ($this->m_sortkey == $description->getRelation()->getDBKey()) );
				if ( $subwhere != '') {
					$where .= ' AND (' . $subwhere . ')';
				}
			}
		} elseif ($description instanceof SMWSomeAttribute) {
			$id = SMWDataValueFactory::getAttributeObjectTypeID($description->getAttribute());
			switch ($id) {
				case 'text':
					if ($this->addInnerJoin('TEXT', $from, $db, $curtables)) {
						$where .= $curtables['TEXT'] . '.attribute_title=' . 
								$db->addQuotes($description->getAttribute()->getDBKey());
						// no recursion: we do not support further conditions on text-type values
					}
				break;
				default:
					if ($this->addInnerJoin('ATTS', $from, $db, $curtables)) {
						$where .= $curtables['ATTS'] . '.attribute_title=' . 
								$db->addQuotes($description->getAttribute()->getDBKey());
						$this->createSQLQuery($description->getDescription(), $from, $subwhere, $db, $curtables, ($this->m_sortkey == $description->getAttribute()->getDBKey()) );
						if ( $subwhere != '') {
							$where .= ' AND (' . $subwhere . ')';
						}
					}
			}
		}

		if ($sort && (!$description instanceof SMWValueDescription) ) {
			if (array_key_exists('PREVREL', $curtables)) {
				$this->m_sortfield = $curtables['PREVREL'] . '.object_title';
			}
		}
	}

	/**
	 * Make sure that each of the column descriptions in the given array is indexed by *one* index
	 * in the given DB table.
	 */
	protected function setupIndex($table, $columns, $db) {
		$fname = 'SMW::SetupIndex';
		$res = $db->query( 'SHOW INDEX FROM ' . $table , $fname);
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
				$db->query( 'DROP INDEX ' . $key . ' ON ' . $table);
			}
		}

		foreach ($columns as $column) { // add remaining indexes
			if ($column != false) {
				$db->query( "ALTER TABLE $table ADD INDEX ( $column )", $fname );
			}
		}
		return true;
	}

}


