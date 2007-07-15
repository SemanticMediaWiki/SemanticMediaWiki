<?php
/**
 * SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgIP/includes/SMW_DataValueFactory.php" );

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

	function getSpecialValues(Title $subject, $specialprop, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE ); // TODO: Is '=&' needed in PHP5?

		// TODO: this method currently supports no ordering or boundary. This is probably best anyway ...

		$result = array();
		if ($specialprop === SMW_SP_HAS_CATEGORY) { // category membership
			$sql = 'cl_from=' . $db->addQuotes($subject->getArticleID());
			$res = $db->select( 'categorylinks',
								'DISTINCT cl_to',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite result as array
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->cl_to, NS_CATEGORY);
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$sql = 'rd_from=' . $db->addQuotes($subject->getArticleID());
			$res = $db->select( 'redirect',
								'rd_namespace,rd_title',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite results as array
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle($row->rd_namespace, $row->rd_title);
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_SUBPROPERTY_OF) { // subproperty
			$sql = 'subject_title=' . $db->addQuotes($subject->getDBKey()) .
			       'AND namespace=' . $db->addQuotes($subject->getNamespace());
			$res = $db->select( 'smw_subprops',
								'object_title',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite results as array
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle($subject->namespace, $row->object_title);
			}
			$db->freeResult($res);
		} else { // "normal" special property
			$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
				'AND property_id=' . $db->addQuotes($specialprop);
			$res = $db->select( 'smw_specialprops',
								'value_string',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite result as array
			switch ($specialprop) {
			case SMW_SP_HAS_TYPE: // type values
				while($row = $db->fetchObject($res)) {
					$v = SMWDataValueFactory::newSpecialValue($specialprop);
					$v->setXSDValue($row->value_string);
					$result[] = $v;
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
			$res = $db->select( 'categorylinks',
								'DISTINCT cl_from',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// rewrite result as array
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->cl_from);
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$sql = 'rd_title=' . $db->addQuotes($value->getDBKey())
					. ' AND rd_namespace=' . $db->addQuotes($value->getNamespace());
			$res = $db->select( 'redirect',
								'rd_from',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// reqrite results as array
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->rd_from);
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_SUBPROPERTY_OF) { // subproperties
			$sql = 'object_title=' . $db->addQuotes($value->getDBKey()) .
			       'AND namespace=' . $db->addQuotes($value->getNamespace());
			$res = $db->select( 'smw_subprops',
								'subject_title',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($requestoptions) );
			// reqrite results as array
			while($row = $db->fetchObject($res)) {
				$result[] =  Title::makeTitle($value->namespace, $row->subject_title);
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
			$res = $db->select( 'smw_specialprops',
			                    'DISTINCT subject_id',
			                    $sql, 'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->subject_id);
			}
			$db->freeResult($res);
		}

		return $result;
	}


	function getPropertyValues(Title $subject, Title $property, $requestoptions = NULL, $outputformat = '') {
		$db =& wfGetDB( DB_SLAVE );
		$result = array();

		$id = SMWDataValueFactory::getPropertyObjectTypeID($property);
		switch ($id) {
			case '_txt': // long text attribute
				$res = $db->select( $db->tableName('smw_longstrings'),
									'value_blob',
									'subject_id=' . $db->addQuotes($subject->getArticleID()) .
									' AND attribute_title=' . $db->addQuotes($property->getDBkey()),
									'SMW::getPropertyValues', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setOutputFormat($outputformat);
					$dv->setXSDValue($row->value_blob, '');
					$result[] = $dv;
				}
				$db->freeResult($res);
			break;
			case '_wpg': // wiki page
				$res = $db->select( $db->tableName('smw_relations'),
									'object_title, object_namespace',
									'subject_id=' . $db->addQuotes($subject->getArticleID()) .
									' AND relation_title=' . $db->addQuotes($property->getDBkey()) .
									$this->getSQLConditions($requestoptions,'object_title','object_title'),
									'SMW::getPropertyValues', $this->getSQLOptions($requestoptions,'object_title') );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setOutputFormat($outputformat);
					$dv->setValues($row->object_title, $row->object_namespace);
					$result[] = $dv;
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
					' AND attribute_title=' . $db->addQuotes($property->getDBkey()) .
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
		return $result;
	}

	function getPropertySubjects(Title $property, SMWDataValue $value, $requestoptions = NULL) {
		if ( !$value->isValid() ) {
			return array();
		}
		$result = array();
		$db =& wfGetDB( DB_SLAVE );

		switch ($value->getTypeID()) {
		case '_txt': // not supported
		break;
		case '_wpg': // wikipage
			$sql = 'object_namespace=' . $db->addQuotes($value->getNamespace()) .
			       ' AND object_title=' . $db->addQuotes($value->getDBKey()) .
			       ' AND relation_title=' . $db->addQuotes($property->getDBKey()) .
			       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

			$res = $db->select( $db->tableName('smw_relations'),
			                    'DISTINCT subject_id',
			                    $sql, 'SMW::getPropertySubjects',
			                    $this->getSQLOptions($requestoptions,'subject_title') );
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->subject_id);
			}
			$db->freeResult($res);
		break;
		default:
			$sql = 'value_xsd=' . $db->addQuotes($value->getXSDValue()) .
			       ' AND value_unit=' . $db->addQuotes($value->getUnit()) .
			       ' AND attribute_title=' . $db->addQuotes($property->getDBKey()) .
			       $this->getSQLConditions($requestoptions,'subject_title','subject_title');
			$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getPropertySubjects',
		                    $this->getSQLOptions($requestoptions,'subject_title') );
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromID($row->subject_id);
			}
			$db->freeResult($res);
		break;
		}
		return $result;
	}

	function getAllPropertySubjects(Title $property, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'attribute_title=' . $db->addQuotes($property->getDBkey()) .
		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');

		$result = array();
		$id = SMWDataValueFactory::getPropertyObjectTypeID($property);
		switch ($id) {
			case '_txt':
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
			case '_wpg':
				$sql = 'relation_title=' . $db->addQuotes($property->getDBkey()) .
				       $this->getSQLConditions($requestoptions,'subject_title','subject_title');
				$res = $db->select( $db->tableName('smw_relations'),
				                    'DISTINCT subject_id',
				                    $sql, 'SMW::getAllAttributeSubjects',
				                    $this->getSQLOptions($requestoptions,'subject_title') );
				while($row = $db->fetchObject($res)) {
					$result[] = Title::newFromId($row->subject_id);
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

	function getProperties(Title $subject, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) . $this->getSQLConditions($requestoptions,'attribute_title','attribute_title');

		$result = array();
		$res = $db->select( $db->tableName('smw_attributes'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);
		$res = $db->select( $db->tableName('smw_longstrings'),
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'attribute_title') );
		if ($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);

		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       $this->getSQLConditions($requestoptions,'relation_title','relation_title');
		$res = $db->select( $db->tableName('smw_relations'),
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getOutRelations', $this->getSQLOptions($requestoptions,'relation_title') );
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->relation_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getInProperties(SMWDataValue $value, $requestoptions = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$result = array();
		if ($value->getTypeID() == '_wpg') {
			$sql = 'object_namespace=' . $db->addQuotes($value->getNamespace()) .
				' AND object_title=' . $db->addQuotes($value->getDBKey()) .
				$this->getSQLConditions($requestoptions,'relation_title','relation_title');
	
			$res = $db->select( $db->tableName('smw_relations'),
								'DISTINCT relation_title',
								$sql, 'SMW::getInRelations', $this->getSQLOptions($requestoptions,'relation_title') );
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			}
			$db->freeResult($res);
		}
		return $result;
	}

// 	function getRelationObjects(Title $subject, Title $relation, $requestoptions = NULL) {
// 		$db =& wfGetDB( DB_SLAVE );
// 		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
// 		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey()) .
// 		       $this->getSQLConditions($requestoptions,'object_title','object_title');
// 
// 		$res = $db->select( $db->tableName('smw_relations'),
// 		                    'object_title, object_namespace',
// 		                    $sql, 'SMW::getRelationObjects', $this->getSQLOptions($requestoptions,'object_title') );
// 		// rewrite result as array
// 		$result = array();
// 		if($db->numRows( $res ) > 0) {
// 			while($row = $db->fetchObject($res)) {
// 				$result[] = Title::newFromText($row->object_title, $row->object_namespace);
// 			}
// 		}
// 		$db->freeResult($res);
// 
// 		return $result;
// 	}

// 	function getRelationSubjects(Title $relation, Title $object, $requestoptions = NULL) {
// 		$db =& wfGetDB( DB_SLAVE );
// 		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) .
// 		       ' AND object_title=' . $db->addQuotes($object->getDBKey()) .
// 		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey()) .
// 		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');
// 
// 		$res = $db->select( $db->tableName('smw_relations'),
// 		                    'DISTINCT subject_id',
// 		                    $sql, 'SMW::getRelationSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
// 		// rewrite result as array
// 		$result = array();
// 		if($db->numRows( $res ) > 0) {
// 			while($row = $db->fetchObject($res)) {
// 				$result[] = Title::newFromID($row->subject_id);
// 			}
// 		}
// 		$db->freeResult($res);
// 
// 		return $result;
// 	}

// 	function getAllRelationSubjects(Title $relation, $requestoptions = NULL) {
// 		$db =& wfGetDB( DB_SLAVE );
// 		$sql = 'relation_title=' . $db->addQuotes($relation->getDBkey()) .
// 		       $this->getSQLConditions($requestoptions,'subject_title','subject_title');
// 
// 		$res = $db->select( $db->tableName('smw_relations'),
// 		                    'DISTINCT subject_id',
// 		                    $sql, 'SMW::getAllRelationSubjects', $this->getSQLOptions($requestoptions,'subject_title') );
// 		// rewrite result as array
// 		$result = array();
// 		if($db->numRows( $res ) > 0) {
// 			while($row = $db->fetchObject($res)) {
// 				$result[] = Title::newFromId($row->subject_id);
// 			}
// 		}
// 		$db->freeResult($res);
// 
// 		return $result;
// 	}

// 	function getOutRelations(Title $subject, $requestoptions = NULL) {
// 		$db =& wfGetDB( DB_SLAVE );
// 		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
// 		       $this->getSQLConditions($requestoptions,'relation_title','relation_title');
// 
// 		$res = $db->select( $db->tableName('smw_relations'),
// 		                    'DISTINCT relation_title',
// 		                    $sql, 'SMW::getOutRelations', $this->getSQLOptions($requestoptions,'relation_title') );
// 		// rewrite result as array
// 		$result = array();
// 		if($db->numRows( $res ) > 0) {
// 			while($row = $db->fetchObject($res)) {
// 				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
// 			}
// 		}
// 		$db->freeResult($res);
//
// 		return $result;
// 	}

// 	function getInRelations(Title $object, $requestoptions = NULL) {
// 		$db =& wfGetDB( DB_SLAVE );
// 		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) .
// 		       ' AND object_title=' . $db->addQuotes($object->getDBKey()) .
// 		       $this->getSQLConditions($requestoptions,'relation_title','relation_title');
// 
// 		$res = $db->select( $db->tableName('smw_relations'),
// 		                    'DISTINCT relation_title',
// 		                    $sql, 'SMW::getInRelations', $this->getSQLOptions($requestoptions,'relation_title') );
// 		// rewrite result as array
// 		$result = array();
// 		if($db->numRows( $res ) > 0) {
// 			while($row = $db->fetchObject($res)) {
// 				$result[] = Title::newFromText($row->relation_title, SMW_NS_RELATION);
// 			}
// 		}
// 		$db->freeResult($res);
// 
// 		return $result;
// 	}

///// Writing methods /////

	function deleteSubject(Title $subject) {
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
		if ( ($subject->getNamespace() == SMW_NS_ATTRIBUTE) || ($subject->getNamespace() == SMW_NS_RELATION) ) {
			$db->delete('smw_subprops',
			            array('subject_title' => $subject->getDBKey(), 'namespace' => $subject->getNamespace()),
			            'SMW::deleteSubject::Subprops');
		}
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
		$up_subprops = array();

		//properties
		foreach($data->getProperties() as $property) {
			$propertyValueArray = $data->getPropertyValues($property);
			foreach($propertyValueArray as $value) {
				if ($value->isValid()) {
					if ($value->getTypeID() == '_txt') {
						$up_longstrings[] =
						      array( 'subject_id' => $subject->getArticleID(),
						             'subject_namespace' => $subject->getNamespace(),
						             'subject_title' => $subject->getDBkey(),
						             'attribute_title' => $property->getDBkey(),
						             'value_blob' => $value->getXSDValue() );
					} elseif ($value->getTypeID() == '_wpg') { // f.k.a. "Relation"
						$up_relations[] =
						     array( 'subject_id' => $subject->getArticleID(),
						            'subject_namespace' => $subject->getNamespace(),
						            'subject_title' => $subject->getDBkey(),
						            'relation_title' => $property->getDBkey(),
						            'object_namespace' => $value->getNamespace(),
						            'object_title' => $value->getDBkey() );
					} else {
						$up_attributes[] =
						      array( 'subject_id' => $subject->getArticleID(),
						             'subject_namespace' => $subject->getNamespace(),
						             'subject_title' => $subject->getDBkey(),
						             'attribute_title' => $property->getDBkey(),
						             'value_unit' => $value->getUnit(),
						             'value_datatype' => $value->getTypeID(),
						             'value_xsd' => $value->getXSDValue(),
						             'value_num' => $value->getNumericValue() );
					}
				}
			}
		}

		//special properties
		foreach ($data->getSpecialProperties() as $special) {
			switch ($special) {
				case SMW_SP_IMPORTED_FROM: case SMW_SP_HAS_CATEGORY: case SMW_SP_REDIRECTS_TO:
					// don't store this, just used for display; 
					// TODO: filtering here is bad for fully neglected properties (IMPORTED FROM)
				break;
				case SMW_SP_SUBPROPERTY_OF:
					if ( ($subject->getNamespace() != SMW_NS_RELATION) && 
					     ($subject->getNamespace() != SMW_NS_ATTRIBUTE) ) {
						break;
					}
					$valueArray = $data->getSpecialValues($special);
					foreach($valueArray as $value) {
						if ( $subject->getNamespace() == $value->getNamespace())  {
							$up_subprops[] =
						      array('subject_title' => $subject->getDBkey(),
						            'namespace' => $subject->getNamespace(),
						            'object_title' => $value->getDBKey());
						}
					}
				break;
				default: // normal special value
					$valueArray = $data->getSpecialValues($special);
					foreach($valueArray as $value) {
						if ($value instanceof SMWDataValue) {
							if ($value->getXSDValue() !== false) { // filters out error-values etc.
								$stringvalue = $value->getXSDValue();
							}
						} elseif ($value instanceof Title) {
							if ( $special == SMW_SP_HAS_TYPE ) { /// TODO: ensure that all types are given as DVs!
								$stringvalue = $value->getDBKey();
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
				break;
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

		$db->update('smw_relations', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_attributes', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_longstrings', $val_array, $cond_array, 'SMW::changeTitle');
		$db->update('smw_specialprops', $val_array, $cond_array, 'SMW::changeTitle');

		if ( ($oldtitle->getNamespace() == SMW_NS_ATTRIBUTE) || ($oldtitle->getNamespace() == SMW_NS_RELATION) ) {
			if ( $oldtitle->getNamespace() == $newtitle->getNamespace() ) {
				$db->update('smw_subprops', array('subject_title' => $newtitle->getDBkey()), array('subject_title' => $oldtitle->getDBkey()), 'SMW::changeTitle');
			} else {
				$db->delete('smw_subprops', array('subject_title' => $oldtitle->getDBKey()), 'SMW::changeTitle');
			}
		}
	}

///// Query answering /////

	/**
	 * The SQL store's implementation of query answering.
	 *
	 * TODO: we now have sorting even for subquery conditions. Does this work? Is it slow/problematic?
	 * NOTE: we do not support category wildcards, as they have no useful semantics in OWL/RDFS/LP/whatever
	 */
	function getQueryResult(SMWQuery $query) {
		global $smwgQSortingSupport;

		$db =& wfGetDB( DB_SLAVE );
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deeper levels

		// Build main query
		$this->m_usedtables = array();
		$this->m_sortkey = $query->sortkey;
		$this->m_sortfield = false;

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
			          $query->getDescription()->getQueryString() . '<br />' .
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
		while ( ($count<$query->getLimit()) && ($row = $db->fetchObject($res)) ) {
			$count++;
			$qr[] = Title::newFromText($row->title, $row->namespace);
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
					case SMW_PRINT_THIS:
						$row[] = new SMWResultArray(array($qt), $pr);
						break;
					case SMW_PRINT_RELS:
						$row[] = new SMWResultArray($this->getPropertyValues($qt,$pr->getTitle()), $pr);
						break;
					case SMW_PRINT_CATS:
						$row[] = new SMWResultArray($this->getSpecialValues($qt,SMW_SP_HAS_CATEGORY), $pr);
						break;
					case SMW_PRINT_ATTS:
						$row[] = new SMWResultArray($this->getPropertyValues($qt,$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
						break;
				}
			}
			$result->addRow($row);
		}

		return $result;
	}

