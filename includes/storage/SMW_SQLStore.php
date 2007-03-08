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

	function getSpecialValues(Title $subject, $specialprop, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       'AND property_id=' . $db->addQuotes($specialprop);
		$res = $db->select( $db->tableName('smw_specialprops'), 
		                    'value_string',
		                    $sql, 'SMW::getSpecialValues', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = $row->value_string;
			}
		}
		$db->freeResult($res);
		return $result;
	}


	function getAttributeValues(Title $subject, Title $attribute, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       ' AND attribute_title=' . $db->addQuotes($attribute->getDBkey());

		$res = $db->select( $db->tableName('smw_attributes'), 
		                    'value_unit, value_datatype, value_xsd',
		                    $sql, 'SMW::getAttributeValues', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValue::newTypedValue(SMWTypeHandlerFactory::getTypeHandlerByID($row->value_datatype));
				$dv->setXSDValue($row->value_xsd, $row->value_unit);
				$result[] = $dv;
			}
		}
		$db->freeResult($res);

		return $result;
	}


	function getAttributes(Title $subject, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID());

		$res = $db->select( $db->tableName('smw_attributes'), 
		                    'DISTINCT attribute_title',
		                    $sql, 'SMW::getAttributes', $this->getSQLOptions($limit, $offset) );
		// rewrite result as array
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			}
		}
		$db->freeResult($res);

		return $result;
	}

	function getRelationObjects(Title $subject, Title $relation, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'object_title, object_namespace',
		                    $sql, 'SMW::getRelationObjects', $this->getSQLOptions($limit, $offset) );
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

	function getRelationSubjects(Title $relation, Title $object, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) . 
		       ' AND object_title=' . $db->addQuotes($object->getDBKey()) .
		       ' AND relation_title=' . $db->addQuotes($relation->getDBKey());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'DISTINCT subject_id',
		                    $sql, 'SMW::getRelationSubjects', $this->getSQLOptions($limit, $offset) );
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

	function getOutRelations(Title $subject, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getOutRelations', $this->getSQLOptions($limit, $offset) );
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

	function getInRelations(Title $object, $limit = -1, $offset = 0) {
		$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
		$sql = 'object_namespace=' . $db->addQuotes($object->getNamespace()) . 
		       ' AND object_title=' . $db->addQuotes($object->getDBKey());

		$res = $db->select( $db->tableName('smw_relations'), 
		                    'DISTINCT relation_title',
		                    $sql, 'SMW::getInRelations', $this->getSQLOptions($limit, $offset) );
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
		$db->delete($db->tableName('smw_specialprops'), 
		            array('subject_id' => $subject->getArticleID()),
		            'SMW::deleteSubject::Specialprops');
	}

	function updateData(SMWSemData $data) {
		$db =& wfGetDB( DB_MASTER );
		$subject = $data->getSubject();
		$this->deleteSubject($subject);
		// relations
		foreach(SMWSemanticData::$semdata->getRelations() as $relation) {
			foreach(SMWSemanticData::$semdata->getRelationObjects($relation) as $object) {
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
		foreach(SMWSemanticData::$semdata->getAttributes() as $attribute) {
			$attributeValueArray = SMWSemanticData::$semdata->getAttributeValues($attribute);
			foreach($attributeValueArray as $value) {
				// DEBUG echo "in storeAttributes, considering $value, getXSDValue=" . $value->getXSDValue() . "<br />\n" ;
				if ($value->getXSDValue()!==false) {
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
				}
			}
		}

		//special properties
		foreach (SMWSemanticData::$semdata->getSpecialProperties() as $special) {
			if ($special == SMW_SP_IMPORTED_FROM) { // don't store this, just used for display; TODO: filtering it here is bad
				continue;
			}
			$valueArray = SMWSemanticData::$semdata->getSpecialValues($special);
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
		$db->update($db->tableName('smw_specialprops'), $val_array, $cond_array, 'SMW::changeTitle');
	}

///// Private methods /////

	/**
	 * Transform input parameters into a suitable array of SQL options.
	 */
	protected function getSQLOptions($limit, $offset) {
		$sql_options = array();
		if ($limit >= 0) {
			$sql_options['LIMIT'] = $limit;
		}
		if ($offset > 0) {
			$sql_options['OFFSET'] = $offset;
		}
		return $sql_options;
	}
	
///// Setup store /////

	function setup() {
		global $wgDBname;

		$fname = 'SMW::setupDatabase';
		$db =& wfGetDB( DB_MASTER );
		
		extract( $db->tableNames('smw_relations','smw_attributes','smw_specialprops') );

		// create relation table
		$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_relations . '
				( subject_id         INT(8) UNSIGNED NOT NULL,
				  subject_namespace  INT(11) NOT NULL,
				  subject_title      VARCHAR(255) NOT NULL,
				  relation_title     VARCHAR(255) NOT NULL,
				  object_namespace   INT(11) NOT NULL,
				  object_title       VARCHAR(255) NOT NULL
				) TYPE=innodb';
		$res = $db->query( $sql, $fname );

		$sql = "ALTER TABLE $smw_relations ADD INDEX ( `subject_id` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_relations ADD INDEX ( `relation_title` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_relations ADD INDEX ( `object_title` )";
		$db->query( $sql, $fname );

		// create attribute table
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

		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `subject_id` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `attribute_title` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_num` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_xsd` )";
		$db->query( $sql, $fname );

		// create table for special properties
		$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_specialprops . '
				( subject_id         INT(8) UNSIGNED NOT NULL,
				  subject_namespace  INT(11) NOT NULL,
				  subject_title      VARCHAR(255) NOT NULL,
				  property_id        SMALLINT NOT NULL,
				  value_string       VARCHAR(255) NOT NULL
				) TYPE=innodb';
		$res = $db->query( $sql, $fname );

		$sql = "ALTER TABLE $smw_specialprops ADD INDEX ( `subject_id` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_specialprops ADD INDEX ( `property_id` )";
		$db->query( $sql, $fname );

		return true;
	}

}

 
?>