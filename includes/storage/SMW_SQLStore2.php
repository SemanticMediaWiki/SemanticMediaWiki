<?php
/**
 * New SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWStore
 */

define('SMW_SQL2_SMWIW',':smw'); // virtual "interwiki prefix" for special SMW objects
define('SMW_SQL2_SMWREDIIW',':smw-redi'); // virtual "interwiki prefix" for SMW objects that are redirected

// Constant flags for identifying tables/retrieval types
define('SMW_SQL2_RELS2',1);
define('SMW_SQL2_ATTS2',2);
define('SMW_SQL2_TEXT2',4);
define('SMW_SQL2_SPEC2',8);
define('SMW_SQL2_REDI2',16);
define('SMW_SQL2_NARY2',32); // not really a table, but a retrieval type
define('SMW_SQL2_SUBS2',64);
define('SMW_SQL2_INST2',128);
define('SMW_SQL2_CONC2',256);


/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 *
 * @note Regarding the use of interwiki links in the store, there is currently
 * no support for storing semantic data about interwiki objects, and hence queries
 * that involve interwiki objects really make sense only for them occurring in 
 * object positions. Most methods still use the given input interwiki text as a simple
 * way to filter out results that may be found if an interwiki object is given but a
 * local object of the same name exists. It is currently not planned to support things
 * like interwiki reuse of properties.
 */
class SMWSQLStore2 extends SMWStore {

	/// Cache for SMW IDs, indexed by string keys
	protected $m_ids = array();

	/// Cache for SMWSemanticData objects, indexed by SMW ID
	protected $m_semdata = array();
	/// Like SMWSQLStore2::m_semdata, but containing flags indicating completeness of the SMWSemanticData objs
	protected $m_sdstate = array();

	protected static $in_getSemanticData = 0; /// >0 while getSemanticData runs, used to prevent nested calls from clearing the cache while another call runs and is about to fill it with data

///// Reading methods /////

