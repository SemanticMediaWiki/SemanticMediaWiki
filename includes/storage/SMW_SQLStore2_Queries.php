<?php
/**
 * Query answering functions for SMWSQLStore2. Separated frmo main code for readability and
 * for avoiding twice the amount of code being required on every use of a simple storage function.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWStore
 */

/// Types for query descriptions (comments refer to SMWSQLStore2Query class below):
define('SMW_SQL2_TABLE',1); // jointable: internal table name, joinfield, components, where use alias.fields, from uses external table names, components interpreted conjunctively (JOIN)
define('SMW_SQL2_VALUE',2); // joinfield (disjunctive) array of unquoted values, jointable empty, components empty
define('SMW_SQL2_DISJUNCTION',3); // joinfield, jointable empty, only components relevant
define('SMW_SQL2_CONJUNCTION',4); // joinfield, jointable empty, only components relevant
define('SMW_SQL2_CLASS_HIERARCHY',5); // only joinfield relevant: (disjunctive) array of unquoted values
define('SMW_SQL2_PROP_HIERARCHY',6); // only joinfield relevant: (disjunctive) array of unquoted values

/**
 * Class for representing a single (sub)query description. Simple data
 * container.
 */
class SMWSQLStore2Query {
	public $type = SMW_SQL2_TABLE;
	public $jointable = '';
	public $joinfield = '';
	public $from = '';
	public $where = '';
	public $components = array();
	public $alias; // the alias to be used for jointable; read-only after construct!
	public $sortfields = array(); // property dbkey => db field; passed down during query execution

	public static $qnum = 0;

	public function __construct() {
		$this->alias = 't' . SMWSQLStore2Query::$qnum++;
	}
}

/**
 * Class that implements query answering for SMWSQLStore2.
 */
class SMWSQLStore2QueryEngine {

	/// Database slave to be used
	protected $m_dbs; /// TODO: should temporary tables be created on the master DB?
	/// Parent SMWSQLStore2
	protected $m_store;
	/// Query mode copied from given query, some submethods act differently when in SMWQuery::MODE_DEBUG
	protected $m_qmode;
	/// Array of generated query descriptions
	protected $m_queries = array();
	/// Array of arrays of executed queries, indexed by the temporary table names results were fed into
	protected $m_querylog = array();
	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC". Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 */
	protected $m_sortkeys;
	/// Cache of computed hierarchy queries for reuse, cat/prop-value string => tablename
	protected $m_hierarchies = array();
	/// Local collection of error strings, passed on to callers if possible.
	protected $m_errors = array();

