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


	function getAttributeValues(Title $subject, Title $attribute, $requestoptions = NULL) {
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
				$dv = SMWDataValue::newTypedValue(SMWTypeHandlerFactory::getTypeHandlerByID($row->value_datatype));
				$dv->setAttribute($attribute->getText());
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
				$dv = SMWDataValue::newTypedValue(SMWTypeHandlerFactory::getTypeHandlerByID('text'));
				$dv->setAttribute($attribute->getText());
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

	function getQueryResult(SMWQuery $query) {
		$db =& wfGetDB( DB_SLAVE );
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deepder levels

		// Here, the actual SQL query building and execution must happen. Loads of work.
		// For testing purposes, we assume that the outcome is the following array of titles
		// (the eventual query result format is quite certainly different)
		$tables = '';
		$conds = '';
		$id = $this->createSQLQuery($query->getDescription(), $tables, $conds, $db);
		if ($tables != '') {
			$tables .= ', ';
		}
		$tables .= 'page';
		if ($conds != '') {
			$conds .= ' AND ';
		}
		$conds .= 'page.page_id=' . $id;
		print $tables . "<br />\n" . $conds . "<br />\n"; //DEBUG

		$sql_options = array('LIMIT' => '5');
		$res = $db->select($tables,
		       'DISTINCT page.page_title as title, page.page_namespace as namespace',
		        $conds,
		        'SMW::getQueryResult',
		        $sql_options );
		$qr = array();
		while ($row = $db->fetchObject($res)) {
			$qr[] = Title::newFromText($row->title, $row->namespace);
		}
		$db->freeResult($res);
	
		//$qr = array(Title::newFromText('Angola'), Title::newFromText('Namibia'));

		// create result by executing print statements for everything that was fetched
		///TODO: use limit and offset values
		$result = new SMWQueryResult($prs);
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
						///TODO: respect given datavalue (desired unit), needs extension of getAttributeValues()
						$row[] = new SMWResultArray($this->getAttributeValues($qt,$pr->getTitle()), $pr);
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
	 * Create an SQL query for a given description. The query is defined by call-by-ref
	 * parameters for conditions (WHERE) and tables (FROM). Further condistions are not
	 * encoded in the description. If the parameter $jointable is given, the function will
	 * insert conditions for joining its conditions with the given table. It is assumed 
	 * that the $jointable has an appropriate signature. If no $jointable is given, the 
	 * function returns the name of a table field that contains the *title ids* for the
	 * possible result values, if this is meaningful (i.e. if no datavalues are selected).
	 * In all other cases the return value is undefined.
	 * 
	 * @param $description The SMWDescription to be processed.
	 * @param &$tables The string of computed FROM statements (with aliases for tables), appended to supplied string.
	 * @param &$conds The string of computed WHERE conditions, appended to supplied string.
	 * @param $db The database object
	 * @param $tablepref The base name of table aliases that may be created. Used as a prefix to all such aliases.
	 * @param $jointable The name of the table with which the results should be joined or none if the join is performed by the caller. 
	 */
	protected function createSQLQuery(SMWDescription $description, &$tables, &$conds, &$db, $tablepref = 't', $jointable = '') {
		$tablecount = 0;
		$newtables = '';
		$newconds = '';
		$result = NULL;
		if ($description instanceof SMWThingDescription) {
			// no condition (and no possible return id value)
		} elseif ($description instanceof SMWClassDescription) {
			$cattable = $tablepref . $tablecount++;
			$newtables .= $db->tableName('categorylinks') . ' as ' . $cattable;
			$newconds .= $cattable . '.cl_to=' . $db->addQuotes($description->getCategory()->getText());
			$result = $cattable . '.cl_from';
		} elseif ($description instanceof SMWNominalDescription) {
			if ($jointable != '') {
				$newconds .= $jointable . '.object_title=' . 
				             $db->addQuotes($description->getIndividual()->getText()) . ' AND ' .
				             $jointable . '.object_namespace=' . $description->getIndividual()->getNamespace();
			} else {
				$pagetable = $tablepref . $tablecount++;
				$newtables .= $db->tableName('page') . ' as ' . $pagetable;
				$newconds .= $pagetable . '.page_title=' . 
				             $db->addQuotes($description->getIndividual()->getText()) . ' AND ' .
				             $pagetable . '.page_namespace=' . $description->getIndividual()->getNamespace();
				$result = $pagetable . '.page_id';
			}
		} elseif ($description instanceof SMWValueDescription) {
			if ($jointable != '') {
				switch ($description->getComparator()) {
					case SMW_CMP_EQUAL: $op = '='; break;
					case SMW_CMP_LEQ: $op = '<='; break;
					case SMW_CMP_GEQ: $op = '>='; break;
					case SMW_CMP_NEQ: $op = '!='; break;
					case SMW_CMP_ANY: default: $op = NULL; break;
				}
				if ($op !== NULL) {
					if ($description->getDatavalue()->isNumeric()) {
						$valuefiled = 'value_num';
						$value = $description->getDatavalue()->getNumericValue();
					} else {
						$valuefiled = 'value_xsd';
						$value = $description->getDatavalue()->getXSDValue();
					}
					$newconds .= $jointable . '.' .  $valuefiled . $op . $db->addQuotes($value);
				}
			} // else: not possible
		} elseif ($description instanceof SMWConjunction) {
			$id = NULL;
			foreach ($description->getDescriptions() as $subdesc) {
				$subtablepref = $tablepref . $tablecount++ . 't';
				$newid = $this->createSQLQuery($subdesc, $newtables, $newconds, $db, $subtablepref);
				if ($newid !== NULL) { // catches e.g. the case that owl:Thing is used in conjunctions (no id)
					if ($id !== NULL) {
						$newconds .= ' AND ' . $id . '=' . $newid;
					}
					$id = $newid;
				}
			}
			$result = $id; //NULL if only non-sensical conditions were included
		} elseif ($description instanceof SMWDisjunction) {
			$id = NULL;
			//TODO
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
			$result = $id; //NULL if only non-sensical conditions were included
		} elseif ($description instanceof SMWSomeRelation) {
			$reltable = $tablepref . $tablecount++;
			$newtables .= $db->tableName('smw_relations') . ' as ' . $reltable;
			$newconds .= $reltable . '.relation_title=' . $db->addQuotes($description->getRelation()->getDBKey());
			$this->createSQLQuery($description->getDescription(), $newtables, $newconds, $db, $reltable . 't', $reltable);
			$result = $reltable . '.subject_id';
		} elseif ($description instanceof SMWSomeAttribute) {
			$atttable = $tablepref . $tablecount++;
			$newtables .= $db->tableName('smw_attributes') . ' as ' . $atttable;
			$newconds .= $atttable . '.attribute_title=' . $db->addQuotes($description->getAttribute()->getDBKey());
			$this->createSQLQuery($description->getDescription(), $newtables, $newconds, $db, $atttable . 't', $atttable);
			$result = $atttable . '.subject_id';
		}

		// add standard join clauses if applicable
		if ( ($jointable != '') && ($result !== NULL) ) {
			$pagetable = $tablepref . $tablecount++;
			if ($newtables != '') {	
				$newtables .= ', ';
			}
			$newtables .= $db->tableName('page') . ' as ' . $pagetable;
			$newconds .= $result . '=' . $pagetable . '.page_id AND ' .
			             $jointable . '.object_title=' . $pagetable . '.page_title' . ' AND ' .
			             $jointable . '.object_namespace=' . $pagetable . '.page_namespace';
		}

		if ( ($tables != '') && ($newtables != '') ) {
			$tables .= ', ';
		}
		$tables .= $newtables;
		if ( ($conds != '') && ($newconds != '') ) {
			$conds .= ' AND (';
			$newconds .= ')';
		}
		$conds .= $newconds;
		return $result;
	}

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