	function getSemanticData($subject, $filter = false) {
		wfProfileIn("SMWSQLStore2::getSemanticData (SMW)");
		SMWSQLStore2::$in_getSemanticData++;
		$db =& wfGetDB( DB_SLAVE );

		if ( $subject instanceof Title ) {
			$sid = $this->getSMWPageID($subject->getDBkey(),$subject->getNamespace(),$subject->getInterwiki());
			$svalue = SMWDataValueFactory::newTypeIDValue('_wpg');
			$svalue->setValues($subject->getDBkey(), $subject->getNamespace());;
		} elseif ($subject instanceof SMWWikiPageValue) {
			$sid = $this->getSMWPageID($subject->getDBkey(),$subject->getNamespace(),$subject->getInterwiki());
			$svalue = $subject;
		} else {
			$sid = 0;
			$result = NULL;
		}
		if ($sid == 0) { // no data, safe our time
		/// NOTE: we consider redirects for getting $sid, so $sid == 0 also means "no redirects"
			SMWSQLStore2::$in_getSemanticData--;
			wfProfileOut("SMWSQLStore2::getSemanticData (SMW)");
			return isset($svalue)?(new SMWSemanticData($svalue)):NULL;
		}

		if ($filter !== false) { //array as described in docu for SMWStore
			$tasks = 0;
			foreach ($filter as $value) {
				switch ($value) {
					case '_wpg': $tasks = $tasks | SMW_SQL2_RELS2; break;
					case '_txt': case '_cod':
					             $tasks = $tasks | SMW_SQL2_TEXT2; break;
					case '__nry': $tasks = $tasks | SMW_SQL2_NARY2; break;
					case SMW_SP_INSTANCE_OF: $tasks = $tasks | SMW_SQL2_INST2; break;
					case SMW_SP_REDIRECTS_TO: $tasks = $tasks | SMW_SQL2_REDI2; break;
					case SMW_SP_SUBPROPERTY_OF: case SMW_SP_SUBCLASS_OF:
						$tasks = $tasks | SMW_SQL2_SUBS2;
					break;
					case SMW_SP_CONCEPT_DESC: $tasks = $tasks | SMW_SQL2_CONC2; break;
					default:
						if (is_numeric($value)) { // some special property
							$tasks = $tasks | SMW_SQL2_SPEC2;
						} else { // some other "attribute"
							$tasks = $tasks | SMW_SQL2_ATTS2;
						}
				}
			}
		} else {
			$tasks = SMW_SQL2_RELS2 | SMW_SQL2_ATTS2 | SMW_SQL2_TEXT2| SMW_SQL2_SPEC2 | SMW_SQL2_NARY2 | SMW_SQL2_SUBS2 | SMW_SQL2_INST2 | SMW_SQL2_REDI2 | SMW_SQL2_CONC2;
		}
		if ( ($subject->getNamespace() != SMW_NS_PROPERTY) && ($subject->getNamespace() != NS_CATEGORY) ) {
			$tasks = $tasks & ~SMW_SQL2_SUBS2;
		}
		if ($subject->getNamespace() != SMW_NS_CONCEPT) {
			$tasks = $tasks & ~SMW_SQL2_CONC2;
		}

		if (!array_key_exists($sid, $this->m_semdata)) { // new cache entry
			$this->m_semdata[$sid] = new SMWSemanticData($svalue, false);
			$this->m_sdstate[$sid] = $tasks;
		} else { // do only remaining tasks
			$newtasks = $tasks & ~$this->m_sdstate[$sid];
			$this->m_sdstate[$sid] = $this->m_sdstate[$sid] | $tasks;
			$tasks = $newtasks;
		}
		if ( (count($this->m_semdata) > 1000) && (SMWSQLStore2::$in_getSemanticData == 0) ) {
			// prevent memory leak on very long PHP runs
			$this->m_semdata = array($sid => $this->m_semdata[$sid]);
			$this->m_sdstate = array($sid => $this->m_sdstate[$sid]);
		}

		// most types of data suggest rather similar code
		foreach (array(SMW_SQL2_RELS2, SMW_SQL2_ATTS2, SMW_SQL2_TEXT2, SMW_SQL2_INST2, SMW_SQL2_SUBS2, SMW_SQL2_SPEC2, SMW_SQL2_REDI2, SMW_SQL2_CONC2) as $task) {
			if ( !($tasks & $task) ) continue;
			wfProfileIn("SMWSQLStore2::getSemanticData-task$task (SMW)");
			$where = 'p_id=smw_id AND s_id=' . $db->addQuotes($sid);
			switch ($task) {
				case SMW_SQL2_RELS2:
					$from = $db->tableName('smw_rels2') . ' INNER JOIN ' . $db->tableName('smw_ids') . ' AS p ON p_id=p.smw_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS o ON o_id=o.smw_id';
					$select = 'p.smw_title as prop, o.smw_title as title, o.smw_namespace as namespace, o.smw_iw as iw';
					$where = 's_id=' . $db->addQuotes($sid);
				break;
				case SMW_SQL2_ATTS2:
					$from = array('smw_atts2','smw_ids');
					$select = 'smw_title as prop, value_unit as unit, value_xsd as value';
				break;
				case SMW_SQL2_TEXT2:
					$from = array('smw_text2','smw_ids');
					$select = 'smw_title as prop, value_blob as value';
				break;
				case SMW_SQL2_SPEC2:
					$from = 'smw_spec2';
					$select = 'sp_id as prop, value_string as value';
					$where = 's_id=' . $db->addQuotes($sid);
				break;
				case SMW_SQL2_SUBS2:
					$from = array('smw_subs2','smw_ids');
					$select = 'smw_title as value';
					$where = 'o_id=smw_id AND s_id=' . $db->addQuotes($sid);
					$namespace = $subject->getNamespace();
					$specprop = ($namespace==NS_CATEGORY)?SMW_SP_SUBCLASS_OF:SMW_SP_SUBPROPERTY_OF;
				break;
				case SMW_SQL2_REDI2:
					$from = array('smw_redi2','smw_ids');
					$select = 'smw_title as title, smw_namespace as namespace';
					$where = 'o_id=smw_id AND s_title=' . $db->addQuotes($subject->getDBkey()) .
					         ' AND s_namespace=' . $db->addQuotes($subject->getNamespace());
				break;
				case SMW_SQL2_INST2:
					$from = array('smw_inst2','smw_ids');
					$select = 'smw_title as value';
					$where = 'o_id=smw_id AND s_id=' . $db->addQuotes($sid);
				break;
				case SMW_SQL2_CONC2:
					$from = 'smw_conc2';
					$select = 'concept_txt as concept, concept_docu as docu, concept_features as features, concept_size as size, concept_depth as depth';
					$where = 's_id=' . $db->addQuotes($sid);
				break;
			}
			$res = $db->select( $from, $select, $where, 'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				if ($task & (SMW_SQL2_RELS2 | SMW_SQL2_ATTS2 | SMW_SQL2_TEXT2) ) {
					$property = Title::makeTitle(SMW_NS_PROPERTY, $row->prop);
				}
				if ($task == SMW_SQL2_RELS2) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					if ($dv instanceof SMWWikiPagevalue) { // may fail if type was changed!
						$dv->setValues($row->title, $row->namespace, false, $row->iw);
						$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
					}
				} elseif ($task == SMW_SQL2_ATTS2) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setXSDValue($row->value, $row->unit);
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				} elseif ($task == SMW_SQL2_TEXT2) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setXSDValue($row->value, '');
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				} elseif ($task == SMW_SQL2_SPEC2) {
					$dv = SMWDataValueFactory::newSpecialValue($row->prop);
					$dv->setXSDValue($row->value, '');
					$this->m_semdata[$sid]->addSpecialValue($row->prop, $dv);
				} elseif ($task == SMW_SQL2_SUBS2) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->value, $namespace);
					$this->m_semdata[$sid]->addSpecialValue($specprop, $dv);
				} elseif ($task == SMW_SQL2_REDI2) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->title, $row->namespace);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_REDIRECTS_TO, $dv);
				} elseif ($task == SMW_SQL2_INST2) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->value, NS_CATEGORY);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_INSTANCE_OF, $dv);
				} elseif ($task == SMW_SQL2_CONC2) {
					$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_CONCEPT_DESC);
					$dv->setValues($row->concept, $row->docu, $row->features, $row->size, $row->depth);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_CONCEPT_DESC, $dv);
				}
			}
			$db->freeResult($res);
			wfProfileOut("SMWSQLStore2::getSemanticData-task$task (SMW)");
		}

		// nary values
		if ($tasks & SMW_SQL2_NARY2) {
			// here we fetch all relevant data at once, with one call per table
			// requires filling out data for all properties in parallel
			$properties = array(); // property title objects indexed by DBkey
			$ptypes = array(); // arrays of subtypes per property, indexed by DBkey
			$dvs = array(); // datavalue objects, nested array: property DBkey x bnode x Pos

			foreach (array('smw_rels2','smw_atts2','smw_text2') as $table) {
				switch ($table) {
					case 'smw_rels2':
						$sql='SELECT r.o_id AS bnode, prop.smw_title AS prop, pos.smw_title AS pos, o.smw_title AS title, o.smw_namespace AS namespace, o.smw_iw AS iw FROM ' . $db->tableName('smw_rels2') .  ' AS r INNER JOIN ' . $db->tableName('smw_rels2') . ' AS r2 ON r.o_id=r2.s_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS pos ON pos.smw_id=r2.p_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS prop ON prop.smw_id=r.p_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS o ON o.smw_id=r2.o_id WHERE pos.smw_iw=' . $db->addQuotes(SMW_SQL2_SMWIW) . ' AND r.s_id=' . $db->addQuotes($sid);
					break;
					case 'smw_atts2':
						$sql='SELECT r.o_id AS bnode, prop.smw_title AS prop, pos.smw_title AS pos, att.value_unit AS unit, att.value_xsd AS xsd FROM ' . $db->tableName('smw_rels2') . ' AS r INNER JOIN ' . $db->tableName('smw_atts2') . ' AS att ON r.o_id=att.s_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS pos ON pos.smw_id=att.p_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS prop ON prop.smw_id=r.p_id WHERE pos.smw_iw=' . $db->addQuotes(SMW_SQL2_SMWIW) . ' AND r.s_id=' . $db->addQuotes($sid);
					break;
					case 'smw_text2':
						$sql='SELECT r.o_id AS bnode, prop.smw_title AS prop, pos.smw_title AS pos, text.value_blob AS xsd FROM ' . $db->tableName('smw_rels2') . ' AS r INNER JOIN ' . $db->tableName('smw_text2') . ' AS text ON r.o_id=text.s_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS pos ON pos.smw_id=text.p_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS prop ON prop.smw_id=r.p_id WHERE pos.smw_iw=' . $db->addQuotes(SMW_SQL2_SMWIW) . ' AND r.s_id=' . $db->addQuotes($sid);
					break;
				}
				$res = $db->query($sql, 'SMW::getPropertyValues');
				while($row = $db->fetchObject($res)) {
					if ( !array_key_exists($row->prop,$properties) ) {
						$properties[$row->prop] = Title::makeTitle(SMW_NS_PROPERTY,$row->prop);
						$type = SMWDataValueFactory::getPropertyObjectTypeValue($properties[$row->prop]);
						$ptypes[$row->prop] = $type->getTypeValues();
						$dvs[$row->prop] = array();
					}
					$pos = intval($row->pos);
					if ($pos >= count($ptypes[$row->prop])) continue; // out of range, maybe some old data that still waits for update
					if (!array_key_exists($row->bnode,$dvs[$row->prop])) {
						$dvs[$row->prop][$row->bnode] = array();
						for ($i=0; $i < count($ptypes[$row->prop]); $i++) { // init array
							$dvs[$row->prop][$row->bnode][$i] = NULL;
						}
					}
					$dv = SMWDataValueFactory::newTypeObjectValue($ptypes[$row->prop][$pos]);
					switch ($table) {
						case 'smw_rels2':
							$dv->setValues($row->title, $row->namespace);
						break;
						case 'smw_atts2':
							$dv->setXSDValue($row->xsd, $row->unit);
						break;
						case 'smw_text2':
							$dv->setXSDValue($row->xsd, '');
						break;
					}
					$dvs[$row->prop][$row->bnode][$pos] = $dv;
				}
				$db->freeResult($res);
			}
			
			foreach ($properties as $name => $property) {
				$pdvs = $dvs[$name];
				foreach ($pdvs as $bnode => $values) {
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
					$dv->setDVs($values);
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				}
			}
		}

		SMWSQLStore2::$in_getSemanticData--;
		wfProfileOut("SMWSQLStore2::getSemanticData (SMW)");
		return $this->m_semdata[$sid];
	}

	function getSpecialValues(Title $subject, $specialprop, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getSpecialValues-$specialprop (SMW)");

		if ($subject !== NULL) {
			$sid = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),$subject->getInterwiki());
		}
		if ( ($sid == 0) && ($specialprop != SMW_SP_REDIRECTS_TO)) {
			/// NOTE: SMW_SP_REDIRECTS_TO is the only property that objects without an SMW-ID may have
			wfProfileOut("SMWSQLStore2::getSpecialValues-$specialprop (SMW)");
			return array();
		}
		$sd = $this->getSemanticData($subject,array($specialprop));
		$result = $this->applyRequestOptions($sd->getPropertyValues($specialprop),$requestoptions);
		wfProfileOut("SMWSQLStore2::getSpecialValues-$specialprop (SMW)");
		return $result;
	}

	function getSpecialSubjects($specialprop, SMWDataValue $value, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getSpecialSubjects-$specialprop (SMW)");
		$db =& wfGetDB( DB_SLAVE );

		$result = array();
		/// NOTE: We expect the given SMWDataValue to have the appropriate type for the special 
		/// property that is queried. There is some dependency between the store's assumptions and
		/// the types returned for special properties by SMWDataValueFactory. But the type alone 
		/// would always be too little, since the store uses custom tables for many special properties.

		if ($specialprop === SMW_SP_INSTANCE_OF) { // class membership
			$oid = $this->getSMWPageID($value->getDBkey(),NS_CATEGORY,$value->getInterwiki());
			if ( ($oid != 0) && ($value->getNamespace() == NS_CATEGORY) ) {
				$res = $db->select( array('smw_inst2','smw_ids'), 'smw_title,smw_namespace,smw_sortkey',
				                    's_id=smw_id AND o_id=' . $db->addQuotes($oid), 
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->smw_title, $row->smw_namespace, false, '', $row->smw_sortkey);
					$result[] = $dv;
				}
				$db->freeResult($res);
			}
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki(),false);
			/// NOTE: we do not use the canonical (redirect-aware) id here!
			/// NOTE: we ignore sortkeys here -- this appears to be ok
			if ($oid != 0) {
				$res = $db->select( array('smw_redi2'), 's_title,s_namespace',
				                    'o_id=' . $db->addQuotes($oid),
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->s_title, $row->s_namespace);
					$result[] = $dv;
				}
				$db->freeResult($res);
			}
		} elseif ( ($specialprop === SMW_SP_SUBPROPERTY_OF) || ($specialprop === SMW_SP_SUBCLASS_OF) ) { 
			// subproperties/subclasses
			$namespace = ($specialprop === SMW_SP_SUBCLASS_OF)?NS_CATEGORY:SMW_NS_PROPERTY;
			$oid = $this->getSMWPageID($value->getDBkey(),$namespace,$value->getInterwiki());
			if ( ($oid != 0) && ($value->getNamespace() == $namespace) ) {
				$res = $db->select( array('smw_subs2','smw_ids'), array('smw_title','smw_sortkey'),
				                    's_id=smw_id AND o_id=' . $db->addQuotes($oid) . $this->getSQLConditions($requestoptions, 'smw_sortkey', 'smw_sortkey'),
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions, 'smw_sortkey') );
				while($row = $db->fetchObject($res)) {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
					$dv->setValues($row->smw_title, $namespace, false, '', $row->smw_sortkey);
					$result[] = $dv;
				}
				$db->freeResult($res);
			}
		} elseif ($specialprop === SMW_SP_CONCEPT_DESC) {
			// no inverse search for concept descriptions (blobs)
		} else {
			if ($value->getXSDValue() !== false) { // filters out error-values etc.
				$stringvalue = $value->getXSDValue();
			} else {
				wfProfileOut("SMWSQLStore2::getSpecialSubjects-$specialprop (SMW)");
				return array();
			}
			$sql = 'smw_id=s_id AND sp_id=' . $db->addQuotes($specialprop) .
			       ' AND value_string=' . $db->addQuotes($stringvalue) .
			       $this->getSQLConditions($requestoptions,'smw_sortkey','smw_sortkey');
			$res = $db->select( array('smw_spec2','smw_ids'), 'DISTINCT smw_title,smw_namespace,smw_sortkey',
			                    $sql, 'SMW::getSpecialSubjects', 
			                    $this->getSQLOptions($requestoptions,'smw_sortkey') );
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->smw_title, $row->smw_namespace, false, '', $row->smw_sortkey);
				$result[] = $dv;
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore2::getSpecialSubjects-$specialprop (SMW)");
		return $result;
	}


	function getPropertyValues($subject, $property, $requestoptions = NULL, $outputformat = '') {
		wfProfileIn("SMWSQLStore2::getPropertyValues (SMW)");
		if ($subject !== NULL) {
			$sid = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),$subject->getInterwiki());
		} else {
			$sid = 0;
		}
		$pid = $this->getSMWPageID($property->getDBkey(), SMW_NS_PROPERTY, $property->getInterwiki());
		if ( ( ($sid == 0) && ($subject !== NULL) ) || ($pid == 0)) {
			wfProfileOut("SMWSQLStore2::getPropertyValues (SMW)");
			return array();
		}

		if ($sid != 0) { // subject given, use semantic data cache:
			$sd = $this->getSemanticData($subject,array(SMWDataValueFactory::getPropertyObjectTypeID($property)));
			$result = $this->applyRequestOptions($sd->getPropertyValues($property),$requestoptions);
			if ($outputformat != '') { // reformat cached values
				$newres = array();
				foreach ($result as $dv) {
					$ndv = clone $dv;
					$ndv->setOutputFormat($outputformat);
					$newres[] = $ndv;
				}
				$result = $newres;
			}
		} else { // no subject given, get all values for the given property
			$db =& wfGetDB( DB_SLAVE );
			$result = array();
			$id = SMWDataValueFactory::getPropertyObjectTypeID($property);
			switch ($id) {
				case '_txt': case '_cod':
					$res = $db->select( 'smw_text2', 'value_blob',
										'p_id=' . $db->addQuotes($pid),
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
					$res = $db->select( array('smw_rels2', 'smw_ids'),
										'smw_namespace, smw_title, smw_iw',
										'p_id=' . $db->addQuotes($pid) . ' AND o_id=smw_id' .
										$this->getSQLConditions($requestoptions,'smw_sortkey','smw_sortkey'),
										'SMW::getPropertyValues', $this->getSQLOptions($requestoptions,'smw_sortkey') );
					while($row = $db->fetchObject($res)) {
						$dv = SMWDataValueFactory::newPropertyObjectValue($property);
						$dv->setOutputFormat($outputformat);
						$dv->setValues($row->smw_title, $row->smw_namespace, false, $row->smw_iw);
						$result[] = $dv;
					}
					$db->freeResult($res);
				break;
				case '__nry': ///TODO: currently disabled
// 					$type = SMWDataValueFactory::getPropertyObjectTypeValue($property);
// 					$subtypes = $type->getTypeValues();
// 					$res = $db->select( $db->tableName('smw_nary'),
// 										'nary_key',
// 										$subjectcond .
// 										'attribute_title=' . $db->addQuotes($property->getDBkey()),
// 										'SMW::getPropertyValues', $this->getSQLOptions($requestoptions) );
// 					///TODO: presumably slow. Try to do less SQL queries by making a join with smw_nary
// 					while($row = $db->fetchObject($res)) {
// 						$values = array();
// 						for ($i=0; $i < count($subtypes); $i++) { // init array
// 							$values[$i] = NULL;
// 						}
// 						$res2 = $db->select( $db->tableName('smw_nary_attributes'),
// 										'nary_pos, value_unit, value_xsd',
// 										$subjectcond .
// 										'nary_key=' . $db->addQuotes($row->nary_key),
// 										'SMW::getPropertyValues');
// 						while($row2 = $db->fetchObject($res2)) {
// 							if ($row2->nary_pos < count($subtypes)) {
// 								$dv = SMWDataValueFactory::newTypeObjectValue($subtypes[$row2->nary_pos]);
// 								$dv->setXSDValue($row2->value_xsd, $row2->value_unit);
// 								$values[$row2->nary_pos] = $dv;
// 							}
// 						}
// 						$db->freeResult($res2);
// 						$res2 = $db->select( $db->tableName('smw_nary_longstrings'),
// 										'nary_pos, value_blob',
// 										$subjectcond .
// 										'nary_key=' . $db->addQuotes($row->nary_key),
// 										'SMW::getPropertyValues');
// 						while($row2 = $db->fetchObject($res2)) {
// 							if ( $row2->nary_pos < count($subtypes) ) {
// 								$dv = SMWDataValueFactory::newTypeObjectValue($subtypes[$row2->nary_pos]);
// 								$dv->setXSDValue($row2->value_blob, '');
// 								$values[$row2->nary_pos] = $dv;
// 							}
// 						}
// 						$db->freeResult($res2);
// 						$res2 = $db->select( $db->tableName('smw_nary_relations'),
// 										'nary_pos, object_title, object_namespace, object_id',
// 										$subjectcond .
// 										'nary_key=' . $db->addQuotes($row->nary_key),
// 										'SMW::getPropertyValues');
// 						while($row2 = $db->fetchObject($res2)) {
// 							if ( ($row2->nary_pos < count($subtypes)) &&
// 								($subtypes[$row2->nary_pos]->getXSDValue() == '_wpg') ) {
// 								$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
// 								$dv->setValues($row2->object_title, $row2->object_namespace, $row2->object_id);
// 								$values[$row2->nary_pos] = $dv;
// 							}
// 						}
// 						$db->freeResult($res2);
// 						$dv = SMWDataValueFactory::newPropertyObjectValue($property);
// 						$dv->setOutputFormat($outputformat);
// 						$dv->setDVs($values);
// 						$result[] = $dv;
// 					}
// 					$db->freeResult($res);
				break;
				default:
					if ( ($requestoptions !== NULL) && ($requestoptions->boundary !== NULL) &&
						($requestoptions->boundary->isNumeric()) ) {
						$value_column = 'value_num';
					} else {
						$value_column = 'value_xsd';
					}
					$sql = 'p_id=' . $db->addQuotes($pid) .
						$this->getSQLConditions($requestoptions,$value_column,'value_xsd');
					$res = $db->select( 'smw_atts2', 'value_unit, value_xsd',
										'p_id=' . $db->addQuotes($pid) .
										$this->getSQLConditions($requestoptions,$value_column,'value_xsd'),
										'SMW::getPropertyValues', $this->getSQLOptions($requestoptions,$value_column) );
					while($row = $db->fetchObject($res)) {
						$dv = SMWDataValueFactory::newPropertyObjectValue($property);
						$dv->setOutputFormat($outputformat);
						$dv->setXSDValue($row->value_xsd, $row->value_unit);
						$result[] = $dv;
					}
					$db->freeResult($res);
			}
		}
		wfProfileOut("SMWSQLStore2::getPropertyValues (SMW)");
		return $result;
	}

	function getPropertySubjects(Title $property, $value, $requestoptions = NULL) {
		/// TODO: should we share code with #ask query computation here? Just use queries?
		wfProfileIn("SMWSQLStore2::getPropertySubjects (SMW)");
		$result = array();
		$pid = $this->getSMWPageID($property->getDBkey(), $property->getNamespace(),$property->getInterwiki());
		if ( ($pid == 0) || ( ($value !== NULL) && (!$value->isValid()) ) ) {
			wfProfileOut("SMWSQLStore2::getPropertySubjects (SMW)");
			return $result;
		}
		$db =& wfGetDB( DB_SLAVE );
		$table = '';
		$sql = 'p_id=' . $db->addQuotes($pid);
		if ($value === NULL) {
			$typeid = SMWDataValueFactory::getPropertyObjectTypeID($property);
		} else {
			$typeid = $value->getTypeID();
		}

		switch ($typeid) {
		case '_txt': case '_cod':
			$table = 'smw_text2'; // ignore value condition in any case
		break;
		case '_wpg': // wikipage
			if ($value !== NULL) {
				$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki());
				$sql .= ' AND o_id=' . $db->addQuotes($oid);
			}
			if ( ($value === NULL) || ($oid != 0) ) {
				$table = 'smw_rels2';
			}
		break;
		case '__nry':
			if ($value === NULL) { // no value -- handled just like for wikipage
				$table = 'smw_rels2';
				break;
			}
			$values = $value->getDVs();
			$smw_rels2 = $db->tableName('smw_rels2');
			$smw_ids = $db->tableName('smw_ids');
			// build a single SQL query for that
			$where = "t.p_id=" . $db->addQuotes($pid);
			$from = "$smw_rels2 AS t INNER JOIN $smw_ids AS i ON t.s_id=i.smw_id";
			$count = 0;
			foreach ($values as $dv) {
				if ( ($dv === NULL) || (!$dv->isValid()) ) {
					$count++;
					continue;
				}
				$npid = $this->makeSMWPageID(strval($count),SMW_NS_PROPERTY,SMW_SQL2_SMWIW); // might be cached
				switch ($dv->getTypeID()) {
				case '_txt': case '_cod': break; // not supported
				case '_wpg':
					$from .= " INNER JOIN $smw_rels2 AS t$count ON t.o_id=t$count.s_id INNER JOIN $smw_ids AS i$count ON t$count.o_id=i$count.smw_id";
					$where .= " AND t$count.p_id=" . $db->addQuotes($npid) .
					          " AND i$count.smw_title=" . $db->addQuotes($dv->getDBkey()) .
					          " AND i$count.smw_namespace=" . $db->addQuotes($dv->getNamespace()) .
					          " AND i$count.smw_iw=" . $db->addQuotes('');
				break;
				default:
					$from .= ' INNER JOIN ' . $db->tableName('smw_atts2') . " AS t$count ON t.o_id=t$count.s_id";
					$where .= " AND t$count.p_id=" . $db->addQuotes($npid) .
					          " AND t$count.value_xsd=" . $db->addQuotes($dv->getXSDValue()) .
					          " AND t$count.value_unit=" . $db->addQuotes($dv->getUnit());
				}
				$count++;
			}
			$res = $db->query("SELECT DISTINCT i.smw_title AS title,i.smw_namespace AS namespace,i.smw_sortkey AS sortkey FROM $from WHERE $where", 'SMW::getPropertySubjects', $this->getSQLOptions($requestoptions,'smw_sortkey'));
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->title, $row->namespace, false, '', $row->sortkey);
				$result[] = $dv;
			}
			$db->freeResult($res);
		break;
		default:
			$table = 'smw_atts2';
			if ($value !== NULL) {
				$sql .= ' AND value_xsd=' . $db->addQuotes($value->getXSDValue()) .
				        ' AND value_unit=' . $db->addQuotes($value->getUnit());
			}
		break;
		}

		if ($table != '') {
			$res = $db->select( array($table,'smw_ids'),
			                    'DISTINCT smw_title,smw_namespace,smw_sortkey',
			                    's_id=smw_id AND ' . $sql . $this->getSQLConditions($requestoptions,'smw_sortkey','smw_sortkey'), 'SMW::getPropertySubjects',
			                    $this->getSQLOptions($requestoptions,'smw_sortkey') );
			while($row = $db->fetchObject($res)) {
				$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				$dv->setValues($row->smw_title, $row->smw_namespace, false, '', $row->smw_sortkey);
				$result[] = $dv;
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore2::getPropertySubjects (SMW)");
		return $result;
	}

	function getAllPropertySubjects(Title $property, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getAllPropertySubjects (SMW)");
		$result = $this->getPropertySubjects($property, NULL, $requestoptions);
		wfProfileOut("SMWSQLStore2::getAllPropertySubjects (SMW)");
		return $result;
	}

	function getProperties(Title $subject, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getProperties (SMW)");
		$sid = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),$subject->getInterwiki());
		if ($sid == 0) {
			wfProfileOut("SMWSQLStore2::getProperties (SMW)");
			return array();
		}

		$db =& wfGetDB( DB_SLAVE );
		$sql = 's_id=' . $db->addQuotes($sid) . ' AND p_id=smw_id' . $this->getSQLConditions($requestoptions,'smw_sortkey','smw_sortkey');

		$result = array();
		// NOTE: the following also includes naries, which are now kept in smw_rels2
		foreach (array('smw_atts2','smw_text2','smw_rels2') as $table) {
			$res = $db->select( array($table,'smw_ids'), 'DISTINCT smw_title',
			                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'smw_sortkey') );
			if ($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->smw_title);
				}
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore2::getProperties (SMW)");
		return $result;
	}

	function getInProperties(SMWDataValue $value, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getInProperties (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$result = array();
		if ($value->getTypeID() == '_wpg') {
			$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki());
			$sql = 'p_id=smw_id AND o_id=' . $db->addQuotes($oid) .
			       ' AND smw_iw=' . $db->addQuotes('') . // only local, non-internal properties
			       $this->getSQLConditions($requestoptions,'smw_sortkey','smw_sortkey');
			$res = $db->select( array('smw_rels2','smw_ids'), 'DISTINCT smw_title',
			                    $sql, 'SMW::getInProperties', $this->getSQLOptions($requestoptions,'smw_sortkey') );
			while($row = $db->fetchObject($res)) {
				$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->smw_title);
			}
			$db->freeResult($res);
		}
		wfProfileOut("SMWSQLStore2::getInProperties (SMW)");
		return $result;
	}

