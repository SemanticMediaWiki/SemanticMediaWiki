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

		$result = array();
		if ($specialprop === SMW_SP_HAS_CATEGORY) { // category membership
			$sql = 'cl_from=' . $db->addQuotes($subject->getArticleID());
			$res = $db->select( $db->tableName('categorylinks'), 
								'DISTINCT cl_to',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($limit, $offset) );
			// rewrite result as array
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::newFromText($row->cl_to, NS_CATEGORY);
				}
			}
			$db->freeResult($res);
		} else { // "normal" special property
			$sql = 'subject_id=' . $db->addQuotes($subject->getArticleID()) .
				'AND property_id=' . $db->addQuotes($specialprop);
			$res = $db->select( $db->tableName('smw_specialprops'), 
								'value_string',
								$sql, 'SMW::getSpecialValues', $this->getSQLOptions($limit, $offset) );
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
		$db->update($db->tableName('smw_specialprops'), $val_array, $cond_array, 'SMW::changeTitle');
	}

///// Query answering /////

	function getQueryResult(SMWQuery $query) {
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deepder levels

		// Here, the actual SQL query building and execution must happen. Loads of work.
		// For testing purposes, we assume that the outcome is the following array of titles
		// (the eventual query result format is quite certainly different)
		$qr = array(Title::newFromText('Angola'), Title::newFromText('Namibia'));

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
		
		extract( $db->tableNames('smw_relations','smw_attributes','smw_specialprops') );

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