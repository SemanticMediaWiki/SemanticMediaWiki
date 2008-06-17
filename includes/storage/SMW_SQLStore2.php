<?php
/**
 * New SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $smwgIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgIP/includes/SMW_DataValueFactory.php" );

define('SMW_SQL2_SMWIW',':smw'); // virtual "interwiki prefix" for special SMW objects

// Constant flags for identifying tables/retrieval types
define('SMW_SQL2_RELS2',1);
define('SMW_SQL2_ATTS2',2);
define('SMW_SQL2_TEXT2',4);
define('SMW_SQL2_SPEC2',8);
define('SMW_SQL2_REDI2',16);
define('SMW_SQL2_NARY2',32); // not really a table, but a retrieval type
define('SMW_SQL2_SUBS2',64);
define('SMW_SQL2_INST2',128);


/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 * 
 * NOTE: Regarding the use of interwiki links in the store, there is currently
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

///// Reading methods /////

	function getSemanticData($subject, $filter = false) {
		wfProfileIn("SMWSQLStore2::getSemanticData (SMW)");
		$db =& wfGetDB( DB_SLAVE );

		if ( $subject instanceof Title ) {
			$sid = $this->getSMWPageID($subject->getDBkey(),$subject->getNamespace(),$subject->getInterwiki());
			$stitle = $subject;
		} elseif ($subject instanceof SMWWikiPageValue) {
			$sid = $this->getSMWPageID($subject->getDBkey(),$subject->getNamespace(),$subject->getInterwiki());
			$stitle = $subject->getTitle();
		} else {
			$sid = 0;
			$result = NULL;
		}
		if ($sid == 0) { // no data, safe our time
		/// NOTE: we consider redirects for getting $sid, so $sid == 0 also means "no redirects"
			wfProfileOut("SMWSQLStore2::getSemanticData (SMW)");
			return isset($stitle)?(new SMWSemanticData($stitle)):NULL;
		}

		if ($filter !== false) { //array as described in docu for SMWStore
			$tasks = 0;
			foreach ($filter as $value) {
				switch ($value) {
					case '_wpg': $tasks = $tasks | SMW_SQL2_RELS2; break;
					case '_txt': $tasks = $tasks | SMW_SQL2_TEXT2; break;
					case '__nry': $tasks = $tasks | SMW_SQL2_NARY2; break;
					case SMW_SP_INSTANCE_OF: $tasks = $tasks | SMW_SQL2_INST2; break;
					case SMW_SP_REDIRECTS_TO: $tasks = $tasks | SMW_SQL2_REDI2;	break;
					case SMW_SP_SUBPROPERTY_OF: case SMW_SP_SUBCLASS_OF:
						$tasks = $tasks | SMW_SQL2_SUBS2;
					break;
					default:
						if (is_numeric($value)) { // some special property
							$tasks = $tasks | SMW_SQL2_SPEC2;
						} else { // some other "attribute"
							$tasks = $tasks | SMW_SQL2_ATTS2;
						}
				}
			}
		} else {
			$tasks = SMW_SQL2_RELS2 | SMW_SQL2_ATTS2 | SMW_SQL2_TEXT2| SMW_SQL2_SPEC2 | SMW_SQL2_NARY2 | SMW_SQL2_SUBS2 | SMW_SQL2_INST2 | SMW_SQL2_REDI2;
		}
		if ( ($subject->getNamespace() != SMW_NS_PROPERTY) && ($subject->getNamespace() != NS_CATEGORY) ) {
			$tasks = $tasks & ~SMW_SQL2_SUBS2;
		}

		if (!array_key_exists($sid, $this->m_semdata)) { // new cache entry
			$this->m_semdata[$sid] = new SMWSemanticData($stitle, false);
			$this->m_sdstate[$sid] = $tasks;
		} else { // do only remaining tasks
			$newtasks = $tasks & ~$this->m_sdstate[$sid];
			$this->m_sdstate[$sid] = $this->m_sdstate[$sid] | $tasks;
			$tasks = $newtasks;
		}
		if (count($this->m_semdata) > 1000) { // prevent memory leak on very long PHP runs
			$this->m_semdata = array($sid => $this->m_semdata[$sid]);
			$this->m_sdstate = array($sid => $this->m_sdstate[$sid]);
		}

		// most types of data suggest rather similar code
		foreach (array(SMW_SQL2_RELS2, SMW_SQL2_ATTS2, SMW_SQL2_TEXT2, SMW_SQL2_INST2, SMW_SQL2_SUBS2, SMW_SQL2_SPEC2, SMW_SQL2_REDI2) as $task) {
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
			}
			$res = $db->select( $from, $select, $where, 'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				if ($task & (SMW_SQL2_RELS2 | SMW_SQL2_ATTS2 | SMW_SQL2_TEXT2) ) {
					$property = Title::makeTitle(SMW_NS_PROPERTY, $row->prop);
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				} elseif ($task == SMW_SQL2_SPEC2) {
					$dv = SMWDataValueFactory::newSpecialValue($row->prop);
				} else {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				}
				if ($task == SMW_SQL2_RELS2) {
					if ($dv instanceof SMWWikiPagevalue) { // may fail if type was changed!
						$dv->setValues($row->title, $row->namespace, false, $row->iw);
						$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
					}
				} elseif ($task == SMW_SQL2_ATTS2) {
					$dv->setXSDValue($row->value, $row->unit);
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				} elseif ($task == SMW_SQL2_TEXT2) {
					$dv->setXSDValue($row->value, '');
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				} elseif ($task == SMW_SQL2_SPEC2) {
					$dv->setXSDValue($row->value);
					$this->m_semdata[$sid]->addSpecialValue($row->prop, $dv);
				} elseif ($task == SMW_SQL2_SUBS2) {
					$dv->setValues($row->value, $namespace);
					$this->m_semdata[$sid]->addSpecialValue($specprop, $dv);
				} elseif ($task == SMW_SQL2_REDI2) {
					$dv->setValues($row->title, $row->namespace);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_REDIRECTS_TO, $dv);
				} elseif ($task == SMW_SQL2_INST2) {
					$dv->setValues($row->value, NS_CATEGORY);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_INSTANCE_OF, $dv);
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
				$res = $db->select( array('smw_inst2','smw_ids'), 'smw_title,smw_namespace',
				                    's_id=smw_id AND o_id=' . $db->addQuotes($oid), 
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$result[] =  Title::makeTitle($row->smw_namespace, $row->smw_title);
				}
				$db->freeResult($res);
			}
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),$value->getInterwiki(),false);
			/// NOTE: we do not use the canonical (redirect-aware) id here!
			if ($oid != 0) {
				$res = $db->select( array('smw_redi2'), 's_title,s_namespace',
				                    'o_id=' . $db->addQuotes($oid),
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$t = Title::makeTitle($row->s_namespace,$row->s_title);
					if ($t !== NULL) {
						$result[] = $t;
					}
				}
				$db->freeResult($res);
			}
		} elseif ( ($specialprop === SMW_SP_SUBPROPERTY_OF) || ($specialprop === SMW_SP_SUBCLASS_OF) ) { 
			// subproperties/subclasses
			$namespace = ($specialprop === SMW_SP_SUBCLASS_OF)?NS_CATEGORY:SMW_NS_PROPERTY;
			$oid = $this->getSMWPageID($value->getDBkey(),$namespace,$value->getInterwiki());
			if ( ($oid != 0) && ($value->getNamespace() == $namespace) ) {
				$res = $db->select( array('smw_subs2','smw_ids'), 'smw_title',
				                    's_id=smw_id AND o_id=' . $db->addQuotes($oid), 
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$result[] =  Title::makeTitle($namespace, $row->smw_title);
				}
				$db->freeResult($res);
			}
		} else {
			if ($value->getXSDValue() !== false) { // filters out error-values etc.
				$stringvalue = $value->getXSDValue();
			} else {
				wfProfileOut("SMWSQLStore2::getSpecialSubjects-$specialprop (SMW)");
				return array();
			}
			$sql = 'smw_id=s_id AND sp_id=' . $db->addQuotes($specialprop) .
			       ' AND value_string=' . $db->addQuotes($stringvalue) .
			       $this->getSQLConditions($requestoptions,'smw_title','smw_title');
			$res = $db->select( array('smw_spec2','smw_ids'), 'DISTINCT smw_title,smw_namespace',
			                    $sql, 'SMW::getSpecialSubjects', 
			                    $this->getSQLOptions($requestoptions,'smw_title') );
			while($row = $db->fetchObject($res)) {
				$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
				if ($t !== NULL) {
					$result[] = $t;
				}
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
		}
		$pid = $this->getSMWPageID($property->getDBkey(), SMW_NS_PROPERTY, $property->getInterwiki());
		if ( ($sid == 0) || ($pid == 0)) {
			wfProfileOut("SMWSQLStore2::getPropertyValues (SMW)");
			return array();
		}
		$sd = $this->getSemanticData($subject,array(SMWDataValueFactory::getPropertyObjectTypeID($property)));
		$result = $this->applyRequestOptions($sd->getPropertyValues($property),$requestoptions);
		if ($outputformat != '') {
			$newres = array();
			foreach ($result as $dv) {
				$ndv = clone $dv;
				$ndv->setOutputFormat($outputformat);
				$newres[] = $ndv;
			}
			$result = $newres;
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
		case '_txt': break; // not supported
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
				case '_txt': break; // not supported
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
			$res = $db->query("SELECT DISTINCT i.smw_title AS title,i.smw_namespace AS namespace FROM $from WHERE $where", 'SMW::getPropertySubjects', $this->getSQLOptions($requestoptions,'title'));
			while($row = $db->fetchObject($res)) {
				$t = Title::makeTitle($row->namespace,$row->title);
				if ($t !== NULL) {
					$result[] = $t;
				}
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
			                    'DISTINCT smw_title,smw_namespace',
			                    's_id=smw_id AND ' . $sql . $this->getSQLConditions($requestoptions,'smw_title','smw_title'), 'SMW::getPropertySubjects',
			                    $this->getSQLOptions($requestoptions,'smw_title') );
			while($row = $db->fetchObject($res)) {
				$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
				if ($t !== NULL) {
					$result[] = $t;
				}
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
		$sql = 's_id=' . $db->addQuotes($sid) . ' AND p_id=smw_id' . $this->getSQLConditions($requestoptions,'smw_title','smw_title');

		$result = array();
		// NOTE: the following also includes naries, which are now kepn in smw_rels2
		foreach (array('smw_atts2','smw_text2','smw_rels2') as $table) {
			$res = $db->select( array($table,'smw_ids'), 'DISTINCT smw_title',
			                    $sql, 'SMW::getProperties', $this->getSQLOptions($requestoptions,'smw_title') );
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
			       $this->getSQLConditions($requestoptions,'smw_title','smw_title');
			$res = $db->select( array('smw_rels2','smw_ids'), 'DISTINCT smw_title',
			                    $sql, 'SMW::getInProperties', $this->getSQLOptions($requestoptions,'smw_title') );
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
		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///TODO: Possibly delete ID here (at least for non-properties/categories, if not used in any place in rels2)
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
		// always make an ID (pages without ID cannot be in qurey results, not even in fixed value queries!):
		$sid = $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),'');
		$db =& wfGetDB( DB_MASTER );

		// do bulk updates:
		$up_rels2 = array();  $up_atts2 = array();
		$up_text2 = array();  $up_spec2 = array();
		$up_subs2 = array();  $up_inst2 = array();

		//properties
		foreach($data->getProperties() as $key => $property) {
			$propertyValueArray = $data->getPropertyValues($property);
			if ($property instanceof Title) { // normal property
				foreach($propertyValueArray as $value) {
					if ($value->isValid()) {
						if ($value->getTypeID() == '_txt') {
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
									case '_txt':
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
					case SMW_SP_IMPORTED_FROM: // don't store this, just used for display;
						/// TODO: filtering here is bad for fully neglected properties (IMPORTED FROM)
					case SMW_SP_REDIRECTS_TO: // handled by updateRedirects above
					break;
					case SMW_SP_INSTANCE_OF:
						foreach($propertyValueArray as $value) {
							if ( $value->getNamespace() == NS_CATEGORY )  {
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
							if ( $value->getNamespace() == $namespace )  {
								$up_subs2[] =
								array('s_id' => $sid,
								      'o_id' => $this->makeSMWPageID($value->getDBkey(),$value->getNamespace(),''));
							}
						}
					break;
					default: // normal special value
						foreach($propertyValueArray as $value) {
							if ($value->getXSDValue() !== false) { // filters out error-values etc.
								$stringvalue = $value->getXSDValue();
							}
							$up_spec2[] =
							array('s_id' => $sid,
							      'sp_id' => $property,
							      'value_string' => $stringvalue);
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
			} else { // make id for use in redirect table
				$sid = $this->makeSMWPageID($oldtitle->getDBKey(),$oldtitle->getNamespace(),'',false);
			}
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
		                         'smw_spec2','smw_subs2','smw_redi2','smw_inst2') );

		$this->setupTable($smw_ids, // internal IDs used in this store
		              array('smw_id'        => 'INT(8) UNSIGNED NOT NULL KEY AUTO_INCREMENT',
		                    'smw_namespace' => 'INT(11) NOT NULL',
		                    'smw_title'     => 'VARCHAR(255) binary NOT NULL',
		                    'smw_iw'        => 'CHAR(32)'
		                    ), $db, $verbose);
		$this->setupIndex($smw_ids, array('smw_id','smw_title,smw_namespace,smw_iw'), $db);

		$this->setupTable($smw_redi2, // fast redirect resolution
		              array('s_title'       => 'VARCHAR(255) binary NOT NULL',
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

		$this->reportProgress("Database initialised successfully.\n",$verbose);
		return true;
	}

	function drop($verbose = true) {
		$this->reportProgress("Deleting all database content and tables generated by SMW ...\n\n",$verbose);
		$db =& wfGetDB( DB_MASTER );
		$tables = array('smw_rels2', 'smw_atts2', 'smw_text2', 'smw_spec2', 'smw_subs2', 'smw_redi2', 'smw_ids');
		foreach ($tables as $table) {
			$name = $db->tableName($table);
			$db->query("DROP TABLE $name", 'SMWSQLStore2::drop');
			$this->reportProgress(" ... dropped table $name.\n", $verbose);
		}
		$this->reportProgress("All data removed successfully.\n",$verbose);
		return true;
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
						case SMW_STRCOND_PRE:  $string .= '%'; break;
						case SMW_STRCOND_POST: $string = '%' . $string; break;
						case SMW_STRCOND_MID:  $string = '%' . $string . '%'; break;
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
			if ($item instanceof SMWDataValue) {
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
					case SMW_STRCOND_PRE:
						$ok = $ok && (strpos($label,$strcond->string)===0);
						break;
					case SMW_STRCOND_POST:
						$ok = $ok && (strpos(strrev($label),strrev($strcond->string))===0);
						break;
					case SMW_STRCOND_MID:
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
		wfProfileIn('SMWSQLStore2::getSMWPageID (SMW)');
		$key = "$iw $namespace $title " . ($canonical?'C':'-');
		if (array_key_exists($key,$this->m_ids)) {
			wfProfileOut('SMWSQLStore2::getSMWPageID (SMW)');
			return $this->m_ids[$key];
		}
		$db =& wfGetDB( DB_SLAVE );
		$id = 0;
		if ( $canonical && (!$iw) ) { // check redirect alias first
			if ($namespace == SMW_NS_PROPERTY) { // redirect properties only to properties
				$res = $db->select(array('smw_redi2','smw_ids'), 'o_id', 'o_id=smw_id AND smw_namespace=s_namespace AND s_title=' . $db->addQuotes($title) . ' AND s_namespace=' . $db->addQuotes($namespace), 'SMW::getSMWPageID', array('LIMIT'=>1) );
			} else {
				$res = $db->select('smw_redi2', 'o_id', 's_title=' . $db->addQuotes($title) . ' AND s_namespace=' . $db->addQuotes($namespace), 'SMW::getSMWPageID', array('LIMIT'=>1) );
			}
			if ($row = $db->fetchObject($res)) {
				$id = $row->o_id;
			}
			$db->freeResult($res);
		}
		if ($id == 0) { // try other table if nothing was found yet
			$res = $db->select('smw_ids', 'smw_id', 'smw_title=' . $db->addQuotes($title) . ' AND ' . 'smw_namespace=' . $db->addQuotes($namespace) . ' AND smw_iw=' . $db->addQuotes($iw), 'SMW::getSMWPageID', array('LIMIT'=>1));
			if ($row = $db->fetchObject($res)) {
				$id = $row->smw_id;
			}
			$db->freeResult($res);
		}
		if (count($this->m_ids)>1000) { // prevent memory leak in very long PHP runs
			$this->m_ids = array();
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
	 */
	protected function makeSMWPageID($title, $namespace, $iw, $canonical=true) {
		wfProfileIn('SMWSQLStore2::makeSMWPageID (SMW)');
		$id = $this->getSMWPageID($title, $namespace, $iw, $canonical);
		if ($id == 0) {
			$db =& wfGetDB( DB_MASTER );
			$db->insert('smw_ids', array('smw_id' => 0, 'smw_title' => $title, 'smw_namespace' => $namespace, 'smw_iw' => $iw), 'SMW::makeSMWPageID');
			$id = $db->insertId();
			$this->m_ids["$iw $namespace $title -"] = $id; // fill that cache, even if canonical was given
			if ($canonical) { // this ID is also authorative for the canonical version
				$this->m_ids["$iw $namespace $title C"] = $id;
			}
		}
		wfProfileOut('SMWSQLStore2::makeSMWPageID (SMW)');
		return $id;
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
	protected function deleteSemanticData(Title $subject) {
		$db =& wfGetDB( DB_MASTER );
		/// NOTE: redirects are handled by updateRedirects(), not here!
			//$db->delete('smw_redi2', array('s_title' => $subject->getDBkey(),'s_namespace' => $subject->getNamespace()), 'SMW::deleteSubject::Redi2');
		$id = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),$subject->getInterwiki(),false);
		if ($id == 0) return; // not (directly) used anywhere yet, maybe a redirect but we do not care here
		$db->delete('smw_rels2', array('s_id' => $id), 'SMW::deleteSubject::Rels2');
		$db->delete('smw_atts2', array('s_id' => $id), 'SMW::deleteSubject::Atts2');
		$db->delete('smw_text2', array('s_id' => $id), 'SMW::deleteSubject::Text2');
		$db->delete('smw_spec2', array('s_id' => $id), 'SMW::deleteSubject::Spec2');
		$db->delete('smw_inst2', array('s_id' => $id), 'SMW::deleteSubject::Text2');
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
	 * NOTE: this method must do a lot of updates right, and some care is needed to not confuse
	 * ids or forget relevant tables. Please make sure you understand the relevant cases before
	 * making changes, especially since errors may go unnoticed for some time.
	 */
	protected function updateRedirects($subject_t, $subject_ns, $curtarget_t='', $curtarget_ns=-1) {
		$sid = $this->getSMWPageID($subject_t, $subject_ns, '', false); // find real id of subject, if any
		/// NOTE: $sid can be 0 here, which is fine for redirect pages (they do not need an own id)
		$db =& wfGetDB( DB_SLAVE );
		$res = $db->select( array('smw_redi2'),'o_id','s_title=' . $db->addQuotes($subject_t) .
		                    ' AND s_namespace=' . $db->addQuotes($subject_ns),
		                    'SMW::updateRedirects', array('LIMIT' => 1) );
		$old_tid = ($row = $db->fetchObject($res))?$row->o_id:0; // real id of old target, if any
		$db->freeResult($res);
		$new_tid = $curtarget_t?($this->makeSMWPageID($curtarget_t, $curtarget_ns, '', false)):0; // real id of new target
		/// NOTE: $old_tid and $new_tid both ignore further redirects, (intentionally) no redirect chains!
		if ($old_tid == $new_tid) { // no change, all happy
			return $sid;
		} elseif ( ($old_tid == 0) && ($sid != 0) ) { // new redirect, directly change object entries of $sid to $new_tid
			/// NOTE: if $sid == 0, then nothing needs to be done here
			$db =& wfGetDB( DB_MASTER );
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
			$db =& wfGetDB( DB_MASTER );
			//first delete the existing redirect:
			$db->delete('smw_redi2', array('s_title' => $subject_t,'s_namespace' => $subject_ns), 'SMW::updateRedirects');
			$res = $db->select( array('smw_rels2','smw_ids'),'DISTINCT smw_title,smw_namespace',
			                    's_id=smw_id AND o_id=' . $db->addQuotes($old_tid),
			                    'SMW::updateRedirects');
			while ($row = $db->fetchObject($res)) {
				$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
				$job = new SMWUpdateJob($t);
				$job->insert();
			}
			$db->freeResult($res);
			if ( $subject_ns == SMW_NS_PROPERTY ) {
				/// TODO: this would be more efficient when we would know the type of the
				/// property, but the current architecture deletes this first (PERFORMANCE)
				foreach (array('smw_rels2','smw_atts2','smw_text2') as $table) {
					$res = $db->select( array($table,'smw_ids'),'DISTINCT smw_title,smw_namespace',
					                    's_id=smw_id AND p_id=' . $db->addQuotes($old_tid),
					                    'SMW::updateRedirects');
					while ($row = $db->fetchObject($res)) {
						$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
						$job = new SMWUpdateJob($t);
						$job->insert();
					}
				}
				$res = $db->select( array('smw_subs2','smw_ids'),'DISTINCT smw_title,smw_namespace',
				                    's_id=smw_id AND o_id=' . $db->addQuotes($old_tid),
				                    'SMW::updateRedirects');
				while ($row = $db->fetchObject($res)) {
					$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
					$job = new SMWUpdateJob($t);
					$job->insert();
				}
			} elseif ( $subject_ns == NS_CATEGORY ) {
				foreach (array('smw_subs2','smw_inst2') as $table) {
					$res = $db->select( array($table,'smw_ids'),'DISTINCT smw_title,smw_namespace',
					                    's_id=smw_id AND o_id=' . $db->addQuotes($old_tid),
					                    'SMW::updateRedirects');
					while ($row = $db->fetchObject($res)) {
						$t = Title::makeTitle($row->smw_namespace,$row->smw_title);
						$job = new SMWUpdateJob($t);
						$job->insert();
					}
				}
			}
		}
		// finally, write the new redirect AND refresh your internal canonical id cache!
		if ($new_tid != 0) {
			$db->insert( 'smw_redi2', array('s_title'=>$subject_t, 's_namespace'=>$subject_ns, 'o_id'=>$new_tid), 'SMW::updateRedirects');
			$this->m_ids[" $subject_ns $subject_t C"] = $new_tid; // "iw" is empty here
		} else {
			$this->m_ids[" $subject_ns $subject_t C"] = $sid; // "iw" is empty here
		}
		// just flush those caches to be safe, they are not essential in program runs with redirect updates
		unset($this->m_semdata[$sid]); unset($this->m_semdata[$new_tid]); unset($this->m_semdata[$old_tid]);
		unset($this->m_sdstate[$sid]); unset($this->m_sdstate[$new_tid]); unset($this->m_sdstate[$old_tid]);
		return ($new_tid==0)?$sid:$new_tid;
	}

}