///// Writing methods /////

	function deleteSubject(Title $subject) {
		wfProfileIn('SMWSQLStore2::deleteSubject (SMW)');
		$this->deleteSemanticData($subject);
		$this->updateRedirects($subject->getDBkey(), $subject->getNamespace()); // also delete redirects, may trigger update jobs!
		if ($subject->getNamespace() == SMW_NS_CONCEPT) { // make sure to clear caches
			$db =& wfGetDB( DB_MASTER );
			$id = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),$subject->getInterwiki(),false);
			$db->delete('smw_conc2', array('s_id' => $id), 'SMW::deleteSubject::Conc2');
			$db->delete('smw_conccache', array('o_id' => $id), 'SMW::deleteSubject::Conccache');
		}
		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///TODO: Possibly delete ID here (at least for non-properties/categories, if not used in any place in rels2)
		///FIXME: clean internal caches here
		wfProfileOut('SMWSQLStore2::deleteSubject (SMW)');
	}

	function updateData(SMWSemanticData $data, $newpage) {
		wfProfileIn("SMWSQLStore2::updateData (SMW)");
		$subject = $data->getSubject();
		$this->deleteSemanticData($subject);
		$redirects = $data->getPropertyValues(SMW_SP_REDIRECTS_TO);
		if (count($redirects) > 0) {
			$redirect = end($redirects); // at most one redirect per page
			$this->updateRedirects($subject->getDBKey(), $subject->getNamespace(), $redirect->getDBKey(), $redirect->getNameSpace());
			wfProfileOut("SMWSQLStore2::updateData (SMW)");
			return; // stop here -- no support for annotations on redirect pages!
		} else {
			$this->updateRedirects($subject->getDBKey(),$subject->getNamespace());
		}
		// always make an ID (pages without ID cannot be in query results, not even in fixed value queries!):
		$sid = $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),'',true,$subject->getSortkey());
		$db =& wfGetDB( DB_MASTER );

		// do bulk updates:
		$up_rels2 = array();  $up_atts2 = array();
		$up_text2 = array();  $up_spec2 = array();
		$up_subs2 = array();  $up_inst2 = array();

		$concept_desc = NULL;

		//properties
		foreach($data->getProperties() as $key => $property) {
			$propertyValueArray = $data->getPropertyValues($property);
			if ($property instanceof Title) { // normal property
				foreach($propertyValueArray as $value) {
					if ($value->isValid()) {
						if ( ($value->getTypeID() == '_txt') || ($value->getTypeID() == '_cod') ){
							$up_text2[] =
								array( 's_id' => $sid,
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'value_blob' => $value->getXSDValue() );
						} elseif ($value->getTypeID() == '_wpg') {
							$up_rels2[] =
								array( 's_id' => $sid,
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'o_id' => $this->makeSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki()) );
						} elseif ($value->getTypeID() == '__nry') {
							$bnode = $this->makeSMWBnodeID($sid);
							$up_rels2[] =
								array( 's_id' => $sid,
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'o_id' => $bnode );
							$npos = 0;
							foreach ($value->getDVs() as $dv) {
								if ( ($dv !== NULL) && ($dv->isValid()) ) {
									$pid = $this->makeSMWPageID(strval($npos),SMW_NS_PROPERTY,SMW_SQL2_SMWIW);
									switch ($dv->getTypeID()) {
									case '_wpg':
										$up_rels2[] =
											array( 's_id' => $bnode,
											       'p_id' => $pid,
											       'o_id' => $this->makeSMWPageID($dv->getDBkey(),$dv->getNamespace(),$dv->getInterwiki()) );
									break;
									case '_txt': case '_cod':
										$up_text2[] =
											array( 's_id' => $bnode,
											       'p_id' => $pid,
											       'value_blob' => $dv->getXSDValue() );
									break;
									default:
										$up_atts2[] =
											array( 's_id' => $bnode,
											       'p_id' => $pid,
											       'value_unit' => $dv->getUnit(),
											       'value_xsd' => $dv->getXSDValue(),
											       'value_num' => $dv->getNumericValue() );
									}
								}
								$npos++;
							}
						} else {
							$up_atts2[] =
								array( 's_id' => $sid,
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'value_unit' => $value->getUnit(),
								       'value_xsd' => $value->getXSDValue(),
								       'value_num' => $value->getNumericValue() );
						}
					}
				}
			} else { // special property
				switch ($property) {
					case SMW_SP_REDIRECTS_TO: // handled above
					break;
					case SMW_SP_INSTANCE_OF:
						foreach($propertyValueArray as $value) {
							if ( ($value->isValid()) && ($value->getNamespace() == NS_CATEGORY) )  {
								$up_inst2[] =
								array('s_id' => $sid,
								      'o_id' => $this->makeSMWPageID($value->getDBkey(),$value->getNamespace(),''));
							}
						}
					break;
					case SMW_SP_SUBPROPERTY_OF: case SMW_SP_SUBCLASS_OF:
						$namespace = ($property==SMW_SP_SUBPROPERTY_OF)?SMW_NS_PROPERTY:NS_CATEGORY;
						if ( $subject->getNamespace() != $namespace ) {
							break;
						}
						foreach($propertyValueArray as $value) {
							if ( ($value->isValid()) && ($value->getNamespace() == $namespace) )  {
								$up_subs2[] =
								array('s_id' => $sid,
								      'o_id' => $this->makeSMWPageID($value->getDBkey(),$value->getNamespace(),''));
							}
						}
					break;
					case SMW_SP_CONCEPT_DESC: // textual concept description
						$concept_desc = end($propertyValueArray); // only one value per page!
					break;
					default: // normal special value
						foreach($propertyValueArray as $value) {
							if ($value->isValid()) { // filters out error-values etc.
								$stringvalue = $value->getXSDValue();
								$up_spec2[] =
								array('s_id' => $sid,
								      'sp_id' => $property,
								      'value_string' => $stringvalue);
							}
						}
					break;
				}
			}
		}

		// write to DB:
		if (count($up_rels2) > 0) {
			$db->insert( 'smw_rels2', $up_rels2, 'SMW::updateRel2Data');
		}
		if (count($up_atts2) > 0) {
			$db->insert( 'smw_atts2', $up_atts2, 'SMW::updateAtt2Data');
		}
		if (count($up_text2) > 0) {
			$db->insert( 'smw_text2', $up_text2, 'SMW::updateText2Data');
		}
		if (count($up_spec2) > 0) {
			$db->insert( 'smw_spec2', $up_spec2, 'SMW::updateSpec2Data');
		}
		if (count($up_subs2) > 0) {
			$db->insert( 'smw_subs2', $up_subs2, 'SMW::updateSubs2Data');
		}
		if (count($up_inst2) > 0) {
			$db->insert( 'smw_inst2', $up_inst2, 'SMW::updateInst2Data');
		}
		// Concepts are not just written but carefully updated,
		// preserving existing metadata (cache ...) for a concept:
		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) {
			if ( ($concept_desc !== NULL) && ($concept_desc->isValid()) )  {
				$up_conc2 = array(
				     'concept_txt'   => $concept_desc->getXSDValue(),
				     'concept_docu'  => $concept_desc->getDocu(),
				     'concept_features' => $concept_desc->getQueryFeatures(),
				     'concept_size'  => $concept_desc->getSize(),
				     'concept_depth' => $concept_desc->getDepth()
				);
			} else {
				$up_conc2 = array(
				     'concept_txt'   => '',
				     'concept_docu'  => '',
				     'concept_features' => 0,
				     'concept_size'  => -1,
				     'concept_depth' => -1
				);
			}
			$row = $db->selectRow('smw_conc2', array('cache_date','cache_count'), array('s_id'=>$sid), 'SMWSQLStore2Queries::updateConst2Data');
			if ( ($row === false) && ($up_conc2['concept_txt'] != '') ) { // insert newly given data
				$up_conc2['s_id'] = $sid;
				$db->insert( 'smw_conc2', $up_conc2, 'SMW::updateConc2Data');
			} elseif ($row !== false) { // update data, preserve existing entries
				$db->update('smw_conc2',$up_conc2, array('s_id'=>$sid), 'SMW::updateConc2Data');
			}
		}

		$this->m_semdata[$sid] = clone $data; // update cache, important if jobs are directly following this call
		$this->m_sdstate[$sid] = 0xFFFFFFFF; // everything that one can know
		if ($subject->getNamespace() == SMW_NS_PROPERTY) { // be sure that this is not invalid after update
			SMWDataValueFactory::clearTypeCache($subject->getTitle());
		}

		wfProfileOut("SMWSQLStore2::updateData (SMW)");
	}

	function changeTitle(Title $oldtitle, Title $newtitle, $pageid, $redirid=0) {
		wfProfileIn("SMWSQLStore2::changeTitle (SMW)");
		///NOTE: this function ignores the given MediaWiki IDs (this store has its own IDs)
		///NOTE: this function assumes input titles to be local (no interwiki). Anything else would be too gross.
		$sid_c = $this->getSMWPageID($oldtitle->getDBKey(),$oldtitle->getNamespace(),'');
		$sid = $this->getSMWPageID($oldtitle->getDBKey(),$oldtitle->getNamespace(),'',false);
		$tid_c = $this->getSMWPageID($newtitle->getDBKey(),$newtitle->getNamespace(),'');
		$tid = $this->getSMWPageID($newtitle->getDBKey(),$newtitle->getNamespace(),'',false);

		$db =& wfGetDB( DB_MASTER );

		if ($tid_c == 0) { // target not used anywhere yet, just hijack its title for our current id
			/// NOTE: given our lazy id management, this condition may not hold, even if $newtitle is an unused new page
			if ($sid != 0) { // move only if id exists at all
				$cond_array = array( 'smw_id' => $sid );
				$val_array  = array( 'smw_title' => $newtitle->getDBkey(),
				                     'smw_namespace' => $newtitle->getNamespace());
				$db->update('smw_ids', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
			} else { // make new (target) id for use in redirect table
				$sid = $this->makeSMWPageID($newtitle->getDBKey(),$newtitle->getNamespace(),''); // make target id
			} // at this point, $sid is the id of the target page (according to smw_ids)
			$this->makeSMWPageID($oldtitle->getDBKey(),$oldtitle->getNamespace(),SMW_SQL2_SMWREDIIW); // make redirect id for oldtitle
			// update redirects
			/// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
			/// redirects are not supported by MW or SMW, the below is maximally correct there too.
			$db->insert( 'smw_redi2', array('s_title'=>$oldtitle->getDBkey(), 's_namespace'=>$oldtitle->getNamespace(), 'o_id'=>$sid), 'SMWSQLStore2::changeTitle');
			/// NOTE: this temporarily leaves existing redirects to oldtitle point to newtitle as well, which
			/// will be lost after the next update. Since double redirects are an error anyway, this is not
			/// a bad behaviour: everything will continue to work until the old redirect is updated, which 
			/// will hopefully be to fix the double redirect.
		} else {
			$this->deleteSemanticData($newtitle); // should not have much effect, but let's be sure
			$this->updateRedirects($newtitle->getDBkey(), $newtitle->getNamespace()); // delete these redirects, may trigger update jobs!
			$this->updateRedirects($oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace());
			// also move subject data along (updateRedirects only cares about changes in objects/properties)
			if ($sid != 0) {
				$cond_array = array( 's_id' => $sid );
				$val_array  = array( 's_id' => $tid );
				$db->update('smw_rels2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
				$db->update('smw_atts2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
				$db->update('smw_text2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
				$db->update('smw_inst2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
				if ( ( $oldtitle->getNamespace() == SMW_NS_PROPERTY ) && 
				     ( $newtitle->getNamespace() == SMW_NS_PROPERTY ) ) {
					$db->update('smw_subs2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
				} elseif ($oldtitle->getNamespace() == SMW_NS_PROPERTY) {
					$db->delete('smw_subs2', $cond_array, 'SMWSQLStore2::changeTitle');
				} elseif ( ( $oldtitle->getNamespace() == NS_CATEGORY ) && 
				           ( $newtitle->getNamespace() == NS_CATEGORY ) ) {
					$db->update('smw_subs2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
				} elseif ($oldtitle->getNamespace() == NS_CATEGORY) {
					$db->delete('smw_subs2', $cond_array, 'SMWSQLStore2::changeTitle');
				} elseif ( ( $oldtitle->getNamespace() == SMW_NS_CONCEPT ) && 
				           ( $newtitle->getNamespace() == SMW_NS_CONCEPT ) ) {
					$db->update('smw_conc2', $val_array, $cond_array, 'SMWSQLStore2::changeTitle');
					$db->update('smw_conccache', array('o_id' => $tid), array('o_id' => $sid), 'SMWSQLStore2::changeTitle');
				} elseif ($oldtitle->getNamespace() == SMW_NS_CONCEPT) {
					$db->delete('smw_conc2', $cond_array, 'SMWSQLStore2::changeTitle');
					$db->delete('smw_conccache', array('o_id' => $sid), 'SMWSQLStore2::changeTitle');
				}
			}
			/// TODO: may not be optimal for the standard case that newtitle existed and redirected to oldtitle (PERFORMANCE)
		}

		wfProfileOut("SMWSQLStore2::changeTitle (SMW)");
	}

///// Query answering /////

	function getQueryResult(SMWQuery $query) {
		wfProfileIn('SMWSQLStore2::getQueryResult (SMW)');
		global $smwgIP;
		include_once("$smwgIP/includes/storage/SMW_SQLStore2_Queries.php");
		$qe = new SMWSQLStore2QueryEngine($this,wfGetDB( DB_SLAVE ));
		$result = $qe->getQueryResult($query);
		wfProfileOut('SMWSQLStore2::getQueryResult (SMW)');
		return $result;
	}

///// Special page functions /////

	function getPropertiesSpecial($requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getPropertiesSpecial (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$options = ' ORDER BY smw_title';
		if ($requestoptions->limit > 0) {
			$options .= ' LIMIT ' . $requestoptions->limit;
		}
		if ($requestoptions->offset > 0) {
			$options .= ' OFFSET ' . $requestoptions->offset;
		}
		// NOTE: the query needs to do the fitlering of internal properties, else LIMIT is wrong
		$res = $db->query('(SELECT smw_title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_rels2') . ' INNER JOIN ' . $db->tableName('smw_ids') . ' ON p_id=smw_id WHERE smw_iw=' . $db->addQuotes('') . ' GROUP BY p_id) UNION ' .
		                  '(SELECT smw_title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_atts2') . ' INNER JOIN ' . $db->tableName('smw_ids') . ' ON p_id=smw_id WHERE smw_iw=' . $db->addQuotes('') . ' GROUP BY p_id) UNION ' .
		                  '(SELECT smw_title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_text2') . ' INNER JOIN ' . $db->tableName('smw_ids') . ' ON p_id=smw_id WHERE smw_iw=' . $db->addQuotes('') . ' GROUP BY p_id)' . $options,
		                  'SMW::getPropertySubjects');
		$result = array();
		while($row = $db->fetchObject($res)) {
			$title = Title::makeTitle(SMW_NS_PROPERTY, $row->smw_title);
			$result[] = array($title, $row->count);
		}
		$db->freeResult($res);
		wfProfileOut("SMWSQLStore2::getPropertiesSpecial (SMW)");
		return $result;
	}

	function getUnusedPropertiesSpecial($requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getUnusedPropertiesSpecial (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		/// TODO: some db-calls in here can use better wrapper functions,
		/// make an options array for those and use them
		$options = ' ORDER BY title';
		if ($requestoptions->limit > 0) {
			$options .= ' LIMIT ' . $requestoptions->limit;
		}
		if ($requestoptions->offset > 0) {
			$options .= ' OFFSET ' . $requestoptions->offset;
		}
		extract( $db->tableNames('page', 'smw_rels2', 'smw_atts2', 'smw_text2', 'smw_subs2', 'smw_ids', 'smw_tmp_unusedprops', 'smw_redi2') );

		$db->query( "CREATE TEMPORARY TABLE $smw_tmp_unusedprops" .
		            ' ( title VARCHAR(255) ) TYPE=MEMORY', 'SMW::getUnusedPropertiesSpecial' );
		$db->query( "INSERT INTO $smw_tmp_unusedprops SELECT page_title FROM $page" .
		            " WHERE page_namespace=" . SMW_NS_PROPERTY , 'SMW::getUnusedPropertySubjects');
		foreach (array($smw_rels2,$smw_atts2,$smw_text2) as $table) {
			$db->query( "DELETE $smw_tmp_unusedprops.* FROM $smw_tmp_unusedprops, $table INNER JOIN $smw_ids ON p_id=smw_id WHERE title=smw_title AND smw_iw=" . $db->addQuotes(''), 'SMW::getUnusedPropertySubjects');
		}
		$db->query( "DELETE $smw_tmp_unusedprops.* FROM $smw_tmp_unusedprops, $smw_subs2 INNER JOIN $smw_ids ON o_id=smw_id WHERE title=smw_title", 'SMW::getUnusedPropertySubjects');
		// assume any property redirecting to some property to be used here:
		// (a stricter and more costy approach would be to delete only redirects to active properties;
		//  this would need to be done with an addtional query in the above loop)
		$db->query( "DELETE $smw_tmp_unusedprops.* FROM $smw_tmp_unusedprops, $smw_redi2 INNER JOIN $smw_ids ON (s_title=smw_title AND s_namespace=" . $db->addQuotes(SMW_NS_PROPERTY) . ") WHERE title=smw_title", 'SMW::getUnusedPropertySubjects');
		$res = $db->query("SELECT title FROM $smw_tmp_unusedprops " . $options, 'SMW::getUnusedPropertySubjects');
		/// FIXME: $res still includes builtin properties, but this might change when these are managed 
		/// differently in this store

		$result = array();
		while($row = $db->fetchObject($res)) {
			$result[] = Title::makeTitle(SMW_NS_PROPERTY, $row->title);
		}
		$db->freeResult($res);
		$db->query("DROP TEMPORARY table $smw_tmp_unusedprops", 'SMW::getUnusedPropertySubjects');
		wfProfileOut("SMWSQLStore2::getUnusedPropertiesSpecial (SMW)");
		return $result;
	}

	function getWantedPropertiesSpecial($requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getWantedPropertiesSpecial (SMW)");
		$db =& wfGetDB( DB_SLAVE );
		$options = ' ORDER BY count DESC';
		if ($requestoptions->limit > 0) {
			$options .= ' LIMIT ' . $requestoptions->limit;
		}
		if ($requestoptions->offset > 0) {
			$options .= ' OFFSET ' . $requestoptions->offset;
		}
		$res = $db->query('SELECT smw_title, COUNT(*) as count FROM ' .
		                  $db->tableName('smw_rels2') . ' INNER JOIN ' . $db->tableName('smw_ids') . 
		                  ' ON p_id=smw_id LEFT JOIN ' . $db->tableName('page') .
		                  ' ON (page_namespace=' . SMW_NS_PROPERTY .
		                  ' AND page_title=smw_title) WHERE page_id IS NULL GROUP BY smw_title' . $options,
		                  'SMW::getWantedPropertiesSpecial');
		$result = array();
		while($row = $db->fetchObject($res)) {
			$title = Title::makeTitle(SMW_NS_PROPERTY, $row->smw_title);
			$result[] = array($title, $row->count);
		}
		wfProfileOut("SMWSQLStore2::getWantedPropertiesSpecial (SMW)");
		return $result;
	}

	function getStatistics() {
		wfProfileIn('SMWSQLStore2::getStatistics (SMW)');
		$db =& wfGetDB( DB_SLAVE );
		$result = array();
		extract( $db->tableNames('smw_rels2', 'smw_atts2', 'smw_text2', 'smw_spec2') );
		$propuses = 0;
		$usedprops = 0;
		foreach (array($smw_rels2, $smw_atts2, $smw_text2) as $table) {
			/// TODO: this currently counts parts of nary properties as singular property uses
			/// Is this minor issue worth the extra join of filtering those?
			$res = $db->query("SELECT COUNT(s_id) AS count FROM $table", 'SMW::getStatistics');
			$row = $db->fetchObject( $res );
			$propuses += $row->count;
			$db->freeResult( $res );
			$res = $db->query("SELECT COUNT(DISTINCT(p_id)) AS count FROM $table", 'SMW::getStatistics');
			$row = $db->fetchObject( $res );
			$usedprops += $row->count;
			$db->freeResult( $res );
		}
		$result['PROPUSES'] = $propuses;
		$result['USEDPROPS'] = $usedprops;

		$res = $db->query("SELECT COUNT(s_id) AS count FROM $smw_spec2 WHERE sp_id=" . $db->addQuotes(SMW_SP_HAS_TYPE), 'SMW::getStatistics');
		$row = $db->fetchObject( $res );
		$result['DECLPROPS'] = $row->count;
		$db->freeResult( $res );

		wfProfileOut('SMWSQLStore2::getStatistics (SMW)');
		return $result;
	}

///// Setup store /////

	function setup($verbose = true) {
		global $wgDBtype;
		$this->reportProgress("Setting up standard database configuration for SMW ...\n\n",$verbose);
		if ($wgDBtype === 'postgres') {
			$this->reportProgress("For Postgres, please import the file SMW_Postgres_Schema_2.sql manually\n",$verbose);
			return;
		}
		$db =& wfGetDB( DB_MASTER );
		extract( $db->tableNames('smw_ids','smw_rels2','smw_atts2','smw_text2',
		                         'smw_spec2','smw_subs2','smw_redi2','smw_inst2',
		                         'smw_conc2','smw_conccache') );

		$this->setupTable($smw_ids, // internal IDs used in this store
		              array('smw_id'        => 'INT(8) UNSIGNED NOT NULL KEY AUTO_INCREMENT',
		                    'smw_namespace' => 'INT(11) NOT NULL',
		                    'smw_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'smw_iw'        => 'CHAR(32)',
		                    'smw_sortkey'   => 'VARCHAR(255) binary NOT NULL'
		                    ), $db, $verbose);
		$this->setupIndex($smw_ids, array('smw_id','smw_title,smw_namespace,smw_iw', 'smw_sortkey'), $db);

		$this->setupTable($smw_redi2, // fast redirect resolution
		              array('s_title'     => 'VARCHAR(255) binary NOT NULL',
		                    's_namespace' => 'INT(11) NOT NULL',
		                    'o_id'        => 'INT(8) UNSIGNED NOT NULL',), $db, $verbose);
		$this->setupIndex($smw_redi2, array('s_title,s_namespace','o_id'), $db);

		$this->setupTable($smw_rels2, // properties with other pages as values ("relations")
		              array('s_id' => 'INT(8) UNSIGNED NOT NULL',
		                    'p_id' => 'INT(8) UNSIGNED NOT NULL',
		                    'o_id' => 'INT(8) UNSIGNED NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_rels2, array('s_id','p_id','o_id'), $db);

		$this->setupTable($smw_atts2, // most standard properties ("attributes")
		              array('s_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'p_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'value_unit'        => 'VARCHAR(63) binary',
		                    'value_xsd'         => 'VARCHAR(255) binary NOT NULL',
		                    'value_num'         => 'DOUBLE'), $db, $verbose);
		$this->setupIndex($smw_atts2, array('s_id','p_id','value_num','value_xsd'), $db);

		$this->setupTable($smw_text2, // properties with long strings as values
		              array('s_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'p_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'value_blob'        => 'MEDIUMBLOB'), $db, $verbose);
		$this->setupIndex($smw_text2, array('s_id','p_id'), $db);

		$this->setupTable($smw_spec2, // (generic builtin) special properties
		              array('s_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'sp_id'       => 'SMALLINT(6) NOT NULL',
		                    'value_string'      => 'VARCHAR(255) binary NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_spec2, array('s_id', 'sp_id', 's_id,sp_id'), $db);

		$this->setupTable($smw_subs2, // subproperty/subclass relationships
		              array('s_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'o_id'        => 'INT(8) UNSIGNED NOT NULL',), $db, $verbose);
		$this->setupIndex($smw_subs2, array('s_id', 'o_id'), $db);

		$this->setupTable($smw_inst2, // class instances (s_id the element, o_id the class)
		              array('s_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'o_id'        => 'INT(8) UNSIGNED NOT NULL',), $db, $verbose);
		$this->setupIndex($smw_inst2, array('s_id', 'o_id'), $db);

		$this->setupTable($smw_conc2, // concept descriptions
		              array('s_id'             => 'INT(8) UNSIGNED NOT NULL KEY',
		                    'concept_txt'      => 'MEDIUMBLOB',
		                    'concept_docu'     => 'MEDIUMBLOB',
		                    'concept_features' => 'INT(8)',
		                    'concept_size'     => 'INT(8)',
		                    'concept_depth'    => 'INT(8)',
		                    'cache_date'       => 'INT(8) UNSIGNED',
		                    'cache_count'      => 'INT(8) UNSIGNED' ), $db, $verbose);
		$this->setupIndex($smw_conc2, array('s_id'), $db);

		$this->setupTable($smw_conccache, // concept cache: member elements (s)->concepts (o)
		              array('s_id'        => 'INT(8) UNSIGNED NOT NULL',
		                    'o_id'        => 'INT(8) UNSIGNED NOT NULL'), $db, $verbose);
		$this->setupIndex($smw_conccache, array('o_id'), $db);

		$this->reportProgress("Database initialised successfully.\n",$verbose);
		return true;
	}

	function drop($verbose = true) {
		$this->reportProgress("Deleting all database content and tables generated by SMW ...\n\n",$verbose);
		$db =& wfGetDB( DB_MASTER );
		$tables = array('smw_rels2', 'smw_atts2', 'smw_text2', 'smw_spec2', 
		                'smw_subs2', 'smw_redi2', 'smw_ids', 'smw_inst2',
		                'smw_conc2');
		foreach ($tables as $table) {
			$name = $db->tableName($table);
			$db->query("DROP TABLE IF EXISTS $name", 'SMWSQLStore2::drop');
			$this->reportProgress(" ... dropped table $name.\n", $verbose);
		}
		$this->reportProgress("All data removed successfully.\n",$verbose);
		return true;
	}


///// Concept caching /////

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function refreshConceptCache($concept) {
		wfProfileIn('SMWSQLStore2::refreshConceptCache (SMW)');
		global $smwgIP;
		include_once("$smwgIP/includes/storage/SMW_SQLStore2_Queries.php");
		$qe = new SMWSQLStore2QueryEngine($this,wfGetDB( DB_MASTER ));
		$result = $qe->refreshConceptCache($concept);
		wfProfileOut('SMWSQLStore2::refreshConceptCache (SMW)');
		return $result;
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache($concept) {
		wfProfileIn('SMWSQLStore2::deleteConceptCache (SMW)');
		global $smwgIP;
		include_once("$smwgIP/includes/storage/SMW_SQLStore2_Queries.php");
		$qe = new SMWSQLStore2QueryEngine($this,wfGetDB( DB_MASTER ));
		$result = $qe->deleteConceptCache($concept);
		wfProfileOut('SMWSQLStore2::deleteConceptCache (SMW)');
		return $result;
	}

	/**
	 * Show status of the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function showConceptCache($concept) {
		wfProfileIn('SMWSQLStore2::showConceptCache (SMW)');
		$db =& wfGetDB( DB_SLAVE );
		$cid = $this->getSMWPageID($concept->getDBKey(), $concept->getNamespace(), '', false);
		$row = $db->selectRow('smw_conc2',
		         array('concept_txt','concept_features','concept_size','concept_depth','cache_date','cache_count'),
		         array('s_id'=>$cid), 'SMWSQLStore2::showConceptCache (SMW)');
		if ($row !== false) {
			$result = ($row->cache_date?"Cache created at " . date("Y-m-d H:i:s",$row->cache_date) . " ($row->cache_count elements)":'Not cached.');
		} else {
			$result = 'Concept not known or redirect.';
		}
		wfProfileOut('SMWSQLStore2::showConceptCache (SMW)');
		return $result;
	}


///// Helper methods, mostly protected /////

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
					$op = $requestoptions->include_boundary?' >= ':' > ';
				} else {
					$op = $requestoptions->include_boundary?' <= ':' < ';
				}
				$sql_conds .= ' AND ' . $valuecol . $op . $db->addQuotes($requestoptions->boundary);
			}
			if ($labelcol !== NULL) { // apply string conditions
				foreach ($requestoptions->getStringConditions() as $strcond) {
					$string = str_replace('_', '\_', $strcond->string);
					switch ($strcond->condition) {
						case SMWStringCondition::STRCOND_PRE:  $string .= '%'; break;
						case SMWStringCondition::STRCOND_POST: $string = '%' . $string; break;
						case SMWStringCondition::STRCOND_MID:  $string = '%' . $string . '%'; break;
					}
					$sql_conds .= ' AND ' . $labelcol . ' LIKE ' . $db->addQuotes($string);
				}
			}
		}
		return $sql_conds;
	}

	/**
	 * Not in all cases can requestoptions be forwarded to the DB using getSQLConditions()
	 * and getSQLOptions(): some data comes from caches that do not respect the options yet.
	 * This method takes an array of results (SMWDataValue or Title objects) and applies
	 * the given requestoptions as appropriate.
	 */
	protected function applyRequestOptions($data, $requestoptions) {
		wfProfileIn("SMWSQLStore2::applyRequestOptions (SMW)");
		$result = array();
		$sortres = array();
		$key = 0;
		if ( (count($data) == 0) || ($requestoptions === NULL) ) {
			wfProfileOut("SMWSQLStore2::applyRequestOptions (SMW)");
			return $data;
		}
		foreach ($data as $item) {
			$numeric = false;
			$ok = true;
			if ($item instanceof SMWWikiPageValue) {
				$label = $item->getSortkey();
				$value = $label;
			} elseif ($item instanceof SMWDataValue) {
				$label = $item->getXSDValue();
				if ($item->isNumeric()) {
					$value = $item->getNumericValue();
					$numeric = true;
				} else {
					$value = $label;
				}
			} else { // instance of Title
				$label = $item->getText(); /// NOTE: no prefixed text, since only Text is used in SQL operations
				$value = $label;
			}
			if ($requestoptions->boundary !== NULL) { // apply value boundary
				$strc = $numeric?0:strcmp($value,$requestoptions->boundary);
				if ($requestoptions->ascending) {
					if ($requestoptions->include_boundary) {
						$ok = $numeric? ($value >= $requestoptions->boundary) : ($strc >= 0);
					} else {
						$ok = $numeric? ($value > $requestoptions->boundary) : ($strc > 0);
					}
				} else {
					if ($requestoptions->include_boundary) {
						$ok = $numeric? ($value <= $requestoptions->boundary) : ($strc <= 0);
					} else {
						$ok = $numeric? ($value < $requestoptions->boundary) : ($strc < 0);
					}
				}
			}
			foreach ($requestoptions->getStringConditions() as $strcond) { // apply string conditions
				switch ($strcond->condition) {
					case SMWStringCondition::STRCOND_PRE:
						$ok = $ok && (strpos($label,$strcond->string)===0);
						break;
					case SMWStringCondition::STRCOND_POST:
						$ok = $ok && (strpos(strrev($label),strrev($strcond->string))===0);
						break;
					case SMWStringCondition::STRCOND_MID:
						$ok = $ok && (strpos($label,$strcond->string)!==false);
						break;
				}
			}
			if ($ok) {
				$result[$key] = $item;
				$sortres[$key] = $value; // we cannot use $value as key: it is not unique if there are units!
				$key++;
			}
		}
		if ($requestoptions->sort) {
			// use last value of $numeric to indicate overall type
			$flag = $numeric?SORT_NUMERIC:SORT_LOCALE_STRING;
			if ($requestoptions->ascending) {
				asort($sortres,$flag);
			} else {
				arsort($sortres,$flag);
			}
			$newres = array();
			foreach ($sortres as $key => $value) {
				$newres[] = $result[$key];
			}
			$result = $newres;
		}
		if ($requestoptions->limit > 0) {
			$result = array_slice($result,$requestoptions->offset,$requestoptions->limit);
		} else {
			$result = array_slice($result,$requestoptions->offset);
		}
		wfProfileOut("SMWSQLStore2::applyRequestOptions (SMW)");
		return $result;
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
	 * @note The function partly ignores the order in which fields are set up.
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
			$db->query( $sql, 'SMWSQLStore2::setupTable' );
			$this->reportProgress("   ... new table created\n",$verbose);
			return array();
		} else { // check table signature
			$this->reportProgress("   ... table exists already, checking structure ...\n",$verbose);
			$res = $db->query( 'DESCRIBE ' . $table, 'SMWSQLStore2::setupTable' );
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
				if ($row->Key == 'PRI') { /// FIXME: updating "KEY" is not possible, the below query will fail in this case.
					$type .= ' KEY';
				}
				if ($row->Extra == 'auto_increment') {
					$type .= ' AUTO_INCREMENT';
				}
				$curfields[$row->Field] = $type;
			}
			$position = 'FIRST';
			foreach ($fields as $name => $type) {
				if ( !array_key_exists($name,$curfields) ) {
					$this->reportProgress("   ... creating column $name ... ",$verbose);
					$db->query("ALTER TABLE $table ADD `$name` $type $position", 'SMWSQLStore2::setupTable');
					$result[$name] = 'new';
					$this->reportProgress("done \n",$verbose);
				} elseif ($curfields[$name] != $type) {
					$this->reportProgress("   ... changing type of column $name from '$curfields[$name]' to '$type' ... ",$verbose);
					$db->query("ALTER TABLE $table CHANGE `$name` `$name` $type $position", 'SMWSQLStore2::setupTable');
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
					$db->query("ALTER TABLE $table DROP COLUMN `$name`", 'SMWSQLStore2::setupTable');
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

		foreach ($columns as $key => $column) { // add remaining indexes
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

	/**
	 * Find the numeric ID used for the page of the given title and namespace.
	 * If $canonical is set to true, redirects are taken into account to find the
	 * canonical alias ID for the given page.
	 * If no such ID exists, 0 is returned.
	 */
	public function getSMWPageID($title, $namespace, $iw, $canonical=true) {
		$sort = '';
		return $this->getSMWPageIDandSort($title, $namespace, $iw, $sort, $canonical);
	}
	
	/**
	 * Like getSMWPageID, but also sets the Call-By-Ref parameter $sort to the current
	 * sortkey.
	 */
	public function getSMWPageIDandSort($title, $namespace, $iw, &$sort, $canonical) {
		global $smwgQEqualitySupport;
		wfProfileIn('SMWSQLStore2::getSMWPageID (SMW)');
		$ckey = "$iw $namespace $title C";
		$nkey = "$iw $namespace $title -";
		$key = ($canonical?$ckey:$nkey);
		if (array_key_exists($key,$this->m_ids)) {
			wfProfileOut('SMWSQLStore2::getSMWPageID (SMW)');
			return $this->m_ids[$key];
		}
		if (count($this->m_ids)>1500) { // prevent memory leak in very long PHP runs
			$this->m_ids = array();
		}
		$db =& wfGetDB( DB_SLAVE );
		$id = 0;
		$redirect = false;
		if ($iw != '') {
			$res = $db->select('smw_ids', array('smw_id','smw_sortkey'), 'smw_title=' . $db->addQuotes($title) . ' AND ' . 'smw_namespace=' . $db->addQuotes($namespace) . ' AND smw_iw=' . $db->addQuotes($iw), 'SMW::getSMWPageID', array('LIMIT'=>1));
			if ($row = $db->fetchObject($res)) {
				$id = $row->smw_id;
				$sort = $row->smw_sortkey;
			}
		} else { // check for potential redirects also
			$res = $db->select('smw_ids', array('smw_id', 'smw_iw', 'smw_sortkey'), 'smw_title=' . $db->addQuotes($title) . ' AND ' . 'smw_namespace=' . $db->addQuotes($namespace) . ' AND (smw_iw=' . $db->addQuotes('') . ' OR smw_iw=' . $db->addQuotes(SMW_SQL2_SMWREDIIW) . ')', 'SMW::getSMWPageID', array('LIMIT'=>1));
			if ($row = $db->fetchObject($res)) {
				$sort = $row->smw_sortkey;
				$id = $row->smw_id; // set id in any case, the below check for properties will use even the redirect id in emergency
				if ( ($row->smw_iw == '') || (!$canonical) || ($smwgQEqualitySupport == SMW_EQ_NONE) ) {
					if ($row->smw_iw == '') {
						$this->m_ids[$ckey] = $id; // what we found is also the canonical key, cache it
					}
				} else {
					$redirect = true;
					$this->m_ids[$nkey] = $id; // what we found is the non-canonical key, cache it
				}
			}
		}
		$db->freeResult($res);

		if ($redirect) { // get redirect alias
			if ($namespace == SMW_NS_PROPERTY) { // redirect properties only to properties
				/// FIXME: Shouldn't this condition be ensured during writing?
				$res = $db->select(array('smw_redi2','smw_ids'), 'o_id', 'o_id=smw_id AND smw_namespace=s_namespace AND s_title=' . $db->addQuotes($title) . ' AND s_namespace=' . $db->addQuotes($namespace), 'SMW::getSMWPageID', array('LIMIT'=>1) );
			} else {
				$res = $db->select('smw_redi2', 'o_id', 's_title=' . $db->addQuotes($title) . ' AND s_namespace=' . $db->addQuotes($namespace), 'SMW::getSMWPageID', array('LIMIT'=>1) );
			}
			if ($row = $db->fetchObject($res)) {
				$id = $row->o_id;
			}
			$db->freeResult($res);
		}
		$this->m_ids[$key] = $id;
		wfProfileOut('SMWSQLStore2::getSMWPageID (SMW)');
		return $id;
	}

	/**
	 * Find the numeric ID used for the page of the given title and namespace.
	 * If $canonical is set to true, redirects are taken into account to find the
	 * canonical alias ID for the given page.
	 * If no such ID exists, a new ID is created and returned.
	 * In any case, the current sortkey is set to the given one unless $sortkey 
	 * is empty.
	 * @note Using this with $canonical==false may make sense, especially when
	 * the title is a redirect target (we do not want chains of redirects).
	 */
	protected function makeSMWPageID($title, $namespace, $iw, $canonical=true, $sortkey = '') {
		wfProfileIn('SMWSQLStore2::makeSMWPageID (SMW)');
		$oldsort = '';
		$id = $this->getSMWPageIDandSort($title, $namespace, $iw, $oldsort, $canonical);
		if ($id == 0) {
			$db =& wfGetDB( DB_MASTER );
			$sortkey = $sortkey?$sortkey:(str_replace('_',' ',$title));
			$db->insert('smw_ids', array('smw_id' => 0, 'smw_title' => $title, 'smw_namespace' => $namespace, 'smw_iw' => $iw, 'smw_sortkey' => $sortkey), 'SMW::makeSMWPageID');
			$id = $db->insertId();
			$this->m_ids["$iw $namespace $title -"] = $id; // fill that cache, even if canonical was given
			// This ID is also authorative for the canonical version.
			// This is always the case: if $canonical===false and $id===0, then there is no redi-entry in
			// smw_ids either, hence the object just did not exist at all.
			$this->m_ids["$iw $namespace $title C"] = $id;
		} elseif ( ($sortkey != '') && ($sortkey != $oldsort) ) {
			$db =& wfGetDB( DB_MASTER );
			$db->update('smw_ids', array('smw_sortkey' => $sortkey), array('smw_id' => $id), 'SMW::makeSMWPageID');
		}
		wfProfileOut('SMWSQLStore2::makeSMWPageID (SMW)');
		return $id;
	}

	/**
	 * Extend the ID cache as specified. This is called in places where IDs are retrieved
	 * by SQL queries and it would be a pity to throw them away. This function expects to
	 * get the contents of a line in smw_ids, i.e. possibly with iw being SMW_SQL2_SMWREDIIW.
	 * This information is used to determine whether the given ID is canonical or not.
	 */
	public function cacheSMWPageID($id, $title, $namespace, $iw) {
		$real_iw = ($iw == SMW_SQL2_SMWREDIIW)?'':$iw;
		$ckey = "$iw $namespace $title C";
		$nkey = "$iw $namespace $title -";
		if (count($this->m_ids)>1500) { // prevent memory leak in very long PHP runs
			$this->m_ids = array();
		}
		$this->m_ids[$nkey] = $id;
		if ($real_iw === $iw) {
			$this->m_ids[$ckey] = $id;
		}
	}

	/**
	 * Get a numeric ID for some Bnode that is to be used to encode an arbitrary
	 * n-ary property. Bnodes are managed through the smw_ids table but will always
	 * have an empty smw_title, and smw_namespace being set to the parent object
	 * (the id of the page that uses the Bnode). Unused Bnodes are not deleted but
	 * marked as available by setting smw_namespace to 0. This method then tries to 
	 * reuse an unused bnode before making a new one.
	 * NOTE: every call to this function, even if the same parameter id is used, returns
	 * a new bnode id!
	 */
	protected function makeSMWBnodeID($sid) {
		$db =& wfGetDB( DB_MASTER );
		$id = 0;
		// check if there is an unused bnode to take:
		$res = $db->select('smw_ids', 'smw_id', 'smw_title=' . $db->addQuotes('') . ' AND ' . 'smw_namespace=' . $db->addQuotes(0) . ' AND smw_iw=' . $db->addQuotes(SMW_SQL2_SMWIW), 'SMW::makeSMWBnodeID', array('LIMIT'=>1));
		if ($row = $db->fetchObject($res)) {
			$id = $row->smw_id;
		}
		// claim that bnode:
		if ($id != 0) {
			$db->update('smw_ids', array('smw_namespace' => $sid), array('smw_id'=>$id, 'smw_title' => '', 'smw_namespace' => 0, 'smw_iw' => SMW_SQL2_SMWIW), 'SMW::makeSMWBnodeID', array('LIMIT'=>1));
			if ($db->affectedRows() == 0) { // Oops, someone was faster (collisions are possible here, no locks)
				$id = 0; // fallback: make a new node (TODO: we could also repeat to try another ID)
			}
		}
		// if no node was found yet, make a new one:
		if ($id == 0) {
			$db->insert('smw_ids', array('smw_id' => 0, 'smw_title' => '', 'smw_namespace' => $sid, 'smw_iw' => SMW_SQL2_SMWIW), 'SMW::makeSMWBnodeID');
			$id = $db->insertId();
		}
		return $id;
	}


	/**
	 * Delete all semantic data stored for the given subject.
	 * Used for update purposes.
	 */
	public function deleteSemanticData($subject) {
		$db =& wfGetDB( DB_MASTER );
		/// NOTE: redirects are handled by updateRedirects(), not here!
			//$db->delete('smw_redi2', array('s_title' => $subject->getDBkey(),'s_namespace' => $subject->getNamespace()), 'SMW::deleteSubject::Redi2');
		$id = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),$subject->getInterwiki(),false);
		if ($id == 0) return; // not (directly) used anywhere yet, maybe a redirect but we do not care here
		$db->delete('smw_rels2', array('s_id' => $id), 'SMW::deleteSubject::Rels2');
		$db->delete('smw_atts2', array('s_id' => $id), 'SMW::deleteSubject::Atts2');
		$db->delete('smw_text2', array('s_id' => $id), 'SMW::deleteSubject::Text2');
		$db->delete('smw_spec2', array('s_id' => $id), 'SMW::deleteSubject::Spec2');
		$db->delete('smw_inst2', array('s_id' => $id), 'SMW::deleteSubject::Inst2');
		if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
			$db->delete('smw_subs2', array('s_id' => $id), 'SMW::deleteSubject::Subs2');
		}

		// find bnodes used by this ID ...
		$res = $db->select('smw_ids', 'smw_id','smw_title=' . $db->addQuotes('') . ' AND smw_namespace=' . $db->addQuotes($id) . ' AND smw_iw=' . $db->addQuotes(SMW_SQL2_SMWIW), 'SMW::deleteSubject::Nary');
		// ... and delete them as well
		while ($row = $db->fetchObject($res)) {
			$db->delete('smw_rels2', array('s_id' => $row->smw_id), 'SMW::deleteSubject::NaryRels2');
			$db->delete('smw_atts2', array('s_id' => $row->smw_id), 'SMW::deleteSubject::NaryAtts2');
			$db->delete('smw_text2', array('s_id' => $row->smw_id), 'SMW::deleteSubject::NaryText2');
		}
		$db->freeResult($res);
		// free all affected bnodes in one call:
		$db->update('smw_ids', array('smw_namespace' => 0), array('smw_title' => '', 'smw_namespace' => $id, 'smw_iw' => SMW_SQL2_SMWIW), 'SMW::deleteSubject::NaryIds');
	}


	/**
	 * Trigger all necessary updates for redirect structure on creation, change, and deletion
	 * of redirects. The title+namespace of the affected page and of its updated redirect 
	 * target are given. The target can be empty ('') if none is specified.
	 * Returns the canonical ID that is now to be used for the subject, or 0 if the subject did
	 * not occur anywhere yet.
	 * @note This method must do a lot of updates right, and some care is needed to not confuse
	 * ids or forget relevant tables. Please make sure you understand the relevant cases before
	 * making changes, especially since errors may go unnoticed for some time.
	 */
	protected function updateRedirects($subject_t, $subject_ns, $curtarget_t='', $curtarget_ns=-1) {
		global $smwgQEqualitySupport, $smwgEnableUpdateJobs;
		$sid = $this->getSMWPageID($subject_t, $subject_ns, '', false); // find real id of subject, if any
		/// NOTE: $sid can be 0 here; this is useful to know since it means that fewer table updates are needed
		$db =& wfGetDB( DB_SLAVE );
		$res = $db->select( array('smw_redi2'),'o_id','s_title=' . $db->addQuotes($subject_t) .
		                    ' AND s_namespace=' . $db->addQuotes($subject_ns),
		                    'SMW::updateRedirects', array('LIMIT' => 1) );
		$old_tid = ($row = $db->fetchObject($res))?$row->o_id:0; // real id of old target, if any
		$db->freeResult($res);
		$new_tid = $curtarget_t?($this->makeSMWPageID($curtarget_t, $curtarget_ns, '', false)):0; // real id of new target
		/// NOTE: $old_tid and $new_tid both ignore further redirects, (intentionally) no redirect chains!
		if ($old_tid == $new_tid) { // no change, all happy
			return ($new_tid==0)?$sid:$new_tid;
		}
		$db =& wfGetDB( DB_MASTER ); // now we need to write something
		if ( ($old_tid == 0) && ($sid != 0) && ($smwgQEqualitySupport != SMW_EQ_NONE) ) {
			// new redirect, directly change object entries of $sid to $new_tid
			/// NOTE: if $sid == 0, then nothing needs to be done here
			$db->update('smw_rels2', array( 'o_id' => $new_tid ), array( 'o_id' => $sid ), 'SMW::updateRedirects');
			if ( ( $subject_ns == SMW_NS_PROPERTY ) && ( $curtarget_ns == SMW_NS_PROPERTY ) ) {
				$cond_array = array( 'p_id' => $sid );
				$val_array  = array( 'p_id' => $new_tid );
				$db->update('smw_rels2', $val_array, $cond_array, 'SMW::updateRedirects');
				$db->update('smw_atts2', $val_array, $cond_array, 'SMW::updateRedirects');
				$db->update('smw_text2', $val_array, $cond_array, 'SMW::updateRedirects');
				$db->update('smw_subs2', array( 'o_id' => $new_tid ), array( 'o_id' => $sid ), 'SMW::updateRedirects');
			} elseif ($subject_ns == SMW_NS_PROPERTY) { // delete triples that are only allowed for properties
				$db->delete('smw_rels2', array( 'p_id' => $sid ), 'SMW::updateRedirects');
				$db->delete('smw_atts2', array( 'p_id' => $sid ), 'SMW::updateRedirects');
				$db->delete('smw_text2', array( 'p_id' => $sid ), 'SMW::updateRedirects');
				$db->delete('smw_subs2', array( 'o_id' => $sid ), 'SMW::updateRedirects');
			} elseif ( ( $subject_ns == NS_CATEGORY ) && ( $curtarget_ns == NS_CATEGORY ) ) {
				$db->update('smw_subs2', array( 'o_id' => $new_tid ), array( 'o_id' => $sid ), 'SMW::updateRedirects');
				$db->update('smw_inst2', array( 'o_id' => $new_tid ), array( 'o_id' => $sid ), 'SMW::updateRedirects');
			} elseif ($subject_ns == NS_CATEGORY) { // delete triples that are only allowed for categories
				$db->delete('smw_subs2', array( 'o_id' => $sid ), 'SMW::updateRedirects');
				$db->delete('smw_inst2', array( 'o_id' => $sid ), 'SMW::updateRedirects');
			}
		} elseif ($old_tid != 0) { // existing redirect is overwritten
			// we do not know which entries of $old_tid are now $new_tid/$sid
			// -> ask SMW to update all affected pages as soon as possible (using jobs)
			//first delete the existing redirect:
			$db->delete('smw_redi2', array('s_title' => $subject_t,'s_namespace' => $subject_ns), 'SMW::updateRedirects');
			if ( $smwgEnableUpdateJobs && ($smwgQEqualitySupport != SMW_EQ_NONE) ) { // further updates if equality reasoning is enabled
				$jobs = array();
				$res = $db->select( array('smw_rels2','smw_ids'),'DISTINCT smw_title,smw_namespace',
				                    's_id=smw_id AND o_id=' . $db->addQuotes($old_tid),
				                    'SMW::updateRedirects');
				while ($row = $db->fetchObject($res)) {
					$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
					$jobs[] = new SMWUpdateJob($t);
				}
				$db->freeResult($res);
				if ( $subject_ns == SMW_NS_PROPERTY ) {
					/// TODO: this would be more efficient if we would know the type of the
					/// property, but the current architecture deletes this first (PERFORMANCE)
					foreach (array('smw_rels2','smw_atts2','smw_text2') as $table) {
						$res = $db->select( array($table,'smw_ids'),'DISTINCT smw_title,smw_namespace',
						                    's_id=smw_id AND p_id=' . $db->addQuotes($old_tid),
						                    'SMW::updateRedirects');
						while ($row = $db->fetchObject($res)) {
							$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
							$jobs[] = new SMWUpdateJob($t);
						}
					}
					$res = $db->select( array('smw_subs2','smw_ids'),'DISTINCT smw_title,smw_namespace',
					                    's_id=smw_id AND o_id=' . $db->addQuotes($old_tid),
					                    'SMW::updateRedirects');
					while ($row = $db->fetchObject($res)) {
						$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
						$jobs[] = new SMWUpdateJob($t);
					}
				} elseif ( $subject_ns == NS_CATEGORY ) {
					foreach (array('smw_subs2','smw_inst2') as $table) {
						$res = $db->select( array($table,'smw_ids'),'DISTINCT smw_title,smw_namespace',
						                    's_id=smw_id AND o_id=' . $db->addQuotes($old_tid),
						                    'SMW::updateRedirects');
						while ($row = $db->fetchObject($res)) {
							$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
							$jobs[] = new SMWUpdateJob($t);
						}
					}
				}
				Job::batchInsert($jobs); ///NOTE: this only happens if $smwgEnableUpdateJobs was true above
			}
		}
		// finally, write the new redirect AND refresh your internal canonical id cache!
		if ($sid == 0) {
			$sid = $this->makeSMWPageID($subject_t, $subject_ns, '', false);
		}
		if ($new_tid != 0) {
			$db->insert( 'smw_redi2', array('s_title'=>$subject_t, 's_namespace'=>$subject_ns, 'o_id'=>$new_tid), 'SMW::updateRedirects');
			if ($smwgQEqualitySupport != SMW_EQ_NONE) {
				$db->update('smw_ids', array('smw_iw'=>SMW_SQL2_SMWREDIIW), array('smw_id'=>$sid), 'SMW::updateRedirects');
			}
			$this->m_ids[" $subject_ns $subject_t C"] = $new_tid; // "iw" is empty here
		} else {
			$this->m_ids[" $subject_ns $subject_t C"] = $sid; // "iw" is empty here
			if ($smwgQEqualitySupport != SMW_EQ_NONE) {
				$db->update('smw_ids', array('smw_iw'=>''), array('smw_id'=>$sid), 'SMW::updateRedirects');
			}
		}
		// just flush those caches to be safe, they are not essential in program runs with redirect updates
		unset($this->m_semdata[$sid]); unset($this->m_semdata[$new_tid]); unset($this->m_semdata[$old_tid]);
		unset($this->m_sdstate[$sid]); unset($this->m_sdstate[$new_tid]); unset($this->m_sdstate[$old_tid]);
		return ($new_tid==0)?$sid:$new_tid;
	}

}
