<?php
/**
 * Query answering functions for SMWSQLStore2. Separated frmo main code for readability and
 * for avoiding twice the amount of code being required on every use of a simple storage function.
 *
 * @author Markus Krötzsch
 */

// Types for query descriptions
define('SMW_SQL2_TABLE',1);
define('SMW_SQL2_VALUE',2);
define('SMW_SQL2_DISJUNCTION',7);
define('SMW_SQL2_CONJUNCTION',8);

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

	public function __construct(&$parentstore, &$dbslave) {
		$this->m_store = $parentstore;
		$this->m_dbs = $dbslave;
	}

	/**
	 * The new SQL store's implementation of query answering.
	 *
	 * NOTE: we do not support category wildcards as they have no useful semantics in OWL/RDFS/LP/Whatever
	 */
	public function getQueryResult(SMWQuery $query) {
		global $smwgQSortingSupport;
		if ($query->querymode == SMWQuery::MODE_NONE) { // don't query, but return something to printer
			$result = new SMWQueryResult($query->getDescription()->getPrintrequests(), $query, false);
			return $result;
		}
		$this->m_queries = array();
		$this->m_querylog = array();
		SMWSQLStore2Query::$qnum = 0;
		$this->m_sortkeys = $query->sortkeys;
		// manually make final root query (to retrieve namespace,title):
		$rootid = SMWSQLStore2Query::$qnum;
		$qobj = new SMWSQLStore2Query();
		$qobj->jointable = 'smw_ids';
		$qobj->joinfield = "$qobj->alias.smw_id";
		// build query dependency tree:
		$qid = $this->compileQueries($query->getDescription());
		if ($qid >= 0) { // append to root
			$qobj->components = array($qid => "$qobj->alias.smw_id");
			$qobj->sortfields = $this->m_queries[$qid]->sortfields;
		}
		$this->m_queries[$rootid] = $qobj;

		$this->applyOrderConditions($query,$rootid); // may extend query if needed for sorting
		$this->executeQueries($this->m_queries[$rootid]); // execute query tree, resolve all dependencies
		/// TODO: the above needs to know whether we are in debug mode or not
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
		// finally, free temporary tables
		foreach ($this->m_querylog as $table => $log) {
			$this->m_dbs->query("DROP TEMPORARY TABLE $table", 'SMW::getQueryResult');
		}
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
		          '<b>Generated Wiki-Query</b><br />' .
		          str_replace('[', '&#x005B;', $query->getDescription()->getQueryString()) . '<br />' .
		          '<b>Query-Size: </b>' . $query->getDescription()->getSize() . '<br />' .
		          '<b>Query-Depth: </b>' . $query->getDescription()->getDepth() . '<br />';
		if ($qobj->joinfield !== '') {
			$result .= '<b>SQL query</b><br />' .
			           "SELECT DISTINCT $qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns FROM " .
			           "$qobj->jointable AS $qobj->alias" . $qobj->from . (($qobj->where=='')?'':' WHERE ') .
			           $qobj->where . "$tailOpts LIMIT " . $sql_options['LIMIT'] . ' OFFSET ' .
			           $sql_options['OFFSET'] . ';';
		} else {
			$result .= '<b>Empty result, no SQL query created.</b>';
		}
		$errors = '';
		foreach ($query->getErrors() as $error) {
			$errors .= $error . '<br />';
		}
		$result .= ($errors)?"<br /><b>Errors and warnings:</b><br />$errors":'<br /><b>No errors or warnings.</b>';
		$auxtables = '';
		foreach ($this->m_querylog as $table => $log) {
			$auxtables .= "\n\n<b>Temporary table $table</b>";
			foreach ($log as $q) {
				$auxtables .= "\n\n$q";
			}
		}
		$result .= ($auxtables)?"<br /><b>Auxilliary tables used:</b><br />$auxtables":'<br /><b>No auxilliary tables used.</b>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper counting output for the given query.
	 */
	protected function getCountQueryResult($query,$rootid) {
		$qobj = $this->m_queries[$rootid];
		if ($qobj->joinfield === '') { // empty result, no query needed
			return 0;
		}
		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );
		$res = $this->m_dbs->select($this->m_dbs->tableName($qobj->jointable) . " AS $qobj->alias" . $qobj->from, "COUNT(DISTINCT $qobj->alias.smw_id) AS count", $qobj->where, 'SMW::getQueryResult', $sql_options);
		$row = $this->m_dbs->fetchObject($res);
		$count = $row->count;
		$this->m_dbs->freeResult($res);
		return $count;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper result instance output for the given query.
	 */
	protected function getInstanceQueryResult($query,$rootid) {
		$qobj = $this->m_queries[$rootid];
		if ($qobj->joinfield === '') { // empty result, no query needed
			$result = new SMWQueryResult($query->getDescription()->getPrintrequests(), $query, false);
			return $result;
		}
		$sql_options = $this->getSQLOptions($query,$rootid);
		$res = $this->m_dbs->select($this->m_dbs->tableName($qobj->jointable) . " AS $qobj->alias" . $qobj->from, "DISTINCT $qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns", $qobj->where, 'SMW::getQueryResult', $sql_options);

		$qr = array();
		$count = 0;
		while ( ($count < $query->getLimit()) && ($row = $this->m_dbs->fetchObject($res)) ) {
			$count++;
			$v = SMWDataValueFactory::newTypeIDValue('_wpg');
			$v->setValues($row->t, $row->ns);
			$qr[] = $v;
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
				case SMW_PRINT_THIS:
					$row[] = new SMWResultArray(array($qt), $pr);
				break;
				case SMW_PRINT_CATS:
					$row[] = new SMWResultArray($this->m_store->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF), $pr);
				break;
				case SMW_PRINT_PROP:
					$row[] = new SMWResultArray($this->m_store->getPropertyValues($qt->getTitle(),$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
				break;
				case SMW_PRINT_CCAT:
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
			$typeid = SMWDataValueFactory::getPropertyObjectTypeID($description->getProperty());
			$query->joinfield = "$query->alias.s_id";
			$pid = $this->m_store->getSMWPageID($description->getProperty()->getDBkey(), $description->getProperty()->getNamespace(),'');
			$query->where = "$query->alias.p_id=" . $this->m_dbs->addQuotes($pid);
			$sortfield = ''; // used if we should sort by this property
			switch ($typeid) {
				case '_wpg': case '__nry': // subconditions as subqueries (compiled)
					$query->jointable = 'smw_rels2';
					$sub = $this->compileQueries($description->getDescription());
					if ($sub >= 0) {
						$query->components = array($sub => "$query->alias.o_id");
					}
				break;
				case '_txt': // no subconditions
					$query->jointable = 'smw_text2';
				break;
				default: // subquery only conj/disj of values, compile to single "where"
					$query->jointable = 'smw_atts2';
					$aw = $this->compileAttributeWhere($description->getDescription(),"$query->alias");
					if ($aw != '') {
						$query->where .= " AND $aw";
					}
					if ( array_key_exists($description->getProperty()->getDBkey(), $this->m_sortkeys) ) {
						$sortfield = "$query->alias." .  (SMWDataValueFactory::newTypeIDValue($typeid)->isNumeric()?'value_num':'value_xsd');
					}
			}
			if ($sortfield) {
				$query->sortfields[$description->getProperty()->getDBkey()] = $sortfield;
			}
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
			$query->jointable = 'smw_inst2';
			$query->joinfield = "$query->alias.s_id";
			$where = '';
			foreach ($description->getCategories() as $cat) {
				$cid = $this->m_store->getSMWPageID($cat->getDBkey(), NS_CATEGORY, '');
				$where .= ($where == ''?'':' OR ') . "$query->alias.o_id=" . $this->m_dbs->addQuotes($cid);
			}
			if (count($description->getCategories()) > 1) {
				$where = "($where)";
			}
			$query->where = $where;
		} elseif ($description instanceof SMWValueList) {
			$qid = -1; /// TODO
		} elseif ($description instanceof SMWValueDescription) { // only processsed here for '_wpg'
			if ($description->getDatavalue()->getTypeID() == '_wpg') {
				if ($description->getComparator() == SMW_CMP_EQ) {
					$query->type = SMW_SQL2_VALUE;
					$oid = $this->m_store->getSMWPageID($description->getDatavalue()->getDBkey(), $description->getDatavalue()->getNamespace(),'');
					$query->joinfield = $oid;
				} else { // join with smw_ids needed for other comparators (apply to title string)
					$query->jointable = 'smw_ids';
					$query->joinfield = "$query->alias.smw_id";
					$value = $description->getDatavalue()->getDBKey();
					switch ($description->getComparator()) {
						case SMW_CMP_LEQ: $comp = '<='; break;
						case SMW_CMP_GEQ: $comp = '>='; break;
						case SMW_CMP_NEQ: $comp = '!='; break;
						case SMW_CMP_LIKE:
							$comp = ' LIKE ';
							$value =  str_replace(array('%', '_', '*', '?'), array('\%', '\_', '%', '_'), $value);
						break;
					}
					$query->where = "$query->alias.smw_title$comp" . $this->m_dbs->addQuotes($value);
				}
			}
		} else { // (e.g. SMWThingDescription)
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
						$query->from .= ' INNER JOIN ' . $subquery->jointable . " AS $subquery->alias ON $joinfield=" . $subquery->joinfield;
					} elseif ($subquery->joinfield !== '') { // require joinfield as "value" via WHERE
						$query->where .= (($query->where == '')?'':' AND ') . "$joinfield=" . $subquery->joinfield;
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
				$this->m_dbs->query( "CREATE TEMPORARY TABLE $query->alias" .
				                     ' ( id INT UNSIGNED KEY ) TYPE=MEMORY', 'SMW::executeQueries' );
				$this->m_querylog[$query->alias] = array();
				foreach ($query->components as $qid => $joinfield) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries($subquery);
					$sql = '';
					if ($subquery->jointable != '') {
						$sql = "INSERT IGNORE INTO $query->alias SELECT $subquery->joinfield FROM $subquery->jointable AS $subquery->alias $subquery->from WHERE $subquery->where ";
					} elseif ($subquery->joinfield !== '') {
						/// NOTE: this works only for single "unconditional" values without further 
						/// WHERE or FROM. The execution must take care of not creating any others.
						$sql = "INSERT IGNORE INTO $query->alias (id) VALUES (" . $this->m_dbs->addQuotes($subquery->joinfield) . ')';
					} // else: // interpret empty joinfields as impossible condition (empty result), ignore
					if ($sql) {
						$this->m_querylog[$query->alias][] = $sql;
						$this->m_dbs->query($sql , 'SMW::executeQueries');
					}
				}
				$query->jointable = $query->alias;
				$query->joinfield = "$query->alias.id";
				$query->sortfields = array(); // make sure we got no sortfields
				/// TODO: currently this eliminates sortkeys, possibly keep them (needs different temp table format though, maybe not such a good thing to do)
			break;
			case SMW_SQL2_VALUE: break; // nothing to do
		}
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
					$qobj->sortfields[$propkey] = "$qobj->alias.smw_title";
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

}