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
	 * TODO: we now have sorting even for subquery conditions. Does this work? Is it slow/problematic?
	 * NOTE: we do not support category wildcards, as they have no useful semantics in OWL/RDFS/LP/whatever
	 */
	public function getQueryResult(SMWQuery $query) {
		global $smwgQSortingSupport;
		$prs = $query->getDescription()->getPrintrequests(); // ignore print requests at deeper levels
		if ($query->querymode == SMWQuery::MODE_NONE) { // don't query, but return something to printer
			$result = new SMWQueryResult($prs, $query, false);
			return $result;
		}
		$this->m_sortkeys = $query->sortkeys;

		$qid = $this->compileQueries($query->getDescription());
		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );
		$this->applyOrderConditions($query,$qid,$sql_options);
		$qobj = new SMWSQLStore2Query();
		$qobj->components = array($qid => 'ids.smw_id');
		$this->executeQueries($qobj);

		// debug:
		$result = ' ' . str_replace('[','&#x005B;',$query->getDescription()->getQueryString())  . "\n\n";
		$result .= 'SELECT DISTINCT ids.smw_title,ids.smw_namespace FROM smw_ids AS ids' . $qobj->from . (($qobj->where=='')?'':' WHERE ') . $qobj->where . ";";
		foreach ($this->m_querylog as $table => $log) {
			$result .= "\n\n<b>Temporary table $table</b>";
			foreach ($log as $q) {
				$result .= "\n\n$q";
			}
		}

		$res = $this->m_dbs->select($this->m_dbs->tableName('smw_ids') . ' AS ids' . $qobj->from, 'DISTINCT ids.smw_title as t,ids.smw_namespace as ns', $qobj->where, 'SMW::getQueryResult', $sql_options);
		
		// debug:
		while ($row = $this->m_dbs->fetchObject($res)) {
			$result .= "\n\n$row->t  ($row->ns)";
		}
		$count=0;
// 		$result = new SMWQueryResult($prs, $query, ($count > $query->getLimit()) );

		// finally, free temporary tables
		foreach ($this->m_querylog as $table => $log) {
			$this->m_dbs->query("DROP TEMPORARY TABLE $table", 'SMW::getQueryResult');
		}

		return $result;


		/// Old code below, unused now

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
						$row[] = new SMWResultArray($this->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF), $pr);
						break;
					case SMW_PRINT_PROP:
						$row[] = new SMWResultArray($this->getPropertyValues($qt->getTitle(),$pr->getTitle(), NULL, $pr->getOutputFormat()), $pr);
						break;
					case SMW_PRINT_CCAT:
						$cats = $this->getSpecialValues($qt->getTitle(),SMW_SP_INSTANCE_OF);
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
						$sortfield = $query->alias .  (SMWDataValueFactory::newTypeIDValue($typeid)->isNumeric())?'value_num':'value_xsd';
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
			case SMW_SQL2_TABLE: case SMW_SQL2_VALUE:
				foreach ($query->components as $qid => $joinfield) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries($subquery);
					if ($subquery->jointable != '') { // join with jointable.joinfield
						$query->from .= ' INNER JOIN ' . $subquery->jointable . " AS $subquery->alias ON $joinfield=" . $subquery->joinfield;
					} elseif ($subquery->joinfield != '') { // require joinfield as "value" via WHERE
						$query->where .= (($query->where == '')?'':' AND ') . "$joinfield=" . $subquery->joinfield;
					} // else: no usable output from subquery, ignore
					if ($subquery->where != '') {
						$query->where .= (($query->where == '')?'':' AND ') . $subquery->where;
					}
					foreach ($subquery->sortfields as $propkey => $field) {
						$query->sortfields[$propkey] = $field; // all fieldnames are kept unchanged and remain available in query result
					}
					$query->from .= $subquery->from;
				}
				$query->components = array();
			break;
			case SMW_SQL2_CONJUNCTION:
				// pick one subquery as anchor point ...
				reset($query->components);
				$key = key($query->components);
				$result = $this->m_queries[$key];
				unset($query->components[$key]);
				$this->executeQueries($result); // execute it first (may change jointable and joinfield, e.g. when making temporary tables
				// ... and append to this query the remaining queries
				foreach ($query->components as $qid => $joinfield) {
					$result->components[$qid] = $result->joinfield;
				}
				$this->executeQueries($result); // second execute, now incorporating remaining conditions
				$query = $result;
			break;
			case SMW_SQL2_DISJUNCTION:
				$this->m_dbs->query( "CREATE TEMPORARY TABLE $query->alias" .
				                     ' ( id INT UNSIGNED KEY ) TYPE=MEMORY', 'SMW::executeQueries' );
				$this->m_querylog[$query->alias] = array();
				foreach ($query->components as $qid => $joinfield) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries($subquery);
					$sql = "INSERT IGNORE INTO $query->alias SELECT $subquery->joinfield FROM $subquery->jointable AS $subquery->alias $subquery->from WHERE $subquery->where ";
					$this->m_querylog[$query->alias][] = $sql;
					$this->m_dbs->query($sql , 'SMW::executeQueries');
				}
				$query->jointable = $query->alias;
				$query->joinfield = "$query->alias.id";
				/// TODO: currently this eliminates sortkeys, possibly keep them (needs different temp table format though, maybe not such a good thing to do)
			break;
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
	 * This function modifies the given query object ID $qid and array of SQL $options to
	 * account for the ordering conditions in the SMWQuery $query.
	 */
	protected function applyOrderConditions($query, &$qid, &$options) {
		global $smwgQSortingSupport;
		if ( !$smwgQSortingSupport ) {
			return;
		}
		$qobj = $this->m_queries[$qid];
// 			$extraproperties = array(); // collect required extra property descriptions
// 			foreach ($this->m_sortkeys as $key => $order) {
// 				if ($this->m_sortfields[$key] == false) { // find missing property to sort by
// 					if ($key == '') { // sort by first column (page titles)
// 						$this->m_sortfields[$key] = "$pagetable.page_title";
// 					} else { // try to extend query
// 						$extrawhere = '';
// 						$sorttitle = Title::newFromText($key, SMW_NS_PROPERTY);
// 						if ($sorttitle !== NULL) { // careful, Title creation might still fail!
// 							$extraproperties[] = new SMWSomeProperty($sorttitle, new SMWThingDescription());
// 						}
// 					}
// 				}
// 			}
// 			if (count($extraproperties) > 0) {
// 				if (count($extraproperties) == 1) {
// 					$desc = end($extraproperties);
// 				} else {
// 					$desc = new SMWConjunction($extraproperties);
// 				}
// 				$this->createSQLQuery($desc, $from, $extrawhere, $db, $curtables);
// 				if ($extrawhere != '') {
// 					if ($where != '') {
// 						$where = "($where) AND ";
// 					}
// 					$where .= "($extrawhere)";
// 				}
// 			}
			foreach ($this->m_sortkeys as $propkey => $order) {
				if (array_key_exists($propkey,$qobj->sortfields)) { // field successfully added
					if (!array_key_exists('ORDER BY', $options)) {
						$options['ORDER BY'] = '';
					} else {
						$options['ORDER BY'] .= ', ';
					}
					$options['ORDER BY'] .= $qobj->sortfields[$propkey] . " $order ";
				}
			}
	}

}