	public function __construct(&$parentstore, &$dbslave) {
		$this->m_store = $parentstore;
		$this->m_dbs = $dbslave;
	}

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function refreshConceptCache($concept) {
		global $smwgQMaxLimit, $smwgQConceptFeatures;
		$cid = $this->m_store->getSMWPageID($concept->getDBKey(), SMW_NS_CONCEPT, '');
		$cid_c = $this->m_store->getSMWPageID($concept->getDBKey(), SMW_NS_CONCEPT, '', false);
		if ($cid != $cid_c) {
			$this->m_errors[] = "Skipping redirect concept.";
			return $this->m_errors;
		}
		$dv = end($this->m_store->getSpecialValues($concept, SMW_SP_CONCEPT_DESC));
		$desctxt = ($dv!==false)?$dv->getWikiValue():false;
		$this->m_errors = array();
		if ($desctxt) { // concept found
			$this->m_qmode = SMWQuery::MODE_INSTANCES;
			$this->m_queries = array();
			$this->m_hierarchies = array();
			$this->m_querylog = array();
			$this->m_sortkeys = array();
			SMWSQLStore2Query::$qnum = 0;

			// Pre-process query:
			$qp = new SMWQueryParser($smwgQConceptFeatures);
			$desc = $qp->getQueryDescription($desctxt);
			$qid = $this->compileQueries($desc);
			$this->executeQueries($this->m_queries[$qid]); // execute query tree, resolve all dependencies
			$qobj = $this->m_queries[$qid];
			if ($qobj->joinfield === '') {
				return;
			}
			// Update database:
			$this->m_dbs->delete('smw_conccache', array('o_id' => $cid), 'SMW::refreshConceptCache');
			$this->m_dbs->query("INSERT IGNORE INTO " . $this->m_dbs->tableName('smw_conccache') .
			                    " SELECT DISTINCT $qobj->joinfield AS s_id, $cid AS o_id FROM " .
			                    $this->m_dbs->tableName($qobj->jointable) . " AS $qobj->alias" . $qobj->from .
			                    ($qobj->where?" WHERE ":'') . $qobj->where . " LIMIT $smwgQMaxLimit",
			                    'SMW::refreshConceptCache');
			$this->m_dbs->update('smw_conc2', array('cache_date' => strtotime("now"), 'cache_count' => $this->m_dbs->affectedRows()), array('s_id' => $cid), 'SMW::refreshConceptCache');
		} else { // just delete old data if there is any
			$this->m_dbs->delete('smw_conccache', array('o_id' => $cid), 'SMW::refreshConceptCache');
			$this->m_dbs->update('smw_conc2', array('cache_date' => NULL, 'cache_count' => NULL), array('s_id' => $cid), 'SMW::refreshConceptCache');
			$this->m_errors[] = "No concept description found.";
		}
		$this->cleanUp();
		return $this->m_errors;
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache($concept) {
		$cid = $this->m_store->getSMWPageID($concept->getDBKey(), SMW_NS_CONCEPT, '', false);
		$this->m_dbs->delete('smw_conccache', array('o_id' => $cid), 'SMW::refreshConceptCache');
		$this->m_dbs->update('smw_conc2', array('cache_date' => NULL, 'cache_count' => NULL), array('s_id' => $cid), 'SMW::refreshConceptCache');
	}

	/**
	 * The new SQL store's implementation of query answering.
	 */
	public function getQueryResult(SMWQuery $query) {
		if ($query->querymode == SMWQuery::MODE_NONE) { // don't query, but return something to printer
			$result = new SMWQueryResult($query->getDescription()->getPrintrequests(), $query, true);
			return $result;
		}
		$this->m_qmode = $query->querymode;
		$this->m_queries = array();
		$this->m_hierarchies = array();
		$this->m_querylog = array();
		$this->m_errors = array();
		SMWSQLStore2Query::$qnum = 0;
		$this->m_sortkeys = $query->sortkeys;
		// manually make final root query (to retrieve namespace,title):
		$rootid = SMWSQLStore2Query::$qnum;
		$qobj = new SMWSQLStore2Query();
		$qobj->jointable = 'smw_ids';
		$qobj->joinfield = "$qobj->alias.smw_id";
		// build query dependency tree:
		wfProfileIn('SMWSQLStore2Queries::compileMainQuery (SMW)');
		$qid = $this->compileQueries($query->getDescription()); // compile query, build query "plan"
		wfProfileOut('SMWSQLStore2Queries::compileMainQuery (SMW)');
		if ($qid >= 0) { // append to root
			$qobj->components = array($qid => "$qobj->alias.smw_id");
			$qobj->sortfields = $this->m_queries[$qid]->sortfields;
		}
		$this->m_queries[$rootid] = $qobj;

		$this->applyOrderConditions($query,$rootid); // may extend query if needed for sorting
		wfProfileIn('SMWSQLStore2Queries::executeMainQuery (SMW)');
		$this->executeQueries($this->m_queries[$rootid]); // execute query tree, resolve all dependencies
		wfProfileOut('SMWSQLStore2Queries::executeMainQuery (SMW)');
		switch ($query->querymode) {
			case SMWQuery::MODE_DEBUG:
				$result = $this->getDebugQueryResult($query,$rootid);
			break;
			case SMWQuery::MODE_COUNT:
				$result = $this->getCountQueryResult($query,$rootid);
			break;
			default:
				$result = $this->getInstanceQueryResult($query,$rootid);
			break;
		}
		$this->cleanUp();
		$query->addErrors($this->m_errors);
		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper debug output for the given query.
	 */
	protected function getDebugQueryResult($query,$rootid) {
		$qobj = $this->m_queries[$rootid];
		$sql_options = $this->getSQLOptions($query,$rootid);
		list( $startOpts, $useIndex, $tailOpts ) = $this->m_dbs->makeSelectOptions( $sql_options );
		$result = '<div style="border: 1px dotted black; background: #A1FB00; padding: 20px; ">' .
		          '<b>Debug output by SMWSQLStore2</b><br />' .
		          'Generated Wiki-Query<br /><tt>' .
		          str_replace('[', '&#x005B;', $query->getDescription()->getQueryString()) . '</tt><br />' .
		          'Query-Size: ' . $query->getDescription()->getSize() . '<br />' .
		          'Query-Depth: ' . $query->getDescription()->getDepth() . '<br />';
		if ($qobj->joinfield !== '') {
			$result .= 'SQL query<br />' .
			           "<tt>SELECT DISTINCT $qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns FROM " .
			           $this->m_dbs->tableName($qobj->jointable) . " AS $qobj->alias" . $qobj->from .
			           (($qobj->where=='')?'':' WHERE ') . $qobj->where . "$tailOpts LIMIT " .
			           $sql_options['LIMIT'] . ' OFFSET ' . $sql_options['OFFSET'] . ';</tt>';
		} else {
			$result .= '<b>Empty result, no SQL query created.</b>';
		}
		$errors = '';
		foreach ($query->getErrors() as $error) {
			$errors .= $error . '<br />';
		}
		$result .= ($errors)?"<br />Errors and warnings:<br />$errors":'<br />No errors or warnings.';
		$auxtables = '';
		foreach ($this->m_querylog as $table => $log) {
			$auxtables .= "<li>Temporary table $table";
			foreach ($log as $q) {
				$auxtables .= "<br />&nbsp;&nbsp;<tt>$q</tt>";
			}
			$auxtables .= '</li>';
		}
		$result .= ($auxtables)?"<br />Auxilliary tables used:<ul>$auxtables</ul>":'<br />No auxilliary tables used.';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper counting output for the given query.
	 */
	protected function getCountQueryResult($query,$rootid) {
		wfProfileIn('SMWSQLStore2Queries::getCountQueryResult (SMW)');
		$qobj = $this->m_queries[$rootid];
		if ($qobj->joinfield === '') { // empty result, no query needed
			wfProfileOut('SMWSQLStore2Queries::getCountQueryResult (SMW)');
			return 0;
		}
		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );
		$res = $this->m_dbs->select($this->m_dbs->tableName($qobj->jointable) . " AS $qobj->alias" . $qobj->from, "COUNT(DISTINCT $qobj->alias.smw_id) AS count", $qobj->where, 'SMW::getQueryResult', $sql_options);
		$row = $this->m_dbs->fetchObject($res);
		$count = $row->count;
		$this->m_dbs->freeResult($res);
		wfProfileOut('SMWSQLStore2Queries::getCountQueryResult (SMW)');
		return $count;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper result instance output for the given query.
	 */
	protected function getInstanceQueryResult($query,$rootid) {
		wfProfileIn('SMWSQLStore2Queries::getInstanceQueryResult (SMW)');
		$qobj = $this->m_queries[$rootid];
		if ($qobj->joinfield === '') { // empty result, no query needed
			$result = new SMWQueryResult($query->getDescription()->getPrintrequests(), $query, false);
			wfProfileOut('SMWSQLStore2Queries::getInstanceQueryResult (SMW)');
			return $result;
		}
		$sql_options = $this->getSQLOptions($query,$rootid);
		$res = $this->m_dbs->select($this->m_dbs->tableName($qobj->jointable) . " AS $qobj->alias" . $qobj->from, "DISTINCT $qobj->alias.smw_id AS id,$qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns,$qobj->alias.smw_iw AS iw", $qobj->where, 'SMW::getQueryResult', $sql_options);

		$qr = array();
		$count = 0;
		while ( ($count < $query->getLimit()) && ($row = $this->m_dbs->fetchObject($res)) ) {
			$count++;
			$v = SMWDataValueFactory::newTypeIDValue('_wpg');
			$v->setValues($row->t, $row->ns);
			$qr[] = $v;
			$this->m_store->cacheSMWPageID($row->id,$row->t,$row->ns,$row->iw);
		}
		if ($this->m_dbs->fetchObject($res)) {
			$count++;
		}
		$this->m_dbs->freeResult($res);

		// Create result by executing print statements for everything that was fetched
		///TODO: limit (and offset?) values for printouts?
		$prs = $query->getDescription()->getPrintrequests();
		$result = new SMWQueryResult($prs, $query, ($count > $query->getLimit()) );
		foreach ($qr as $qt) {
			$row = array();
			foreach ($prs as $pr) {
				switch ($pr->getMode()) {
				case SMWPrintRequest::PRINT_THIS:
					$row[] = new SMWResultArray(array($qt), $pr);
				break;
				case SMWPrintRequest::PRINT_CATS:
					$row[] = new SMWResultArray($this->m_store->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF), $pr);
				break;
				case SMWPrintRequest::PRINT_PROP:
					$row[] = new SMWResultArray($this->m_store->getPropertyValues($qt,$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
				break;
				case SMWPrintRequest::PRINT_CCAT:
					$cats = $this->m_store->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF);
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
		wfProfileOut('SMWSQLStore2Queries::getInstanceQueryResult (SMW)');
		return $result;
	}

	/**
	 * Create a new SMWSQLStore2Query object that can be used to obtain results for
	 * the given description. The result is stored in $this->m_queries using a numeric
	 * key that is returned as a result of the function. Returns -1 if no query was
	 * created.
	 */
	protected function compileQueries(SMWDescription $description) {
		$qid = SMWSQLStore2Query::$qnum;
		$query = new SMWSQLStore2Query();
		if ($description instanceof SMWSomeProperty) {
			$this->compilePropertyCondition($query, $description->getProperty(), $description->getDescription());
		} elseif ($description instanceof SMWNamespaceDescription) { /// TODO: One instance of smw_ids on s_id always suffices (swm_id is KEY)! Doable in execution ... (PERFORMANCE)
			$query->jointable = 'smw_ids';
			$query->joinfield = "$query->alias.smw_id";
			$query->where = "$query->alias.smw_namespace=" . $this->m_dbs->addQuotes($description->getNamespace());
		} elseif ( ($description instanceof SMWConjunction) || ($description instanceof SMWDisjunction) ) {
			$query->type = ($description instanceof SMWConjunction)?SMW_SQL2_CONJUNCTION:SMW_SQL2_DISJUNCTION;
			foreach ($description->getDescriptions() as $subdesc) {
				$sub = $this->compileQueries($subdesc);
				if ($sub >= 0) {
					$query->components[$sub] = true;
				}
			}
		} elseif ($description instanceof SMWClassDescription) {
			$cqid = SMWSQLStore2Query::$qnum;
			$cquery = new SMWSQLStore2Query();
			$cquery->type = SMW_SQL2_CLASS_HIERARCHY;
			$cquery->joinfield = array();
			foreach ($description->getCategories() as $cat) {
				$cid = $this->m_store->getSMWPageID($cat->getDBkey(), NS_CATEGORY, $cat->getInterwiki());
				if ($cid != 0) {
					$cquery->joinfield[] = $cid;
				}
			}
			if (count($cquery->joinfield) == 0) { // empty result
				$query->type = SMW_SQL2_VALUE;
				$query->jointable = '';
				$query->joinfield = '';
			} else { // instance query with dicjunction of classes (categories)
				$query->jointable = 'smw_inst2';
				$query->joinfield = "$query->alias.s_id";
				$query->components[$cqid] = "$query->alias.o_id";
				$this->m_queries[$cqid] = $cquery;
			}
		} elseif ($description instanceof SMWValueDescription) { // only processsed here for '_wpg'
			if ($description->getDatavalue()->getTypeID() == '_wpg') {
				if ($description->getComparator() == SMW_CMP_EQ) {
					$query->type = SMW_SQL2_VALUE;
					$oid = $this->m_store->getSMWPageID($description->getDatavalue()->getDBkey(), $description->getDatavalue()->getNamespace(),$description->getDatavalue()->getInterwiki());
					$query->joinfield = array($oid);
				} else { // join with smw_ids needed for other comparators (apply to title string)
					$query->jointable = 'smw_ids';
					$query->joinfield = "$query->alias.smw_id";
					$value = $description->getDatavalue()->getSortkey();
					switch ($description->getComparator()) {
						case SMW_CMP_LEQ: $comp = '<='; break;
						case SMW_CMP_GEQ: $comp = '>='; break;
						case SMW_CMP_NEQ: $comp = '!='; break;
						case SMW_CMP_LIKE:
							$comp = ' LIKE ';
							$value =  str_replace(array('%', '_', '*', '?'), array('\%', '\_', '%', '_'), $value);
						break;
					}
					$query->where = "$query->alias.smw_sortkey$comp" . $this->m_dbs->addQuotes($value);
				}
			}
		} elseif ($description instanceof SMWConceptDescription) { // fetch concept definition and insert it here
			$cid = $this->m_store->getSMWPageID($description->getConcept()->getDBKey(), SMW_NS_CONCEPT, '');
			$row = $this->m_dbs->selectRow('smw_conc2',
			         array('concept_txt','concept_features','concept_size','concept_depth','cache_date'),
			         array('s_id'=>$cid), 'SMWSQLStore2Queries::compileQueries');
			if ( $row === false ) { // no description found, concept does not exist
				// keep the above query object, it yields an empty result
				///TODO: announce an error here? (maybe not, since the query processor can check for 
				///non-existing concept pages which is probably the main reason for finding nothing here
			} else {
				global $smwgQConceptCaching, $smwgQMaxSize, $smwgQMaxDepth, $smwgQFeatures, $smwgQConceptCacheLifetime;
				$may_be_computed = ($smwgQConceptCaching == 2) ||
				    ( ($smwgQConceptCaching == 1) && ( (~(~($row->concept_features+0) | $smwgQFeatures)) == 0) && 
				      ($smwgQMaxSize >= $row->concept_size) && ($smwgQMaxDepth >= $row->concept_depth));
				if ($row->cache_date &&
				    ($row->cache_date > (strtotime("now") - $smwgQConceptCacheLifetime*60) ||
				     !$may_be_computed)) { // cached concept, use cache unless it is dead and can be revived
					$query->jointable = 'smw_conccache';
					$query->joinfield = "$query->alias.s_id";
					$query->where = "$query->alias.o_id=" . $this->m_dbs->addQuotes($cid);
				} elseif ($row->concept_txt) { // parse description and process it recursively
					if ($may_be_computed) {
						$qp = new SMWQueryParser();
						// no defaultnamespaces here; if any, these are already in the concept
						$desc = $qp->getQueryDescription($row->concept_txt);
						$qid = $this->compileQueries($desc);
						$query = $this->m_queries[$qid];
					} else {
						wfLoadExtensionMessages('SemanticMediaWiki');
						$this->m_errors[] = wfMsg('smw_concept_cache_miss',$description->getConcept()->getText());
					}
				} // else: no cache, no description (this may happen); treat like empty concept
			}
		} else { // (e.g. SMWThingDescription, SMWValueList is also treated elswhere)
			$qid = -1; // no condition
		}
		if ($qid >= 0) {
			$this->m_queries[$qid] = $query;
		}
		if ($query->type != SMW_SQL2_DISJUNCTION) { // sortkeys are killed by disjunctions (not all parts may have them), preprocessing might try to push disjunctions downwards to safe sortkey
			foreach ($query->components as $cid => $field) {
				$query->sortfields = array_merge($this->m_queries[$cid]->sortfields,$query->sortfields);
			}
		}
		return $qid;
	}

	/**
	 * Modify the given query object to account for some property condition for the given property.
	 * The parameter $property may be a Title object or an internal storage id. This is what makes
	 * this method useful: it can be used even with internal properties that have no MediaWiki Title.
	 * $typeid is set if property ids are used, since internal properties may not have a defined type.
	 */
	protected function compilePropertyCondition(&$query, $property, SMWDescription $valuedesc, $typeid=false) {
		$query->joinfield = "$query->alias.s_id";
		if ($property instanceof Title) {
			$typeid = SMWDataValueFactory::getPropertyObjectTypeID($property);
			$pid = $this->m_store->getSMWPageID($property->getDBkey(), $property->getNamespace(),$property->getInterwiki());
			$sortkey = $property->getDBkey();
			// also make property hierarchy
			$pqid = SMWSQLStore2Query::$qnum;
			$pquery = new SMWSQLStore2Query();
			$pquery->type = SMW_SQL2_PROP_HIERARCHY;
			$pquery->joinfield = array($pid);
			$query->components[$pqid] = "$query->alias.p_id";
			$this->m_queries[$pqid] = $pquery;
		} else {
			$pid = $property;
			$sortkey = false;
			// no property hierarchy
			$query->where = "$query->alias.p_id=" . $this->m_dbs->addQuotes($pid);
		}
		$sortfield = ''; // used if we should sort by this property
		switch ($typeid) {
			case '_wpg': // subconditions as subqueries (compiled)
				$query->jointable = 'smw_rels2';
				$sub = $this->compileQueries($valuedesc);
				if ($sub >= 0) {
					$query->components[$sub] = "$query->alias.o_id";
				}
				if ( $sortkey && array_key_exists($sortkey, $this->m_sortkeys) ) {
					$query->from = ' INNER JOIN ' . $this->m_dbs->tableName('smw_ids') . " AS ids$query->alias ON ids$query->alias.smw_id=$query->alias.o_id";
					$sortfield = "ids$query->alias.smw_title"; /// TODO: as below, smw_ids here is possibly duplicated! Can we prevent that? (PERFORMANCE)
				}
			break;
			case '__nry':
				$query->jointable = 'smw_rels2';
				if ($valuedesc instanceof SMWValueList) { // anything else is ignored!
					$typevalue = SMWDataValueFactory::getPropertyObjectTypeValue($property);
					$typelabels = $typevalue->getTypeLabels();
					reset($typelabels);
					$subqid = SMWSQLStore2Query::$qnum;
					$subquery = new SMWSQLStore2Query();
					$subquery->type = SMW_SQL2_CONJUNCTION;
					$query->components[$subqid] = "$query->alias.o_id";
					$this->m_queries[$subqid] = $subquery;
					for ($i=0; $i<$valuedesc->getCount(); $i++) {
						$desc = $valuedesc->getDescription($i);
						if ($desc !== NULL) {
							$stypeid = SMWDataValueFactory::findTypeID(current($typelabels));
							$valpid = $this->m_store->getSMWPageID(strval($i),SMW_NS_PROPERTY,SMW_SQL2_SMWIW);
							$valqid = SMWSQLStore2Query::$qnum;
							$valquery = new SMWSQLStore2Query();
							$this->compilePropertyCondition($valquery, $valpid, $desc, $stypeid);
							$subquery->components[$valqid] = true;
							$this->m_queries[$valqid] = $valquery;
						}
						next($typelabels);
					}
				}
			break;
			case '_txt': case '_cod': // no subconditions
				$query->jointable = 'smw_text2';
			break;
			default: // subquery only conj/disj of values, compile to single "where"
				$query->jointable = 'smw_atts2';
				$aw = $this->compileAttributeWhere($valuedesc,"$query->alias");
				if ($aw != '') {
					$query->where .= ($query->where?' AND ':'') . $aw;
				}
				if ( $sortkey && array_key_exists($sortkey, $this->m_sortkeys) ) {
					$sortfield = "$query->alias." .  (SMWDataValueFactory::newTypeIDValue($typeid)->isNumeric()?'value_num':'value_xsd');
				}
		}
		if ($sortfield) {
			$query->sortfields[$sortkey] = $sortfield;
		}
	}

	/**
	 * Given an SMWDescription that is just a conjunction or disjunction of
	 * SMWValueDescription objects, create a plain WHERE condition string for it.
	 */
	protected function compileAttributeWhere(SMWDescription $description, $jointable) {
		if ($description instanceof SMWValueDescription) {
			$dv = $description->getDatavalue();
			$value = $dv->isNumeric() ? $dv->getNumericValue() : $dv->getXSDValue();
			$field = $dv->isNumeric() ? "$jointable.value_num" : "$jointable.value_xsd";
			switch ($description->getComparator()) {
				case SMW_CMP_LEQ: $comp = '<='; break;
				case SMW_CMP_GEQ: $comp = '>='; break;
				case SMW_CMP_NEQ: $comp = '!='; break;
				case SMW_CMP_LIKE:
					if ($dv->getTypeID() == '_str') {
						$comp = ' LIKE ';
						$value =  str_replace(array('%', '_', '*', '?'), array('\%', '\_', '%', '_'), $value);
					} else { // LIKE only works for strings at the moment
						$comp = '=';
					}
				break;
				case SMW_CMP_EQ: default: $comp = '='; break;
			}
			$result = "$field$comp" . $this->m_dbs->addQuotes($value);
		} elseif ( ($description instanceof SMWConjunction) || ($description instanceof SMWDisjunction) ) {
			$op = ($description instanceof SMWConjunction) ? ' AND ' : ' OR ';
			$result = '';
			foreach ($description->getDescriptions() as $subdesc) {
				$result= $result . ( $result!=''?$op:'' ) . $this->compileAttributeWhere($subdesc,$jointable);
			}
			$result = "($result)";
		} else {
			$result = '';
		}
		return $result;
	}

	/**
	 * Process stored queries and change store accordingly. The query obj is modified
	 * so that it contains non-recursive description of a select to execute for getting
	 * the actual result.
	 */
	protected function executeQueries(SMWSQLStore2Query &$query) {
		switch ($query->type) {
			case SMW_SQL2_TABLE: // normal query with conjunctive subcondition
				foreach ($query->components as $qid => $joinfield) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries($subquery);
					if ($subquery->jointable != '') { // join with jointable.joinfield
						$query->from .= ' INNER JOIN ' . $this->m_dbs->tableName($subquery->jointable) . " AS $subquery->alias ON $joinfield=" . $subquery->joinfield;
					} elseif ($subquery->joinfield !== '') { // require joinfield as "value" via WHERE
						$condition = '';
						foreach ($subquery->joinfield as $value) {
							$condition .= ($condition?' OR ':'') . "$joinfield=" . $this->m_dbs->addQuotes($value);
						}
						if (count($subquery->joinfield) > 1) {
							$condition = "($condition)";
						}
						$query->where .= (($query->where == '')?'':' AND ') . $condition;
					} else { // interpret empty joinfields as impossible condition (empty result)
						$query->joinfield = ''; // make whole query false
						$query->jointable = '';
						$query->where = '';
						$query->from = '';
						break;
					}
					if ($subquery->where != '') {
						$query->where .= (($query->where == '')?'':' AND ') . $subquery->where;
					}
					$query->from .= $subquery->from;
				}
				$query->components = array();
			break;
			case SMW_SQL2_CONJUNCTION:
				// pick one subquery with jointable as anchor point ...
				reset($query->components);
				$key = false;
				foreach ($query->components as $qkey => $qid) {
					if ($this->m_queries[$qkey]->jointable != '') {
						$key = $qkey;
						break;
					}
				}
				if ($key !== false) {
					$result = $this->m_queries[$key];
					unset($query->components[$key]);
					$this->executeQueries($result); // execute it first (may change jointable and joinfield, e.g. when making temporary tables)
					// ... and append to this query the remaining queries
					foreach ($query->components as $qid => $joinfield) {
						$result->components[$qid] = $result->joinfield;
					}
					$this->executeQueries($result); // second execute, now incorporating remaining conditions
				} else { // only fixed values in conjunction, make a new value without joining
					$key = $qkey;
					$result = $this->m_queries[$key];
					unset($query->components[$key]);
					foreach ($query->components as $qid => $joinfield) {
						if ($result->joinfield != $this->m_queries[$qid]->joinfield) {
							$result->joinfield = ''; // all other values should already be ''
							break;
						}
					}
				}
				$query = $result;
			break;
			case SMW_SQL2_DISJUNCTION:
				if ($this->m_qmode !== SMWQuery::MODE_DEBUG) {
					$this->m_dbs->query( "CREATE TEMPORARY TABLE " . $this->m_dbs->tableName($query->alias) .
					                     ' ( id INT UNSIGNED KEY ) TYPE=MEMORY', 'SMW::executeQueries' );
				}
				$this->m_querylog[$query->alias] = array();
				foreach ($query->components as $qid => $joinfield) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries($subquery);
					$sql = '';
					if ($subquery->jointable != '') {
						$sql = "INSERT IGNORE INTO " . $this->m_dbs->tableName($query->alias) . " SELECT $subquery->joinfield FROM " .
						$this->m_dbs->tableName($subquery->jointable) . " AS $subquery->alias $subquery->from" . ($subquery->where?" WHERE $subquery->where":'');
					} elseif ($subquery->joinfield !== '') {
						/// NOTE: this works only for single "unconditional" values without further 
						/// WHERE or FROM. The execution must take care of not creating any others.
						$values = '';
						foreach ($subquery->joinfield as $value) {
							$values .= ($values?',':'') . '(' . $this->m_dbs->addQuotes($value) . ')';
						}
						$sql = "INSERT IGNORE INTO " . $this->m_dbs->tableName($query->alias) . " (id) VALUES $values";
					} // else: // interpret empty joinfields as impossible condition (empty result), ignore
					if ($sql) {
						$this->m_querylog[$query->alias][] = $sql;
						if ($this->m_qmode !== SMWQuery::MODE_DEBUG) {
							$this->m_dbs->query($sql , 'SMW::executeQueries');
						}
					}
				}
				$query->jointable = $query->alias;
				$query->joinfield = "$query->alias.id";
				$query->sortfields = array(); // make sure we got no sortfields
				/// TODO: currently this eliminates sortkeys, possibly keep them (needs different temp table format though, maybe not such a good thing to do)
			break;
			case SMW_SQL2_PROP_HIERARCHY: case SMW_SQL2_CLASS_HIERARCHY: // make a saturated hierarchy
				$this->executeHierarchyQuery($query);
			break;
			case SMW_SQL2_VALUE: break; // nothing to do
		}
	}
	
	/**
	 * Find subproperties or subcategories. This may require iterative computation,
	 * and temporary tables are used in many cases.
	 */
	protected function executeHierarchyQuery(&$query) {
		$fname = "SMWSQLStore2Queries::executeQueries-hierarchy-$query->type (SMW)";
		wfProfileIn($fname);
		global $smwgQSubpropertyDepth, $smwgQSubcategoryDepth;
		$depth = ($query->type == SMW_SQL2_PROP_HIERARCHY)?$smwgQSubpropertyDepth:$smwgQSubcategoryDepth;
		if ($depth <= 0) { // treat as value, no recursion
			$query->type = SMW_SQL2_VALUE;
			wfProfileOut($fname);
			return;
		}
		$values = '';
		$valuecond = '';
		foreach ($query->joinfield as $value) {
			$values .= ($values?',':'') . '(' . $this->m_dbs->addQuotes($value) . ')';
			$valuecond .= ($valuecond?' OR ':'') . 'o_id=' . $this->m_dbs->addQuotes($value);
		}
		$smw_subs2 = $this->m_dbs->tableName('smw_subs2');
		// try to safe time (SELECT is cheaper than creating/dropping 3 temp tables):
		$res = $this->m_dbs->select($smw_subs2,'s_id',$valuecond, array('LIMIT'=>1));
		if (!$this->m_dbs->fetchObject($res)) { // no subobjects, we are done!
			$this->m_dbs->freeResult($res);
			$query->type = SMW_SQL2_VALUE;
			wfProfileOut($fname);
			return;
		}
		$this->m_dbs->freeResult($res);
		$tablename = $this->m_dbs->tableName($query->alias);
		$this->m_querylog[$query->alias] = array("Recursively computed hierarchy for element(s) $values.");
		$query->jointable = $query->alias;
		$query->joinfield = "$query->alias.id";
		if ($this->m_qmode == SMWQuery::MODE_DEBUG) {
			wfProfileOut($fname);
			return; // no real queries in debug mode
		}

		$this->m_dbs->query( "CREATE TEMPORARY TABLE $tablename " .
								'( id INT UNSIGNED NOT NULL KEY) TYPE=MEMORY', 'SMW::executeQueries' );
		if (array_key_exists($values, $this->m_hierarchies)) { // just copy known result
			$this->m_dbs->query("INSERT INTO $tablename (id) SELECT id" .
								' FROM ' . $this->m_hierarchies[$values],
								'SMW::executeQueries');
			wfProfileOut($fname);
			return;
		}

		/// NOTE: we use two helper tables. One holds the results of each new iteration, one holds the
		/// results of the previous iteration. One could of course do with only the above result table,
		/// but then every iteration would use all elements of this table, while only the new ones 
		/// obtained in the previous step are relevant. So this is a performance measure.
		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';
		$this->m_dbs->query( "CREATE TEMPORARY TABLE $tmpnew " .
					'( id INT UNSIGNED ) TYPE=MEMORY', 'SMW::executeQueries' );
		$this->m_dbs->query( "CREATE TEMPORARY TABLE $tmpres " .
					'( id INT UNSIGNED ) TYPE=MEMORY', 'SMW::executeQueries' );

		$this->m_dbs->query("INSERT IGNORE INTO $tablename (id) VALUES $values", 'SMW::executeQueries');
		$this->m_dbs->query("INSERT IGNORE INTO $tmpnew (id) VALUES $values", 'SMW::executeQueries');

		for ($i=0; $i<$depth; $i++) {
			$this->m_dbs->query("INSERT INTO $tmpres (id) SELECT s_id FROM $smw_subs2, $tmpnew WHERE o_id=id",
						'SMW::executeQueries');
			if ($this->m_dbs->affectedRows() == 0) { // no change, exit loop
				break;
			}
			$this->m_dbs->query("INSERT IGNORE INTO $tablename (id) SELECT $tmpres.id FROM $tmpres",
						'SMW::executeQueries');
			if ($this->m_dbs->affectedRows() == 0) { // no change, exit loop
				break;
			}
			$this->m_dbs->query('TRUNCATE TABLE ' . $tmpnew, 'SMW::executeQueries'); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}
		$this->m_hierarchies[$values] = $tablename;
		$this->m_dbs->query('DROP TEMPORARY TABLE smw_new', 'SMW::executeQueries');
		$this->m_dbs->query('DROP TEMPORARY TABLE smw_res', 'SMW::executeQueries');
		wfProfileOut($fname);
	}

	/**
	 * This function modifies the given query object at $qid to account for all ordering conditions 
	 * in the SMWQuery $query. It is always required that $qid is the id of a query that joins with 
	 * smw_ids so that the field alias.smw_title is $available for default sorting.
	 */
	protected function applyOrderConditions($query, $qid) {
		global $smwgQSortingSupport;
		if ( !$smwgQSortingSupport ) {
			return;
		}
		$qobj = $this->m_queries[$qid];
		// (1) collect required extra property descriptions:
		$extraproperties = array();
		foreach ($this->m_sortkeys as $propkey => $order) {
			if (!array_key_exists($propkey,$qobj->sortfields)) { // find missing property to sort by
				if ($propkey == '') { // sort by first result column (page titles)
					$qobj->sortfields[$propkey] = "$qobj->alias.smw_sortkey";
				} else { // try to extend query
					$extrawhere = '';
					$sorttitle = Title::newFromText($propkey, SMW_NS_PROPERTY);
					if ($sorttitle !== NULL) { // careful, Title creation might still fail!
						$extraproperties[] = new SMWSomeProperty($sorttitle, new SMWThingDescription());
					}
				}
			}
		}
		// (2) compile according conditions and hack them into $qobj:
		if (count($extraproperties) > 0) {
			$desc = new SMWConjunction($extraproperties);
			$newqid = $this->compileQueries($desc);
			$newqobj = $this->m_queries[$newqid]; // this is always an SMW_SQL2_CONJUNCTION ...
			foreach ($newqobj->components as $cid => $field) { // ... so just re-wire its dependencies
				$qobj->components[$cid] = $qobj->joinfield;
				$qobj->sortfields = array_merge($qobj->sortfields, $this->m_queries[$cid]->sortfields);
			}
			$this->m_queries[$qid] = $qobj;
		}
	}

	/**
	 * Get a SQL option array for the given query and preprocessed query object at given id.
	 */
	protected function getSQLOptions($query,$rootid) {
		global $smwgQSortingSupport;
		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );
		// build ORDER BY options using discovered sorting fields:
		if ($smwgQSortingSupport) {
			$qobj = $this->m_queries[$rootid];
			foreach ($this->m_sortkeys as $propkey => $order) {
				if (array_key_exists($propkey,$qobj->sortfields)) { // field successfully added
					if (!array_key_exists('ORDER BY', $result)) {
						$result['ORDER BY'] = '';
					} else {
						$result['ORDER BY'] .= ', ';
					}
					$result['ORDER BY'] .= $qobj->sortfields[$propkey] . " $order ";
				}
			}
		}
		return $result;
	}

	/**
	 * After querying, make sure no temporary database tables are left.
	 */
	protected function cleanUp() {
		if ($this->m_qmode !== SMWQuery::MODE_DEBUG) {
			foreach ($this->m_querylog as $table => $log) {
				$this->m_dbs->query("DROP TEMPORARY TABLE " . $this->m_dbs->tableName($table), 'SMW::getQueryResult');
			}
		}
	}

}