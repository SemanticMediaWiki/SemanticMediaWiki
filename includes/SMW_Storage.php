<?php
/**
 * This file encapsulates methods used for accessing
 * and storing semantic data.
 *
 * @author Markus Krötzsch
 * @author Klaus Lassleben
 */

	/**
	 * This method writes a single relation into the database.
	 * $subject_title and $object_title are expected as title
	 * objects for the respective articles. $rel_name is the
	 * string label of the relation, without namespace label.
	 */
	function smwfStoreRelation($subject_title, $rel_title, $object_title) {
		$fname = 'SMW::StoreRelation';
		$db =& wfGetDB( DB_MASTER );
		
		return $db->insert( $db->tableName('smw_relations'),
		             array( 'subject_id' => $subject_title->getArticleID(),
		                    'subject_namespace' => $subject_title->getNamespace(),
		                    'subject_title' => $subject_title->getDBkey(),
		                    'relation_title' => $rel_title->getDBkey(),
		                    'object_namespace' => $object_title->getNamespace(),
		                    'object_title' => $object_title->getDBkey()),
		             $fname);
	}
	
	/**
	 * This method writes a single relation into the database.
	 * $subject_title is expected as title object of the article. 
	 * $att_name and $att_unit are the string labels of the 
	 * attribute-name, without namespace label, and of its unit.
	 * $att_type is the internal (international) typeid of the 
	 * type, and $value is a value-string.
	 */
	function smwfStoreAttribute($subject_title, $att_title, $att_unit, $att_type, $value, $value_num=NULL) {
		if ($att_type=='') { // fallback to string if no datatype sepcified
			$att_type='string';
		}

		$fname = 'SMW::StoreAttribute';
		$db =& wfGetDB( DB_MASTER );
		
		return $db->insert( $db->tableName('smw_attributes'),
		             array( 'subject_id' => $subject_title->getArticleID(),
		                    'subject_namespace' => $subject_title->getNamespace(),
		                    'subject_title' => $subject_title->getDBkey(),
		                    'attribute_title' => $att_title->getDBkey(),
		                    'value_unit' => $att_unit,
		                    'value_datatype' => $att_type,
		                    'value_xsd' => $value,
		                    'value_num' => $value_num),
		             $fname);
	}
	
	/**
	 * This method writes a single special property into the 
	 * database. $subject_title is expected to be a title
	 * object. $prop_name the index of some special property.
	 * $value is a string. This method also preprocesses some
	 * values, e.g. to remove implied namespace prefixes.
	 */
	function smwfStoreSpecialProperty($subject_title, $prop_name, $value) {
		switch ($prop_name) {
			case SMW_SP_HAS_TYPE: // remove any namespace prefix from $value
				$vtitle = Title::newFromText($value);
				$value = $vtitle->getText();
				break;
			case SMW_SP_IMPORTED_FROM: // don't store this, just used for display
				return true;
		}
		
		$fname = 'SMW::StoreSpecialProperty';
		$db =& wfGetDB( DB_MASTER );

		return $db->insert( $db->tableName('smw_specialprops'),
		             array('subject_id' => $subject_title->getArticleID(),
		                   'subject_namespace' => $subject_title->getNamespace(),
		                   'subject_title' => $subject_title->getDBkey(),
		                   'property_id' => $prop_name,
		                   'value_string' => $value ), 
		             $fname );
	}
	
	/**
	 *  This method cleans up relations for a specified subject.
	 *  The subject is to be given as a title-object.
	 */
	function smwfDeleteRelations($subject_title) {
		$fname = 'SMW::DeleteRelations';
		$db =& wfGetDB( DB_MASTER );
		
		return $db->delete($db->tableName('smw_relations'), 
		                   array('subject_id' => $subject_title->getArticleID()),
		                   $fname);
	}
	
	/**
	 *  This method cleans up attributes for a specified subject.
	 *  The subject is to be given as a title-object.
	 */
	function smwfDeleteAttributes($subject_title)
	{
		$fname = 'SMW::DeleteAttributes';
		$db =& wfGetDB( DB_MASTER );
		
		return $db->delete($db->tableName('smw_attributes'), 
		                   array('subject_id' => $subject_title->getArticleID()),
		                   $fname);
	}
	
	/**
	 *  This method cleans up special properties for a specified subject.
	 *  The subject is to be given as a title-object.
	 */
	function smwfDeleteSpecialProperties($subject_title)
	{
		$fname = 'SMW::DeleteSpecialProperties';
		$db =& wfGetDB( DB_MASTER );
		
		return $db->delete($db->tableName('smw_specialprops'), 
		                   array('subject_id' => $subject_title->getArticleID()),
		                   $fname);
	}
	
	/**
	*  This method fetches relations for a specified subject, relation, and object.
	*  If some (but not all) of these are NULL, the search fetches all triples
	*  matching the remaining values. The result corresponds to the layout of the
	*  database table for relations.
	*/
	function smwfGetRelations($subject_title,$relation_title,$object_title,$fuzzysearch=false)
	{
		$fname = 'SMW::GetRelations';
		$db =& wfGetDB( DB_MASTER );
		
		$sql='';
		if ($subject_title !== NULL) {
			$sql.='subject_id=' . $db->addQuotes($subject_title->getArticleID());
		}
		if ($relation_title !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			if ($fuzzysearch)
				$sql.='relation_title LIKE \'%' . $db->escapeLike($relation_title->getDBkey()) . '%\'';
			else
				$sql.='relation_title =' . $db->addQuotes($relation_title->getDBkey());
		}
		if ($object_title !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			$sql.='object_namespace=' . $db->addQuotes($object_title->getNamespace());
			if ($fuzzysearch)
				$sql.= ' AND object_title LIKE \'%' . $db->escapeLike($object_title->getDBkey()) . '%\'';
			else
				$sql.= ' AND object_title = ' . $db->addQuotes($object_title->getDBkey());
		}
		
		if ($sql=='') {
			return false; //do not execute queries for Everything
		}
		
		$res = $db->select( $db->tableName('smw_relations'),
		                    'subject_id, relation_title, object_namespace, object_title',
		                    $sql, $fname);
		
		$result = array();
		while($row = $db->fetchObject($res)) {
			$result[]=array($row->subject_id,$row->relation_title,$row->object_namespace,$row->object_title);
		}
		$db->freeResult($res);
		
		return $result;
	}
	
	/**
	*  This method fetches relations for a specified subject, attribute, unit, type, and
	*  value. If some (but not all) of these are NULL, the search fetches all triples
	*  matching the remaining values. The result corresponds to the layout of the
	*  database table for attributes.
	*/
	function smwfGetAttributes($subject_title, $att_title, $att_unit, $att_type, $value, $fuzzysearch=false)
	{
		$fname = 'SMW::GetAttributes';
		$db =& wfGetDB( DB_MASTER );
		
		$sql='';
		if ($subject_title !== NULL) {
			$sql.='subject_id=' . $db->addQuotes($subject_title->getArticleID());
		}
		if ($att_title !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			if ($fuzzysearch)
				$sql.='attribute_title LIKE \'%' . $db->escapeLike($att_title->getDBkey()) . '%\'';
			else
				$sql.='attribute_title=' . $db->addQuotes($att_title->getDBkey());
		}
		if ($att_unit !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			$sql.='value_unit=' . $db->addQuotes($att_unit);
		}
		if ($att_type !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			$sql.='value_datatype=' . $db->addQuotes($att_type);
		}
		if ($value !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			if ($fuzzysearch)
				$sql.='value_xsd LIKE \'' . $db->escapeLike($value) . '%\'';
			else
				$sql.='value_xsd = ' . $db->addQuotes($value);
		}
		
		if ($sql=='') {
			return false; //do not execute queries for Everything
		}
		
		$res = $db->select( $db->tableName('smw_attributes'), 
		                    'subject_id, attribute_title, value_unit, value_datatype, value_xsd',
		                    $sql, $fname );
		
		$result = array();
		if($db->numRows( $res ) > 0)
		{
			$row = $db->fetchObject($res);
			while($row)
			{
				$result[]=array($row->subject_id,$row->attribute_title,$row->value_unit,$row->value_datatype,$row->value_xsd);
				$row = $db->fetchObject($res);
			}
		}
		$db->freeResult($res);
		
		return $result;
	}
	
	
	/**
	*  This method fetches special properties for a specified subject, property,
	*  and value. If some (but not all) of these are NULL, the search fetches 
	*  all triples matching the remaining values. The result corresponds to the 
	*  layout of the database table for special properties. Note that property
	*  is an integer index.
	*
	* TODO: Performance (medium): I think all callers of smwfGetSpecialProperties(), so 
	* only use the value_string in element[2], so replace it with new smwfGetSpecialPropertyValues(). (S)
	*/
	function smwfGetSpecialProperties($subject_title,$property,$value)
	{
		$fname = 'SMW::GetSpecialProperties';
		$db =& wfGetDB( DB_MASTER );
		
		$sql='';
		if ($subject_title !== NULL) {
			$sql.='subject_id=' . $db->addQuotes($subject_title->getArticleID());
		}
		if ($property !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			$sql.='property_id=' . $db->addQuotes($property);
		}
		if ($value !== NULL) {
			if ($sql!='') {$sql.=' AND ';}
			$sql.='value_string=' . $db->addQuotes($value);
		}
		
		if ($sql=='') {
			return false; //do not execute queries for Everything
		}
		
		$res = $db->select( $db->tableName('smw_specialprops'), 
		                    'subject_id, property_id, value_string',
		                    $sql, $fname);
		
		$result = array();
		if($db->numRows( $res ) > 0)
		{
			$row = $db->fetchObject($res);
			while($row)
			{
				$result[]=array($row->subject_id,$row->property_id,$row->value_string);
				$row = $db->fetchObject($res);
			}
		}
		$db->freeResult($res);
		
		return $result;
	}
	
	
	/**
	 * This method changes the subject of all data with a specified
	 * subject. It is required for moving annotations together with their
	 * articles. As usual, input values are expected to be titles.
	 * 
	 */
	function smwfMoveAnnotations($old_title,$new_title,$use_ids = false)
	{
		$fname = 'SMW::MoveAnnotations';
		$db =& wfGetDB( DB_MASTER );

		$old_id = $old_title->getArticleID();
		$new_id = $new_title->getArticleID();

		$cond_array = array( 'subject_title' => $old_title->getDBkey(),
		                     'subject_namespace' => $old_title->getNamespace() );
		$val_array  = array( 'subject_title' => $new_title->getDBkey(),
		                     'subject_namespace' => $new_title->getNamespace() );

		// don't do this by default, since the ids you get when moving articles
		// are not the ones from the old article and the new one (in reality, the
		// $old_title refers to the newly generated redirect article, which does 
		// not have the odl id that was stored in the database):
		if ($use_ids === true) {
			if ($old_id != 0) {
				$cond_array['subject_id'] = $old_id;
			}
			if ($new_id != 0) {
				$val_array['subject_id'] = $new_id;
			}
		}

		$db->update($db->tableName('smw_relations'), $val_array, $cond_array, $fname);
		$db->update($db->tableName('smw_attributes'), $val_array, $cond_array, $fname);
		$db->update($db->tableName('smw_specialprops'), $val_array, $cond_array, $fname);

		return true;
	}
	
	
	/**
	 * This method creates database tables to store the triples internally.
	 */
	function smwfMakeSemanticTables() {
		global $wgDBname;

		$fname = 'SMW::MakeSemanticTables';
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

		// create table for special properties
		$sql = 'CREATE TABLE ' . $wgDBname . '.' . $smw_specialprops . '
				( subject_id         INT(8) UNSIGNED NOT NULL,
				  subject_namespace  INT(11) NOT NULL,
				  subject_title      VARCHAR(255) NOT NULL,
				  property_id        SMALLINT NOT NULL,
				  value_string       VARCHAR(255) NOT NULL
				) TYPE=innodb';
		$res = $db->query( $sql, $fname );
		
		//add indexes for new columns
		$sql = "ALTER TABLE $smw_relations ADD INDEX ( `subject_id` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_relations ADD INDEX ( `relation_title` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_relations ADD INDEX ( `object_title` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `subject_id` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `attribute_title` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_num` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_xsd` )";
		$db->query( $sql, $fname );
		$sql = "ALTER TABLE $smw_specialprops ADD INDEX ( `subject_id` )";
		$db->query( $sql, $fname );

		return true;
	}

?>