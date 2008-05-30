<?php
/**
 * New SQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgIP/includes/SMW_DataValueFactory.php" );

define('SMW_SQL2_SMWIW',':smw'); // virtual "interwiki prefix" for special SMW objects

// Constants flags for identifying tables/retrieval types
define('SMW_SQL2_RELS2',1);
define('SMW_SQL2_ATTS2',2);
define('SMW_SQL2_TEXT2',4);
define('SMW_SQL2_SPEC2',8);
define('SMW_SQL2_REDI2',16);
define('SMW_SQL2_NARY2',32); // not really a table, but a retrieval type
define('SMW_SQL2_SUBS2',64);
define('SMW_SQL2_CATS2',128);

/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data.
 */
class SMWSQLStore2 extends SMWStore {

	/// Cache for SMW IDs, indexed by string keys
	protected $m_ids = array();

	/// Cache for SMWSemanticData objects, indexed by SMW ID
	protected $m_semdata = array();
	/// Like SMWSQLStore2::m_semdata, but containing flags indicating completeness of the SMWSemanticData objs
	protected $m_sdstate = array();

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
		wfProfileIn("SMWSQLStore2::getSemanticData (SMW)");
		$db =& wfGetDB( DB_SLAVE );

		if ( $subject instanceof Title ) {
			$sid = $this->getSMWPageID($subject->getDBkey(),$subject->getNamespace(),'');
			$stitle = $subject;
		} elseif ($subject instanceof SMWWikiPageValue) {
			$sid = $this->getSMWPageID($subject->getDBkey(),$subject->getNamespace(),'');
			$stitle = $subject->getTitle();
		} else {
			$sid = 0;
			$result = NULL;
		}
		if ($sid == 0) { // no data, save our time
		// NOTE: we consider redirects for getting $sid, so $sid == 0 also means "no redirects"
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
					case SMW_SP_HAS_CATEGORY: $tasks = $tasks | SMW_SQL2_CATS2; break;
					case SMW_SP_REDIRECTS_TO: $tasks = $tasks | SMW_SQL2_REDI2;	break;
					case SMW_SP_SUBPROPERTY_OF:	$tasks = $tasks | SMW_SQL2_SUBS2; break;
					default:
						if (is_numeric($value)) { // some special property
							$tasks = $tasks | SMW_SQL2_SPEC2;
						} else { // some other "attribute"
							$tasks = $tasks | SMW_SQL2_ATTS2;
						}
				}
			}
		} else {
			$tasks = SMW_SQL2_RELS2 | SMW_SQL2_ATTS2 | SMW_SQL2_TEXT2| SMW_SQL2_SPEC2 | SMW_SQL2_NARY2 | SMW_SQL2_SUBS2 | SMW_SQL2_CATS2 | SMW_SQL2_REDI2;
		}
		if ($subject->getNamespace() != SMW_NS_PROPERTY) {
			$tasks = $tasks & ~SMW_SQL2_SUBS2;
		}

		if (!array_key_exists($sid, $this->m_semdata)) { // new cache entry
			$this->m_semdata[$sid] = new SMWSemanticData($stitle);
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

		// relations need a different kind of DB call
		if ($tasks & SMW_SQL2_RELS2) {
			// Sorry, no DB wrapper method supports "AS", using query()
			$res = $db->query( 'SELECT p.smw_title AS ptitle, o.smw_title AS otitle, o.smw_namespace AS onamespace FROM ' . $db->tableName('smw_rels2') . ' INNER JOIN ' . $db->tableName('smw_ids') . ' AS p ON p_id=p.smw_id INNER JOIN ' . $db->tableName('smw_ids') . ' AS o ON o_id=o.smw_id WHERE s_id=' . $db->addQuotes($sid), 'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				$property = Title::makeTitle(SMW_NS_PROPERTY, $row->ptitle);
				$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				if ($dv instanceof SMWWikiPagevalue) { // may fail if type was changed!
					$dv->setValues($row->otitle, $row->onamespace);
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				}
			}
			$db->freeResult($res);
		}
		// most other types of data suggest rather similar code
		foreach (array(SMW_SQL2_ATTS2, SMW_SQL2_TEXT2, SMW_SQL2_CATS2, SMW_SQL2_SUBS2, SMW_SQL2_SPEC2, SMW_SQL2_REDI2) as $task) {
			if ( !($tasks & $task) ) continue;
			$where = 'p_id=smw_id AND s_id=' . $db->addQuotes($sid);
			switch ($task) {
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
				break;
				case SMW_SQL2_REDI2:
					$from = array('smw_redi2','smw_ids');
					$select = 'smw_title as title, smw_namespace as namespace';
					$where = 'o_id=smw_id AND s_title=' . $db->addQuotes($subject->getDBkey()) .
					         ' AND s_namespace=' . $db->addQuotes($subject->getNamespace());
				break;
				case SMW_SQL2_CATS2:
					$from = 'categorylinks';
					$select = 'DISTINCT cl_to as value';
					$where = 'cl_from=' . $db->addQuotes($subject->getArticleID());
				break;
			}
			$res = $db->select( $from, $select, $where, 'SMW::getSemanticData' );
			while($row = $db->fetchObject($res)) {
				if ($task & (SMW_SQL2_ATTS2 | SMW_SQL2_TEXT2) ) {
					$property = Title::makeTitle(SMW_NS_PROPERTY, $row->prop);
					$dv = SMWDataValueFactory::newPropertyObjectValue($property);
				} elseif ($task == SMW_SQL2_SPEC2) {
					$dv = SMWDataValueFactory::newSpecialValue($row->prop);
				} else {
					$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
				}
				if ($task == SMW_SQL2_ATTS2) {
					$dv->setXSDValue($row->value, $row->unit);
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				} elseif ($task == SMW_SQL2_TEXT2) {
					$dv->setXSDValue($row->value, '');
					$this->m_semdata[$sid]->addPropertyObjectValue($property, $dv);
				} elseif ($task == SMW_SQL2_SPEC2) {
					$dv->setXSDValue($row->value);
					$this->m_semdata[$sid]->addSpecialValue($row->prop, $dv);
				} elseif ($task == SMW_SQL2_SUBS2) {
					$dv->setValues($row->value, SMW_NS_PROPERTY);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_SUBPROPERTY_OF, $dv);
				} elseif ($task == SMW_SQL2_REDI2) {
					$dv->setValues($row->title, $row->namespace);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_REDIRECTS_TO, $dv);
				} elseif ($task == SMW_SQL2_CATS2) {
					$dv->setValues($row->value, NS_CATEGORY);
					$this->m_semdata[$sid]->addSpecialValue(SMW_SP_HAS_CATEGORY, $dv);
				}
			}
			$db->freeResult($res);
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
			$sid = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),'');
		}
		if ( ($sid == 0) && ($specialprop != SMW_SP_REDIRECTS_TO)) {
			// NOTE: SMW_SP_REDIRECTS_TO is the only property, that objectgs without an SMW-ID may have
			wfProfileOut("SMWSQLStore2::getSpecialValues-$specialprop (SMW)");
			return array();
		}
		$sd = $this->getSemanticData($subject,array($specialprop));
		$result = $this->applyRequestOptions($sd->getPropertyValues($specialprop),$requestoptions);
		wfProfileOut("SMWSQLStore2::getSpecialValues-$specialprop (SMW)");
		return $result;
	}

	function getSpecialSubjects($specialprop, $value, $requestoptions = NULL) {
		wfProfileIn("SMWSQLStore2::getSpecialSubjects-$specialprop (SMW)");
		$db =& wfGetDB( DB_SLAVE );

		$result = array();

		if ($specialprop === SMW_SP_HAS_CATEGORY) { // category membership
			if ( !($value instanceof Title) || ($value->getNamespace() != NS_CATEGORY) ) {
				wfProfileOut("SMWSQLStore2::getSpecialSubjects-$specialprop (SMW)");
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
					$result[] = $t;
				}
			}
			$db->freeResult($res);
		} elseif ($specialprop === SMW_SP_REDIRECTS_TO) { // redirections
			$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),'');
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
		} elseif ($specialprop === SMW_SP_SUBPROPERTY_OF) { // subproperties
			$oid = $this->getSMWPageID($value->getDBkey(),SMW_NS_PROPERTY,'');
			if ( ($oid != 0) && ($value->getNamespace() == SMW_NS_PROPERTY) ) {
				$res = $db->select( array('smw_subs2','smw_ids'), 'smw_title',
				                    's_id=smw_id AND o_id=' . $db->addQuotes($oid), 
				                    'SMW::getSpecialSubjects', $this->getSQLOptions($requestoptions) );
				while($row = $db->fetchObject($res)) {
					$result[] =  Title::makeTitle(SMW_NS_PROPERTY, $row->subject_title);
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
			$sid = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),'');
		}
		$pid = $this->getSMWPageID($property->getDBkey(), SMW_NS_PROPERTY,'');
		if ( ($sid == 0) || ($pid == 0)) {
			wfProfileOut("SMWSQLStore2::getPropertyValues (SMW)");
			return array();
		}
		$sd = $this->getSemanticData($subject,array(SMWDataValueFactory::getPropertyObjectTypeID($property)));
		$result = $this->applyRequestOptions($sd->getPropertyValues($property),$requestoptions);
		if ($outputformat != '') {
			foreach ($result as $dv) {
				$dv->setOutputFormat($outputformat);
			}
		}
		wfProfileOut("SMWSQLStore2::getPropertyValues (SMW)");
		return $result;
	}

	function getPropertySubjects(Title $property, $value, $requestoptions = NULL) {
		/// TODO: could we share code with #ask query computation here? Just use queries?
		wfProfileIn("SMWSQLStore2::getPropertySubjects (SMW)");
		$result = array();
		$pid = $this->getSMWPageID($property->getDBkey(), $property->getNamespace(),'');
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
				$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),'');
				$sql .= ' AND o_id=' . $db->addQuotes($oid);
			}
			if ( ($value === NULL) || ($oid != 0) ) {
				$table = 'smw_rels2';
			}
		break;
		case '__nry':
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
		$sid = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),'');
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
			$oid = $this->getSMWPageID($value->getDBkey(),$value->getNamespace(),'');
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
		wfProfileIn("SMWSQLStore2::deleteSubject (SMW)");
		$this->deleteSemanticData($subject);
		$db =& wfGetDB( DB_MASTER );
		///TODO: Possibly delete ID here (if not used in any place in rel)
		wfProfileOut("SMWSQLStore2::deleteSubject (SMW)");
	}

	function updateData(SMWSemanticData $data, $newpage) {
		wfProfileIn("SMWSQLStore2::updateData (SMW)");
		$subject = $data->getSubject();
		$this->deleteSemanticData($subject);
		$redirects = $data->getPropertyValues(SMW_SP_REDIRECTS_TO);
		if (count($redirects) > 0) {
			$redirect = current($redirects); // at most one redirect per page
			$this->updateRedirects($subject->getDBKey(),$subject->getNamespace(),$redirect->getDBKey(),$redirect->getNameSpace());
			wfProfileOut("SMWSQLStore2::updateData (SMW)");
			return; // stop here -- no support for annotations on redirect pages!
		} else {
			$this->updateRedirects($subject->getDBKey(),$subject->getNamespace());
		}
		$db =& wfGetDB( DB_MASTER );

		// do bulk updates:
		$up_rels2 = array();  $up_atts2 = array();
		$up_text2 = array();  $up_spec2 = array();
		$up_subs2 = array();

		//properties
		foreach($data->getProperties() as $key => $property) {
			$propertyValueArray = $data->getPropertyValues($property);
			if ($property instanceof Title) { // normal property
				foreach($propertyValueArray as $value) {
					if ($value->isValid()) {
						if ($value->getTypeID() == '_txt') {
							$up_text2[] =
								array( 's_id' => $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),''),
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'value_blob' => $value->getXSDValue() );
						} elseif ($value->getTypeID() == '_wpg') {
							$up_rels2[] =
								array( 's_id' => $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),''),
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'o_id' => $this->makeSMWPageID($value->getDBkey(),$value->getNamespace(),'') );
							$oid = $value->getArticleID();
						} elseif ($value->getTypeID() == '__nry') {
							$sid = $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),'');
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
										$oid = $dv->getArticleID();
										if ($oid == 0) { $oid = NULL; }
										$up_rels2[] =
											array( 's_id' => $bnode,
											       'p_id' => $pid,
											       'o_id' => $this->makeSMWPageID($dv->getDBkey(),$dv->getNamespace(),'') );
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
								array( 's_id' => $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),''),
								       'p_id' => $this->makeSMWPageID($property->getDBkey(),SMW_NS_PROPERTY,''),
								       'value_unit' => $value->getUnit(),
								       'value_xsd' => $value->getXSDValue(),
								       'value_num' => $value->getNumericValue() );
						}
					}
				}
			} else { // special property
				switch ($property) {
					case SMW_SP_IMPORTED_FROM: case SMW_SP_HAS_CATEGORY: case SMW_SP_REDIRECTS_TO:
						// don't store this, just used for display;
						/// TODO: filtering here is bad for fully neglected properties (IMPORTED FROM)
					break;
					case SMW_SP_SUBPROPERTY_OF:
						if ( $subject->getNamespace() != SMW_NS_PROPERTY ) {
							break;
						}
						foreach($propertyValueArray as $value) {
							if ( $value->getNamespace() == SMW_NS_PROPERTY )  {
								$up_subs2[] =
								array('s_id' => $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),''),
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
							array('s_id' => $this->makeSMWPageID($subject->getDBkey(),$subject->getNamespace(),''),
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

		wfProfileOut("SMWSQLStore2::updateData (SMW)");
	}

	function changeTitle(Title $oldtitle, Title $newtitle, $pageid, $redirid=0) {
		wfProfileIn("SMWSQLStore2::changeTitle (SMW)");
		// Note: this function ignores the given MediaWiki IDs (this store has its own IDs)
		$db =& wfGetDB( DB_MASTER );
		///FIXME: the below is not satisfactory yet; care for overwriting moves and handle equalities!
		// Keep ID, change title:
		$cond_array = array( 'smw_title' => $oldtitle->getDBkey(),
		                     'smw_namespace' => $oldtitle->getNamespace() );
		$val_array  = array( 'smw_title' => $newtitle->getDBkey(),
		                     'smw_namespace' => $newtitle->getNamespace());
		$db->update('smw_ids', $val_array, $cond_array, 'SMW::changeTitle');

		// properties need special treatment (special table layout)
		/// TODO
// 		if ( $oldtitle->getNamespace() == SMW_NS_PROPERTY ) {
// 			if ( $newtitle->getNamespace() == SMW_NS_PROPERTY ) {
// 				$db->update('smw_subprops', array('subject_title' => $newtitle->getDBkey()), array('subject_title' => $oldtitle->getDBkey()), 'SMW::changeTitle');
// 			} else {
// 				$db->delete('smw_subprops', array('subject_title' => $oldtitle->getDBkey()), 'SMW::changeTitle');
// 			}
// 		}

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

		wfProfileOut("SMWSQLStore2::changeTitle (SMW)");
	}

///// Query answering /////

	/**
	 * The SQL store's implementation of query answering.
	 *
	 * TODO: we now have sorting even for subquery conditions. Does this work? Is it slow/problematic?
	 * NOTE: we do not support category wildcards, as they have no useful semantics in OWL/RDFS/LP/whatever
	 */
	function getQueryResult(SMWQuery $query) {
		wfProfileIn('SMWSQLStore2::getQueryResult (SMW)');
		global $smwgQSortingSupport;
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deeper levels
		if ($query->querymode == SMWQuery::MODE_NONE) { // don't query, but return something to printer
			$result = new SMWQueryResult($prs, $query, false);
			wfProfileOut('SMWSQLStore2::getQueryResult (SMW)');
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
			wfProfileOut('SMWSQLStore2::getQueryResult (SMW)');
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
			wfProfileOut('SMWSQLStore2::getQueryResult (SMW)');
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
					case SMW_PRINT_THIS:
						$row[] = new SMWResultArray(array($qt), $pr);
						break;
					case SMW_PRINT_CATS:
						$row[] = new SMWResultArray($this->getSpecialValues($qt->getTitle(),SMW_SP_HAS_CATEGORY), $pr);
						break;
					case SMW_PRINT_PROP:
						$row[] = new SMWResultArray($this->getPropertyValues($qt->getTitle(),$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
						break;
					case SMW_PRINT_CCAT:
						$cats = $this->getSpecialValues($qt->getTitle(),SMW_SP_HAS_CATEGORY);
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
		wfProfileIn("SMWSQLStore2::getStatistics (SMW)");
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

		wfProfileOut("SMWSQLStore2::getStatistics (SMW)");
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
		                         'smw_spec2','smw_subs2','smw_redi2') );

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
					$op = $requestoptions->include_boundary?' >= ':' > ';
				} else {
					$op = $requestoptions->include_boundary?' <= ':' < ';
				}
				$sql_conds .= ' AND ' . $valuecol . $op . $db->addQuotes($requestoptions->boundary);
			}
			if ($labelcol !== NULL) { // apply string conditions
				foreach ($requestoptions->getStringConditions() as $strcond) {
					$string = str_replace(array('_', ' '), array('\_', '\_'), $strcond->string);
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
		$result = array();
		$sortres = array();
		$key = 0;
		if ( (count($data) == 0) || ($requestoptions === NULL) ) return $data;
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
				$label = $item->getPrefixedText();
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
		return $result;
	}


	/**
	 * Delete all semantic data stored for the given subject.
	 * Used for update purposes.
	 */
	protected function deleteSemanticData(Title $subject) {
		$db =& wfGetDB( DB_MASTER );
		// NOTE: redirects are handled by updateRedirects(), not here!
		//$db->delete('smw_redi2', array('s_title' => $subject->getDBkey(),'s_namespace' => $subject->getNamespace()), 'SMW::deleteSubject::Redi2');
		$id = $this->getSMWPageID($subject->getDBkey(), $subject->getNamespace(),'',false);
		if ($id == 0) return; // not used anywhere yet
		$db->delete('smw_rels2', array('s_id' => $id), 'SMW::deleteSubject::Rels2');
		$db->delete('smw_atts2', array('s_id' => $id), 'SMW::deleteSubject::Atts2');
		$db->delete('smw_text2', array('s_id' => $id), 'SMW::deleteSubject::Text2');
		$db->delete('smw_spec2', array('s_id' => $id), 'SMW::deleteSubject::Spec2');
		if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
			$db->delete('smw_subs2', array('s_id' => $id), 'SMW::deleteSubject::Subs2');
		}

		// find bnodes used by this ID ...
		$res = $db->select('smw_ids', 'smw_id','smw_title=' . $db->addQuotes('') . ' AND smw_namespace=' . $db->addQuotes($id) . ' AND smw_iw=' . $db->addQuotes(SMW_SQL2_SMWIW), 'SMW::deleteSubject::Nary');
		// ... and delete them recursively
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
		wfProfileIn("SMWSQLStore2::getCategoryTable (SMW)");
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

		$tablename = 'cats' . SMWSQLStore2::$m_tablenum++;
		$this->m_usedtables[] = $tablename;
		// TODO: unclear why this commit is needed -- is it a MySQL 4.x problem?
		$db->query("COMMIT");
		$db->query( 'CREATE TEMPORARY TABLE ' . $tablename .
		            '( title VARCHAR(255) binary NOT NULL PRIMARY KEY)
		             TYPE=MEMORY', 'SMW::getCategoryTable' );
		if (array_key_exists($hashkey, SMWSQLStore2::$m_categorytables)) { // just copy known result
			$db->query("INSERT INTO $tablename (title) SELECT " .
			            SMWSQLStore2::$m_categorytables[$hashkey] .
			            '.title FROM ' . SMWSQLStore2::$m_categorytables[$hashkey],
			           'SMW::getCategoryTable');
			wfProfileOut("SMWSQLStore2::getCategoryTable (SMW)");
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

		SMWSQLStore2::$m_categorytables[$hashkey] = $tablename;
		$db->query('DROP TEMPORARY TABLE smw_newcats', 'SMW::getCategoryTable');
		$db->query('DROP TEMPORARY TABLE smw_rescats', 'SMW::getCategoryTable');
		wfProfileOut("SMWSQLStore2::getCategoryTable (SMW)");
		return $tablename;
	}

	/**
	 * Make a (temporary) table that contains the lower closure of the given property
	 * wrt. the subproperty relation.
	 */
	protected function getPropertyTable($propname, &$db) {
		wfProfileIn("SMWSQLStore2::getPropertyTable (SMW)");
		global $smwgQSubpropertyDepth;

		$tablename = 'prop' . SMWSQLStore2::$m_tablenum++;
		$this->m_usedtables[] = $tablename;
		$db->query( 'CREATE TEMPORARY TABLE ' . $tablename .
		            '( title VARCHAR(255) binary NOT NULL PRIMARY KEY)
		             TYPE=MEMORY', 'SMW::getPropertyTable' );
		if (array_key_exists($propname, SMWSQLStore2::$m_propertytables)) { // just copy known result
			$db->query("INSERT INTO $tablename (title) SELECT " .
			            SMWSQLStore2::$m_propertytables[$propname] .
			            '.title FROM ' . SMWSQLStore2::$m_propertytables[$propname],
			           'SMW::getPropertyTable');
			wfProfileOut("SMWSQLStore2::getPropertyTable (SMW)");
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

		SMWSQLStore2::$m_propertytables[$propname] = $tablename;
		$db->query('DROP TEMPORARY TABLE smw_new', 'SMW::getPropertyTable');
		$db->query('DROP TEMPORARY TABLE smw_res', 'SMW::getPropertyTable');
		wfProfileOut("SMWSQLStore2::getPropertyTable (SMW)");
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
				$curtables['PAGE'] = 'p' . SMWSQLStore2::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('page') . ' AS ' . $curtables['PAGE'] . ' ON (' .
				           $curtables['PAGE'] . '.page_title=' . $curtables['pRELS'] . '.object_title AND ' .
				           $curtables['PAGE'] . '.page_namespace=' . $curtables['pRELS'] . '.object_namespace)';
				return $curtables['PAGE'];
			}
		} elseif ($tablename == 'CATS') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['CATS'] = 'cl' . SMWSQLStore2::$m_tablenum++;
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
				$curtables['RELS'] = 'rel' . SMWSQLStore2::$m_tablenum++;
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
				$curtables['ATTS'] = 'att' . SMWSQLStore2::$m_tablenum++;
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
				$curtables['TEXT'] = 'txt' . SMWSQLStore2::$m_tablenum++;
				$from .= ' INNER JOIN ' . $db->tableName('smw_longstrings') . ' AS ' . $curtables['TEXT'] . ' ON ' . $curtables['TEXT'] . '.subject_id=' . $id;
				return $curtables['TEXT'];
			}
		} elseif ($tablename == 'NARY') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['NARY'] = 'nary' . SMWSQLStore2::$m_tablenum++;
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
				$curtables['pATTS'] = 'natt' . SMWSQLStore2::$m_tablenum++;
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
				$curtables['pRELS'] = 'nrel' . SMWSQLStore2::$m_tablenum++;
				$cond = '(' .
				        $curtables['pRELS'] . '.subject_id=' . $curtables['pNARY'] . '.subject_id AND ' .
				        $curtables['pRELS'] . '.nary_key=' . $curtables['pNARY'] . '.nary_key AND ' .
				        $curtables['pRELS'] . '.nary_pos=' . $db->addQuotes($nary_pos) . ')';
				$from .= ' INNER JOIN ' . $db->tableName('smw_nary_relations') . ' AS ' . $curtables['pRELS'] . ' ON ' . $cond;
				return $curtables['pRELS'];
			}
		} elseif ($tablename == 'pTEXT') {
			if ( ($nary_pos !== '') && (array_key_exists('pNARY', $curtables)) ) {
				$curtables['pTEXT'] = 'ntxt' . SMWSQLStore2::$m_tablenum++;
				$cond = '(' .
				        $curtables['pTEXT'] . '.subject_id=' . $curtables['pNARY'] . '.subject_id AND ' .
				        $curtables['pTEXT'] . '.nary_key=' . $curtables['pNARY'] . '.nary_key AND ' .
				        $curtables['pTEXT'] . '.nary_pos=' . $db->addQuotes($nary_pos) . ')';
				$from .= ' INNER JOIN ' . $db->tableName('smw_nary_longstrings') . ' AS ' . $curtables['pTEXT'] . ' ON ' . $cond;
				return $curtables['pTEXT'];
			}
		} elseif ($tablename == 'REDIRECT') {
			if ($id = $this->getCurrentIDField($from, $db, $curtables, $nary_pos)) {
				$curtables['REDIRECT'] = 'rd' . SMWSQLStore2::$m_tablenum++;
				$from .= ' LEFT JOIN ' . $db->tableName('redirect') . ' AS ' . $curtables['REDIRECT'] . ' ON ' . $curtables['REDIRECT'] . '.rd_from=' . $id;
				return $curtables['REDIRECT'];
			}
		} elseif ($tablename == 'REDIPAGE') { // +another copy of page for getting ids of redirect targets; *ouch*
			if ($this->addJoin('REDIRECT', $from, $db, $curtables, $nary_pos)) {
				$curtables['REDIPAGE'] = 'rp' . SMWSQLStore2::$m_tablenum++;
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
	 * @TODO: Maybe there need to be optimisations in certain cases (atomic implementation for common nestings of descriptions?)
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
				case '_txt': // possibly pull in longstring table (for naries)
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
				// pull in page to prevent every child description pulling it seperately!
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
				case '_txt':
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
	protected function getSMWPageID($title, $namespace, $iw, $canonical=true) {
		$key = "$iw $namespace $title " . ($canonical?'C':'-');
		if (array_key_exists($key,$this->m_ids)) {
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
		return $id;
	}

	/**
	 * Find the numeric ID used for the page of the given title and namespace.
	 * If $canonical is set to true, redirects are taken into account to find the
	 * canonical alias ID for the given page.
	 * If no such ID exists, a new ID is created and returned.
	 */
	protected function makeSMWPageID($title, $namespace, $iw, $canonical=true) {
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
	 * Trigger all necessary updates for redirect structure on creation, change, and deletion
	 * of redirects. The title+namespace of the affected page and of its updated redirect 
	 * target are given. The target can be empty ('') if none is specified.
	 * Returns the canonical ID that is now to be used for the subject, or 0 if the subject did
	 * not occur anywhere yet.
	 */
	protected function updateRedirects($subject_t, $subject_ns, $curtarget_t='', $curtarget_ns=-1) {
		$sid = $this->getSMWPageID($subject_t, $subject_ns, '');
		$db =& wfGetDB( DB_SLAVE );
		$res = $db->select( array('smw_redi2'),'o_id','s_title=' . $db->addQuotes($subject_t) .
		                    ' AND s_namespace=' . $db->addQuotes($subject_ns),
		                    'SMW::updateRedirects', array('LIMIT' => 1) );
		if ($row = $db->fetchObject($res)) {
			$old_tid = $row->o_id;
		} else {
			$old_tid = 0;
		}
		$db->freeResult($res);
		if ($curtarget_t) {
			$new_tid = $this->makeSMWPageID($curtarget_t, $curtarget_ns, '');
		} else {
			$new_tid = 0;
		}
		if ($old_tid == $new_tid) { // no change, all happy
			return $sid;
		} elseif ( $old_tid == 0 ) { // new redirect, just change object entries of $sid to $new_tid
			$db =& wfGetDB( DB_MASTER );
			$sid = $this->getSMWPageID($subject_t, $subject_ns, '');
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
			}
		} else {
			$db =& wfGetDB( DB_MASTER );
			// there is an existing redirect, so we do not know which entries of $old_tid are now $new_tid/$sid
			// -> ask SMW to update all affected pages as soon as possible (using jobs)
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
			}
		}
		// finally, write the new redirect:
		if ($new_tid != 0) {
			$db->insert( 'smw_redi2', array('s_title'=>$subject_t, 's_namespace'=>$subject_ns, 'o_id'=>$new_tid), 'SMW::updateRedirects');
		}
		return ($new_tid==0)?$sid:$new_tid;
	}

}
