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
	protected $m_tablenum;

///// Reading methods /////

	function getSpecialValues(Title $subject, $specialprop, $requestoptions = NULL) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?

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
			///TODO: this should not be an array of strings unless it was saved as such, do specialprop typechecks
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = $row->value_string;
				}
			}
			$db->freeResult($res);
		}
		return $result;
	}

	function getSpecialSubjects($specialprop, $value, $requestoptions = NULL) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$result = array();

		// take care of "normal" attributes first
		$value_column = 'value_xsd';
		if ( ($requestoptions !== NULL) && ($requestoptions->boundary !== NULL) && ($requestoptions->boundary->isNumeric()) ) {
			$value_column = 'value_num';
		}
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       ' AND attribute_title=' . $db->addQuotes($attribute->getDBkey()) .
		       $this->getSQLConditions($requestoptions,$value_column,'value_xsd');
		$res = $db->select( $db->tableName('smw_attributes'),
		                    'value_unit, value_datatype, value_xsd',
		                    $sql, 'SMW::getAttributeValues', $this->getSQLOptions($requestoptions,$value_column) );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypehandlerValue(SMWTypeHandlerFactory::getTypeHandlerByID($row->value_datatype));
				$dv->setAttribute($attribute->getText());
				$dv->setOutputFormat($outputformat);
				$dv->setXSDValue($row->value_xsd, $row->value_unit);
				$result[] = $dv;
			}
		}
		$db->freeResult($res);

		// finally, look for long strings
		$res = $db->select( $db->tableName('smw_longstrings'),
		                    'value_blob',
		                    'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		                    ' AND attribute_title=' . $db->addQuotes($attribute->getDBkey()),
		                    'SMW::getAttributeValues', $this->getSQLOptions($requestoptions,$value_column) );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypehandlerValue(SMWTypeHandlerFactory::getTypeHandlerByID('text'));
				$dv->setAttribute($attribute->getText());
				$dv->setOutputFormat($outputformat);
				$dv->setXSDValue($row->value_blob, '');
				$result[] = $dv;
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getAttributeSubjects(Title $attribute, SMWDataValue $value, $requestoptions = NULL) {
		if ( !$value->isValid() ) {
			return array();
		}

		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'attribute_title=' . $db->addQuotes($attribute->getDBkey()) .
		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

		$result = array();
		$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getAllAttributeSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromId($row->subject_id);
			}
		}
		$db->freeResult($res);
		$res = $db->select( $db->tableName('smw_longstrings'),
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getAllAttributeSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromId($row->subject_id);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getAttributes(Title $subject, $requestoptions = NULL) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
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
		// relations
		foreach($data->getRelations() as $relation) {
			foreach($data->getRelationObjects($relation) as $object) {
				$db->insert( $db->tableName('smw_relations'),
				             array( 'subject_id' => $subject->getArticleID(),
				            'subject_namespace' => $subject->getNamespace(),
				            'subject_title' => $subject->getDBkey(),
				            'relation_title' => $relation->getDBkey(),
				            'object_namespace' => $object->getNamespace(),
				            'object_title' => $object->getDBkey()),
				            'SMW::updateRelData');
			}
		}

		//attributes
		foreach($data->getAttributes() as $attribute) {
			$attributeValueArray = $data->getAttributeValues($attribute);
			foreach($attributeValueArray as $value) {
				if ($value->getXSDValue()!==false) {
					if ($value->getTypeID() !== 'text') {
						$db->insert( $db->tableName('smw_attributes'),
						             array( 'subject_id' => $subject->getArticleID(),
						             'subject_namespace' => $subject->getNamespace(),
						             'subject_title' => $subject->getDBkey(),
						             'attribute_title' => $attribute->getDBkey(),
						             'value_unit' => $value->getUnit(),
						             'value_datatype' => $value->getTypeID(),
						             'value_xsd' => $value->getXSDValue(),
						             'value_num' => $value->getNumericValue()),
						             'SMW::updateAttData');
					} else {
						$db->insert( $db->tableName('smw_longstrings'),
						             array( 'subject_id' => $subject->getArticleID(),
						             'subject_namespace' => $subject->getNamespace(),
						             'subject_title' => $subject->getDBkey(),
						             'attribute_title' => $attribute->getDBkey(),
						             'value_blob' => $value->getXSDValue()),
						             'SMW::updateAttDataLongString');
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
				$db->insert( $db->tableName('smw_specialprops'),
				             array('subject_id' => $subject->getArticleID(),
				                   'subject_namespace' => $subject->getNamespace(),
				                   'subject_title' => $subject->getDBkey(),
				                   'property_id' => $special,
				                   'value_string' => $stringvalue),
				             'SMW::updateSpecData');
			}
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
	 * TODO: implement namespace restrictions
	 * TODO: we now have sorting even for subquery conditions. Does this work? Is it slow/problematic?
	 * NOTE: we do not support category wildcards, as they have no useful semantics in OWL/RDFS/LP/whatever
	 */
	function getQueryResult(SMWQuery $query) {
		global $smwgIQSortingEnabled;
		
		$db =& wfGetDB( DB_SLAVE );
		$this->m_tablenum = 0;
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deeper levels

		// Build main query
		$this->m_sortkey = $query->sortkey;
		$this->m_sortfield = false;
		
		$from = $db->tableName('page');
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
				$sql_options['ORDER BY'] = "page.page_title $order "; // default
			} elseif ($this->m_sortfield != false) {
				$sql_options['ORDER BY'] = $this->m_sortfield . " $order ";
			} // else: sortkey given but not found: do not sort
		}

		// Execute query and format result as array
		if ($query->querymode == SMWQuery::MODE_COUNT) {
			$res = $db->select($from,
			       'COUNT(DISTINCT page.page_id) AS count',
			        $where,
			        'SMW::getQueryResult',
			        $sql_options );
			$row = $db->fetchObject($res);
			return $row->count;
		} elseif ($query->querymode == SMWQuery::MODE_DEBUG) {
			list( $startOpts, $useIndex, $tailOpts ) = $db->makeSelectOptions( $sql_options );
			$result = '<div style="border: 1px dotted black; background: #A1FB00; padding: 20px; ">' .
			          '<b>SQL-Query</b><br />' .
			          'SELECT DISTINCT page.page_title as title, page.page_namespace as namespace' .
			          ' FROM ' . $from . ' WHERE ' . $where . $tailOpts . '<br />' .
			          '<b>SQL-Query options</b><br />';
			foreach ($sql_options as $key => $value) {
				$result .= "  $key=$value";
			}
			$result .= '</div>';
			return $result;
		} // else: continue

		$res = $db->select($from,
		       'DISTINCT page.page_title as title, page.page_namespace as namespace',
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
		$result = new SMWQueryResult($prs, ( ($count > $query->limit) && ($query->limit >= 0) ) );
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

		$this->setupIndex($smw_relations, 'subject_id', $db);
		$this->setupIndex($smw_relations, 'relation_title', $db);
		$this->setupIndex($smw_relations, 'object_title', $db);

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
					) TYPE=innodb';
			$res = $db->query( $sql, $fname );
		}

		$this->setupIndex($smw_attributes, 'subject_id', $db);
		$this->setupIndex($smw_attributes, 'attribute_title', $db);
		$this->setupIndex($smw_attributes, 'value_num', $db);
		$this->setupIndex($smw_attributes, 'value_xsd', $db);

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

		$this->setupIndex($smw_longstrings, 'subject_id', $db);
		$this->setupIndex($smw_longstrings, 'attribute_title', $db);

		// create table for special properties
		if ($db->tableExists('smw_specialprops') === false) {
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_specialprops . '
					( subject_id         INT(8) UNSIGNED NOT NULL,
					subject_namespace  INT(11) NOT NULL,
					subject_title      VARCHAR(255) NOT NULL,
					property_id        SMALLINT NOT NULL,
					value_string       VARCHAR(255) NOT NULL
					) TYPE=innodb';
			$res = $db->query( $sql, $fname );
		}

		$this->setupIndex($smw_specialprops, 'subject_id', $db);
		$this->setupIndex($smw_specialprops, 'property_id', $db);

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
			$db =& wfGetDB( DB_MASTER ); // TODO: use slave?
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
	 * Add the table $tablename to the $from condition via an inner join,
	 * using the tables that are already available in $curtables (and extending 
	 * $curtables with the new table). Return true if successful or false if it
	 * wasn't possible to make a suitable inner join.
	 */
	protected function addInnerJoin($tablename, &$from, &$db, &$curtables) {
		if (array_key_exists($tablename, $curtables)) { // table already present
			return true;
		}
		if ($tablename == 'PAGE') {
			if (array_key_exists('PREVREL', $curtables)) { // PREVREL cannot be added if not present
				$curtables['PAGE'] = 'p' . $this->m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('page') . ' AS ' . $curtables['PAGE'] . ' ON (' .
				           $curtables['PAGE'] . '.page_title=' . $curtables['PREVREL'] . '.object_title AND ' .
				           $curtables['PAGE'] . '.page_namespace=' . $curtables['PREVREL'] . '.object_namespace)';
				return true;
			}
		} elseif ($tablename == 'CATS') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['CATS'] = 'cl' . $this->m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('categorylinks') . ' AS ' . $curtables['CATS'] . ' ON ' . $curtables['CATS'] . '.cl_from=' . $curtables['PAGE'] . '.page_id';
				return true;
			}
		} elseif ($tablename == 'RELS') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['RELS'] = 'rel' . $this->m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_relations') . ' AS ' . $curtables['RELS'] . ' ON ' . $curtables['RELS'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
				return true;
			}
		} elseif ($tablename == 'ATTS') {
			if ($this->addInnerJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['ATTS'] = 'att' . $this->m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_attributes') . ' AS ' . $curtables['ATTS'] . ' ON ' . $curtables['ATTS'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
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
				$where .=  $curtables['CATS'] . '.cl_to=' . $db->addQuotes($description->getCategory()->getDBKey());
			}
		} elseif ($description instanceof SMWNominalDescription) {
			if (array_key_exists('PREVREL', $curtables)) {
				$where .= $curtables['PREVREL'] . '.object_title=' . 
				          $db->addQuotes($description->getIndividual()->getDBKey()) . ' AND ' .
				          $curtables['PREVREL'] . '.object_namespace=' .
				          $description->getIndividual()->getNamespace();
			} elseif ($this->addInnerJoin('PAGE', $from, $db, $curtables)) {
				$where .= $curtables['PAGE'] . '.page_title=' .
				          $db->addQuotes($description->getIndividual()->getDBKey()) . ' AND ' .
				          $curtables['PAGE'] . '.page_namespace=' .
				          $description->getIndividual()->getNamespace();
			}
		} elseif ($description instanceof SMWValueDescription) {
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
		} elseif ($description instanceof SMWConjunction) {
			foreach ($description->getDescriptions() as $subdesc) {
				/// TODO: this is not optimal -- we drop more table aliases than needed, but its hard to find out what is feasible in recursive calls ...
				$nexttables = array();
				if (array_key_exists('PAGE',$curtables)) {
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
			if ($this->addInnerJoin('ATTS', $from, $db, $curtables)) {
				$where .= $curtables['ATTS'] . '.attribute_title=' . 
				          $db->addQuotes($description->getAttribute()->getDBKey());
				$this->createSQLQuery($description->getDescription(), $from, $subwhere, $db, $curtables, ($this->m_sortkey == $description->getAttribute()->getDBKey()) );
				if ( $subwhere != '') {
					$where .= ' AND (' . $subwhere . ')';
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
	 * Create an SQL query for a given description. The query is defined by call-by-ref
	 * parameters for conditions (WHERE) and tables (FROM). Further condistions are not
	 * encoded in the description. If the parameter $jointable is given, the function will
	 * insert conditions for joining its conditions with the given table. It is assumed 
	 * that the $jointable has an appropriate signature. If no $jointable is given, the 
	 * function returns the name of a table field that contains the *title ids* for the
	 * possible result values, if this is meaningful (i.e. if no datavalues are selected).
	 * In all other cases the return value is undefined.
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
	 * Also, sorting may (1) impair performance, since SQL needs to keep track of additional values,
	 * (2) impair performance, since additional joins may be needed to incorporate into the query the
	 * page names by which something should be sorted (while otherwise IDs suffice for treating many
	 * cases).
	 *
	 * @param $description The SMWDescription to be processed.
	 * @param &$tables The string of computed FROM statements (with aliases for tables), appended to supplied string.
	 * @param &$conds The string of computed WHERE conditions, appended to supplied string.
	 * @param $db The database object
	 * @param $tablepref The base name of table aliases that may be created. Used as a prefix to all such aliases.
	 * @param $jointable The name of the table with which the results should be joined or none if the join is performed by the caller. 
	 * @param $sort True if the subcondition should be used for sorting. This is only meaningful for queries that are below some relation or attribute statement.
	 * 
	 * @TODO: there are still design problems with sorting, since sorting always needs unique article title fields that some descriptions just do not yield. One problem is SMWThingDescription: if not joined, it just cannot provide a (set of) ids that could be used to join the page table for obtaining the title texts. Maybe one should handle all relation-sort cases in the condition root (when handling SMWSomeRelation)? This woul simplify matters but then $sort is only relevant in one case, which is strange design too. But anyway this appears to be preferrable.
	 * @TODO: the case of disjunction is not implemented yet. Somewhat unclear.
	 * @TODO: Maybe there need to be optimisations in certain cases (atomic implementation for common nestings of descriptions?)
	 */
// 	protected function oldcreateSQLQuery(SMWDescription $description, &$tables, &$conds, &$db, $tablepref = 't', $jointable = '', $sort = false) {
// 		$tablecount = 0;
// 		$newtables = '';
// 		$newconds = '';
// 		$result = NULL;
// 		if ($description instanceof SMWThingDescription) {
// 			// no condition (and no possible return id value)
// 			if ( ($sort) && ($jointable != '') ) {
// 				$this->m_sortfield = $jointable . '.object_title';
// 			}
// 		} elseif ($description instanceof SMWClassDescription) {
// 			$cattable = $tablepref . $tablecount++;
// 			$newtables .= $db->tableName('categorylinks') . ' as ' . $cattable;
// 			$newconds .= $cattable . '.cl_to=' . $db->addQuotes($description->getCategory()->getText());
// 			$result = $cattable . '.cl_from';
// 		} elseif ($description instanceof SMWNominalDescription) {
// 			if ($jointable != '') {
// 				$newconds .= $jointable . '.object_title=' . 
// 				             $db->addQuotes($description->getIndividual()->getText()) . ' AND ' .
// 				             $jointable . '.object_namespace=' . $description->getIndividual()->getNamespace();
// 				if ($sort) {
// 					$this->m_sortfield = $jointable . '.object_title';
// 				}
// 			} else {
// 				$pagetable = $tablepref . $tablecount++;
// 				$newtables .= $db->tableName('page') . ' as ' . $pagetable;
// 				$newconds .= $pagetable . '.page_title=' . 
// 				             $db->addQuotes($description->getIndividual()->getText()) . ' AND ' .
// 				             $pagetable . '.page_namespace=' . $description->getIndividual()->getNamespace();
// 				$result = $pagetable . '.page_id';
// 				if ($sort) {
// 					$this->m_sortfield = $pagetable . '.page_title';
// 					$sort = false; // (prevents standard handling of sorting below)
// 				}
// 			}
// 		} elseif ($description instanceof SMWValueDescription) {
// 			if ($jointable != '') {
// 				switch ($description->getComparator()) {
// 					case SMW_CMP_EQ: $op = '='; break;
// 					case SMW_CMP_LEQ: $op = '<='; break;
// 					case SMW_CMP_GEQ: $op = '>='; break;
// 					case SMW_CMP_NEQ: $op = '!='; break;
// 					case SMW_CMP_ANY: default: $op = NULL; break;
// 				}
// 				if ($op !== NULL) {
// 					if ($description->getDatavalue()->isNumeric()) {
// 						$valuefield = 'value_num';
// 						$value = $description->getDatavalue()->getNumericValue();
// 					} else {
// 						$valuefield = 'value_xsd';
// 						$value = $description->getDatavalue()->getXSDValue();
// 					}
// 					//TODO: implement check for unit
// 					$newconds .= $jointable . '.' .  $valuefield . $op . $db->addQuotes($value);
// 					if ($sort != '') {
// 						$this->m_sortfield = $jointable . '.' . $valuefield;
// 					}
// 				}
// 			} // else: not possible
// 		} elseif ($description instanceof SMWConjunction) {
// 			$id = NULL;
// 			foreach ($description->getDescriptions() as $subdesc) {
// 				$subtablepref = $tablepref . $tablecount++ . 't';
// 				$newid = $this->createSQLQuery($subdesc, $newtables, $newconds, $db, $subtablepref);
// 				if ($newid !== NULL) { // catches e.g. the case that owl:Thing is used in conjunctions (no id)
// 					if ($id !== NULL) {
// 						$newconds .= ' AND ' . $id . '=' . $newid;
// 					}
// 					$id = $newid;
// 				}
// 			}
// 			$result = $id; //NULL if only non-sensical conditions were included
// 		} elseif ($description instanceof SMWDisjunction) {
// 			$id = NULL;
// 			///TODO: complete
// 			foreach ($description->getDescriptions() as $subdesc) {
// // 				$subtablepref = $tablepref . $tablecount++ . 't';
// // 				$newid = $this->createSQLQuery($subdesc, $newtables, $newconds, $db, $subtablepref);
// // 				if ($newid !== NULL) { // catches e.g. the case that owl:Thing is used in conjunctions (no id)
// // 					if ($id !== NULL) {
// // 						$newconds .= ' AND ' . $id . '=' . $newid;
// // 					}
// // 					$id = $newid;
// // 				}
// 			}
// 			$result = $id; //NULL if only non-sensical conditions were included
// 		} elseif ($description instanceof SMWSomeRelation) {
// 			$reltable = $tablepref . $tablecount++;
// 			$newtables .= $db->tableName('smw_relations') . ' as ' . $reltable;
// 			$newconds .= $reltable . '.relation_title=' . $db->addQuotes($description->getRelation()->getDBKey());
// 			$this->createSQLQuery($description->getDescription(), $newtables, $newconds, $db, $reltable . 't', $reltable, ($this->m_sortkey == $description->getRelation()->getDBKey()) );
// 			$result = $reltable . '.subject_id';
// 		} elseif ($description instanceof SMWSomeAttribute) {
// 			$atttable = $tablepref . $tablecount++;
// 			$newtables .= $db->tableName('smw_attributes') . ' as ' . $atttable;
// 			$newconds .= $atttable . '.attribute_title=' . $db->addQuotes($description->getAttribute()->getDBKey());
// 			$this->createSQLQuery($description->getDescription(), $newtables, $newconds, $db, $atttable . 't', $atttable, ($this->m_sortkey == $description->getAttribute()->getDBKey()) );
// 			$result = $atttable . '.subject_id';
// 		}
// 
// 		// add standard join and sort clauses if applicable
// 		if ( ( ($jointable != '') || ($sort) ) && ($result !== NULL) ) {
// 			$pagetable = $tablepref . $tablecount++;
// 			if ($newtables != '') {	
// 				$newtables .= ', ';
// 			}
// 			$newtables .= $db->tableName('page') . ' as ' . $pagetable;
// 			$newconds .= $result . '=' . $pagetable . '.page_id';
// 			if ($jointable != '') {
// 			$newconds .= ' AND ' . $jointable . '.object_title=' . $pagetable . '.page_title' . ' AND ' .
// 			             $jointable . '.object_namespace=' . $pagetable . '.page_namespace';
// 			} 
// 			if ($sort != '') {
// 				$this->m_sortfield = $pagetable . '.page_title';
// 			}
// 		}
// 
// 		if ( ($tables != '') && ($newtables != '') ) {
// 			$tables .= ', ';
// 		}
// 		$tables .= $newtables;
// 		if ( ($conds != '') && ($newconds != '') ) {
// 			$conds .= ' AND (';
// 			$newconds .= ')';
// 		}
// 		$conds .= $newconds;
// 		return $result;
// 	}

	/**
	 * Make sure that the given column in the given DB table is indexed by *one* index.
	 */
	protected function setupIndex($table, $column, $db) {
		$fname = 'SMW::SetupIndex';
		$res = $db->query( 'SHOW INDEX FROM ' . $table , $fname);
		if ( !$res ) {
			return false;
		}
		$exists = false;
		while ( $row = $db->fetchObject( $res ) ) {
			if ( $row->Column_name == $column ) {
				if ($exists) { // duplicate index, fix this
					$db->query( 'DROP INDEX ' . $row->Key_name . ' ON ' . $table);
				}
				$exists = true;
			}
		}

		if (!$exists) {
			$db->query( "ALTER TABLE $table ADD INDEX ( `$column` )", $fname );
		}
		return true;
	}

}

?>