///// Setup store /////

	function setup($verbose = true) {
		global $wgDBname;
		$this->reportProgress("Setting up standard database configuration for SMW ...\n\n",$verbose);
		$db =& wfGetDB( DB_MASTER );

		extract( $db->tableNames('smw_relations','smw_attributes','smw_longstrings','smw_specialprops','smw_subprops') );

		// create relation table
		$this->setupTable($smw_relations,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) NOT NULL',
		                    'relation_title'    => 'VARCHAR(255) NOT NULL',
		                    'object_namespace'  => 'INT(11) NOT NULL',
		                    'object_title'      => 'VARCHAR(255) NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_relations, array('subject_id','relation_title','object_title,object_namespace'), $db);

		// create attribute table
		$this->setupTable($smw_attributes,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) NOT NULL',
		                    'attribute_title'   => 'VARCHAR(255) NOT NULL',
		                    'value_unit'        => 'VARCHAR(63)',
		                    'value_datatype'    => 'VARCHAR(31) NOT NULL', /// TODO: remove value_datatype column
		                    'value_xsd'         => 'VARCHAR(255) NOT NULL',
		                    'value_num'         => 'DOUBLE'), $db, $verbose);
		$this->setupIndex($smw_attributes, array('subject_id','attribute_title','value_num','value_xsd'), $db);

		// create table for long string attributes
		$this->setupTable($smw_longstrings,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) NOT NULL',
		                    'attribute_title'   => 'VARCHAR(255) NOT NULL',
		                    'value_blob'        => 'MEDIUMBLOB'), $db, $verbose);
		$this->setupIndex($smw_longstrings, array('subject_id','attribute_title'), $db);

		// create table for special properties
		$this->setupTable($smw_specialprops,
		              array('subject_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'subject_namespace' => 'INT(11) NOT NULL',
		                    'subject_title'     => 'VARCHAR(255) NOT NULL',
		                    'property_id'       => 'SMALLINT(6) NOT NULL',
		                    'value_string'      => 'VARCHAR(255) NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_specialprops, array('subject_id', 'property_id'), $db);

		// create table for subproperty relationships
		$this->setupTable($smw_subprops,
		              array('subject_title'     => 'VARCHAR(255) NOT NULL',
		                    'namespace'         => 'INT(11) NOT NULL', // will be obsolete when collapsing attribs+rels
		                    'object_title'      => 'VARCHAR(255) NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_subprops, array('subject_title', 'namespace', 'object_title'), $db);

		$this->reportProgress("Database initialised successfully.\n",$verbose);
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
	 * Make a (temporary) table that contains the lower closure of the given category
	 * wrt. the category table.
	 */
	protected function getCategoryTable($catname, &$db) {
		global $wgDBname, $smwgQSubcategoryDepth;

		$tablename = 'cats' . SMWSQLStore::$m_tablenum++;
		$this->m_usedtables[] = $tablename;
		$db->query( 'CREATE TEMPORARY TABLE ' . $tablename .
		            '( title VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		if (array_key_exists($catname, SMWSQLStore::$m_categorytables)) { // just copy known result
			$db->query("INSERT INTO $tablename (title) SELECT " . 
			            SMWSQLStore::$m_categorytables[$catname] . 
			            '.title FROM ' . SMWSQLStore::$m_categorytables[$catname], 
			           'SMW::getCategoryTable');
			return $tablename;
		}

		// Create multiple temporary tables for recursive computation
		$db->query( 'CREATE TEMPORARY TABLE smw_newcats
		             ( title VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		$db->query( 'CREATE TEMPORARY TABLE smw_rescats
		             ( title VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		$tmpnew = 'smw_newcats';
		$tmpres = 'smw_rescats';

		$pagetable = $db->tableName('page');
		$cltable = $db->tableName('categorylinks');
		$db->query("INSERT INTO $tablename (title) VALUES ('$catname')", 'SMW::getCategoryTable');
		$db->query("INSERT INTO $tmpnew (title) VALUES ('$catname')", 'SMW::getCategoryTable');

		/// TODO: avoid duplicate results?
		for ($i=0; $i<$smwgQSubcategoryDepth; $i++) {
			$db->query("INSERT INTO $tmpres (title) SELECT $pagetable.page_title 
			            FROM $cltable,$pagetable,$tmpnew WHERE 
			            $cltable.cl_to=$tmpnew.title AND
			            $pagetable.page_namespace=" . NS_CATEGORY . " AND 
			            $pagetable.page_id=$cltable.cl_from", 'SMW::getCategoryTable');
			if ($db->affectedRows() == 0) { // no change, exit loop
				continue;
			}
			$db->query("INSERT INTO $tablename (title) SELECT $tmpres.title
			            FROM $tmpres", 'SMW::getCategoryTable');
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
	 * Make a (temporary) table that contains the lower closure of the given property
	 * wrt. the subproperty relation.
	 */
	protected function getPropertyTable($propname, &$db) {
		global $wgDBname, $smwgQSubpropertyDepth;

		$tablename = 'prop' . SMWSQLStore::$m_tablenum++;
		$this->m_usedtables[] = $tablename;
		$db->query( 'CREATE TEMPORARY TABLE ' . $tablename .
		            '( title VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		if (array_key_exists($propname, SMWSQLStore::$m_propertytables)) { // just copy known result
			$db->query("INSERT INTO $tablename (title) SELECT " . 
			            SMWSQLStore::$m_propertytables[$propname] . 
			            '.title FROM ' . SMWSQLStore::$m_propertytables[$propname], 
			           'SMW::getPropertyTable');
			return $tablename;
		}

		// Create multiple temporary tables for recursive computation
		$db->query( 'CREATE TEMPORARY TABLE smw_new
		             ( title VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		$db->query( 'CREATE TEMPORARY TABLE smw_res
		             ( title VARCHAR(255) NOT NULL )
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$sptable = $db->tableName('smw_subprops');
		$db->query("INSERT INTO $tablename (title) VALUES ('$propname')", 'SMW::getPropertyTable');
		$db->query("INSERT INTO $tmpnew (title) VALUES ('$propname')", 'SMW::getPropertyTable');

		/// TODO: avoid duplicate results?
		for ($i=0; $i<$smwgQSubpropertyDepth; $i++) {
			$db->query("INSERT INTO $tmpres (title) SELECT $sptable.subject_title
			            FROM $sptable,$tmpnew WHERE 
			            $sptable.object_title=$tmpnew.title", 'SMW::getPropertyTable');
			if ($db->affectedRows() == 0) { // no change, exit loop
				continue;
			}
			$db->query("INSERT INTO $tablename (title) SELECT $tmpres.title
			            FROM $tmpres", 'SMW::getCategoryTable');
			$db->query('TRUNCATE TABLE ' . $tmpnew, 'SMW::getPropertyTable'); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		SMWSQLStore::$m_propertytables[$propname] = $tablename;
		$db->query('DROP TABLE smw_new', 'SMW::getPropertyTable');
		$db->query('DROP TABLE smw_res', 'SMW::getPropertyTable');
		return $tablename;
	}

	/**
	 * Add the table $tablename to the $from condition via an inner join,
	 * using the tables that are already available in $curtables (and extending 
	 * $curtables with the new table). Return true if successful or false if it
	 * wasn't possible to make a suitable inner join.
	 */
	protected function addJoin($tablename, &$from, &$db, &$curtables) {
		global $smwgQEqualitySupport;
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
			if ($this->addJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['CATS'] = 'cl' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['CATS'] . '.cl_from=' . $curtables['PAGE'] . '.page_id';
				/// TODO: slow, introduce another parameter to activate this
				if ($smwgQEqualitySupport && (array_key_exists('PREVREL', $curtables))) {
					// only do this at inner queries (PREVREL set)
					$this->addJoin('REDIPAGE', $from, $db, $curtables);
					$cond = '((' . $cond . ') OR (' .
					  $curtables['REDIPAGE'] . '.page_id=' . $curtables['CATS'] . '.cl_from))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('categorylinks') . ' AS ' . $curtables['CATS'] . ' ON ' . $cond;
				return true;
			}
		} elseif ($tablename == 'RELS') {
			if ($this->addJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['RELS'] = 'rel' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['RELS'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
				/// TODO: slow, introduce another parameter to activate this
				if ($smwgQEqualitySupport && (array_key_exists('PREVREL', $curtables))) {
					// only do this at inner queries (PREVREL set)
					$this->addJoin('REDIRECT', $from, $db, $curtables);
					$cond = '((' . $cond . ') OR (' .
					  //$curtables['PAGE'] . '.page_id=' . $curtables['REDIRECT'] . '.rd_from AND ' .
					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['RELS'] . '.subject_title AND ' .
					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['RELS'] . '.subject_namespace))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('smw_relations') . ' AS ' . $curtables['RELS'] . ' ON ' . $cond;
				return true;
			}
		} elseif ($tablename == 'ATTS') {
			if ($this->addJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['ATTS'] = 'att' . SMWSQLStore::$m_tablenum++;
				$cond = $curtables['ATTS'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
				/// TODO: slow, introduce another parameter to activate this
				if ($smwgQEqualitySupport && (array_key_exists('PREVREL', $curtables))) {
					// only do this at inner queries (PREVREL set)
					$this->addJoin('REDIRECT', $from, $db, $curtables);
					$cond = '((' . $cond . ') OR (' .
					  //$curtables['PAGE'] . '.page_id=' . $curtables['REDIRECT'] . '.rd_from AND ' .
					  $curtables['REDIRECT'] . '.rd_title=' . $curtables['ATTS'] . '.subject_title AND ' .
					  $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['ATTS'] . '.subject_namespace))';
				}
				$from .= ' INNER JOIN ' . $db->tableName('smw_attributes') . ' AS ' . $curtables['ATTS'] . ' ON ' . $cond;
				return true;
			}
		} elseif ($tablename == 'TEXT') {
			if ($this->addJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['TEXT'] = 'txt' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_longstrings') . ' AS ' . $curtables['TEXT'] . ' ON ' . $curtables['TEXT'] . '.subject_id=' . $curtables['PAGE'] . '.page_id';
				return true;
			}
		} elseif ($tablename == 'REDIRECT') {
			if ($this->addJoin('PAGE', $from, $db, $curtables)) { // try to add PAGE
				$curtables['REDIRECT'] = 'rd' . SMWSQLStore::$m_tablenum++;
				$from .= ' LEFT JOIN ' . $db->tableName('redirect') . ' AS ' . $curtables['REDIRECT'] . ' ON ' . $curtables['REDIRECT'] . '.rd_from=' . $curtables['PAGE'] . '.page_id';
				return true;
			}
		} elseif ($tablename == 'REDIPAGE') { // add another copy of page for getting ids of redirect targets
			if ($this->addJoin('REDIRECT', $from, $db, $curtables)) { 
				$curtables['REDIPAGE'] = 'rp' . SMWSQLStore::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('page') . ' AS ' . $curtables['REDIPAGE'] . ' ON (' .
				         $curtables['REDIRECT'] . '.rd_title=' . $curtables['REDIPAGE'] . '.page_title AND ' .
					     $curtables['REDIRECT'] . '.rd_namespace=' . $curtables['REDIPAGE'] . '.page_namespace)';
				
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
			// nothing to check
		} elseif ($description instanceof SMWClassDescription) {
			if ($this->addJoin('CATS', $from, $db, $curtables)) {
				global $smwgQSubcategoryDepth;
				if ($smwgQSubcategoryDepth > 0) {
					$ct = $this->getCategoryTable($description->getCategory()->getDBKey(), $db);
					$from = '`' . $ct . '`, ' . $from;
					$where = "$ct.title=" . $curtables['CATS'] . '.cl_to';
				} else {
					$where .=  $curtables['CATS'] . '.cl_to=' . $db->addQuotes($description->getCategory()->getDBKey());
				}
			}
		} elseif ($description instanceof SMWNamespaceDescription) {
			if ($this->addJoin('PAGE', $from, $db, $curtables)) {
				$where .=  $curtables['PAGE'] . '.page_namespace=' . $db->addQuotes($description->getNamespace());
			}
		} elseif ($description instanceof SMWNominalDescription) {
			global $smwgQEqualitySupport;
			if ($smwgQEqualitySupport) {
				$page = $this->getRedirectTarget($description->getIndividual(), $db);
			} else {
				$page = $description->getIndividual();
			}
			if (array_key_exists('PREVREL', $curtables)) {
				$cond = $curtables['PREVREL'] . '.object_title=' .
				        $db->addQuotes($page->getDBKey()) . ' AND ' .
				        $curtables['PREVREL'] . '.object_namespace=' .
				        $page->getNamespace();
				if ( $smwgQEqualitySupport && ($this->addJoin('REDIRECT', $from, $db, $curtables)) ) {
					$cond = '(' . $cond . ') OR (' . 
					        $curtables['REDIRECT'] . '.rd_title=' .
					        $db->addQuotes($page->getDBKey()) . ' AND ' .
					        $curtables['REDIRECT'] . '.rd_namespace=' .
					        $page->getNamespace() . ')';
				}
				$where .= $cond;
			} elseif ($this->addJoin('PAGE', $from, $db, $curtables)) {
				$where .= $curtables['PAGE'] . '.page_title=' .
				          $db->addQuotes($page->getDBKey()) . ' AND ' .
				          $curtables['PAGE'] . '.page_namespace=' .
				          $page->getNamespace();
			}
		} elseif ($description instanceof SMWValueDescription) {
			switch ($description->getDatavalue()->getTypeID()) {
				case '_txt': // actually this should not happen; we cannot do anything here 
				break;
				default:
					if ( $this->addJoin('ATTS', $from, $db, $curtables) ) {
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
				/// TODO: will be obsolete when PREVREL provides page indices
				if ($this->addJoin('PAGE', $from, $db, $curtables)) {
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
			if ($this->addJoin('RELS', $from, $db, $curtables)) {
				global $smwgQSubpropertyDepth;
				if ($smwgQSubpropertyDepth > 0) {
					$pt = $this->getPropertyTable($description->getRelation()->getDBKey(), $db);
					$from = '`' . $pt . '`, ' . $from;
					$where = "$pt.title=" . $curtables['RELS'] . '.relation_title';
				} else {
					$where .= $curtables['RELS'] . '.relation_title=' . 
					          $db->addQuotes($description->getRelation()->getDBKey());
				}
				$nexttables = array( 'PREVREL' => $curtables['RELS'] );
				$this->createSQLQuery($description->getDescription(), $from, $subwhere, $db, $nexttables, ($this->m_sortkey == $description->getRelation()->getDBKey()) );
				if ( $subwhere != '') {
					$where .= ' AND (' . $subwhere . ')';
				}
			}
		} elseif ($description instanceof SMWSomeAttribute) {
			$id = SMWDataValueFactory::getPropertyObjectTypeID($description->getAttribute());
			switch ($id) {
				case '_txt':
					$table = 'TEXT';
					$sub = false; //no recursion: we do not support further conditions on text-type values
				break;
				default:
					$table = 'ATTS';
					$sub = true;
			}
			if ($this->addJoin($table, $from, $db, $curtables)) {
				global $smwgQSubpropertyDepth;
				if ($smwgQSubpropertyDepth > 0) {
					$pt = $this->getPropertyTable($description->getAttribute()->getDBKey(), $db);
					$from = '`' . $pt . '`, ' . $from;
					$where = "$pt.title=" . $curtables[$table] . '.attribute_title';
				} else {
					$where .= $curtables[$table] . '.attribute_title=' .
					          $db->addQuotes($description->getAttribute()->getDBKey());
				}
				if ($sub) {
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
			$sql = 'CREATE TABLE ' . $wgDBname . '.' . $table . ' (';
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


