<?php
/**
 * Query answering functions for SMWSQLStore2. Separated from main code for readability and
 * for avoiding twice the amount of code being required on every use of a simple storage function.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 *
 * @file
 * @ingroup SMWStore
 */

// Types for query descriptions (comments refer to SMWSQLStore2Query class below):
define( 'SMW_SQL2_NOQUERY', 0 ); // empty query without usable condition, dropped as soon as discovered; this is used only during preparing the query (no queries of this type should ever be added)
define( 'SMW_SQL2_TABLE', 1 ); // jointable: internal table name, joinfield, components, where use alias.fields, from uses external table names, components interpreted conjunctively (JOIN)
define( 'SMW_SQL2_VALUE', 2 ); // joinfield (disjunctive) array of unquoted values, jointable empty, components empty
define( 'SMW_SQL2_DISJUNCTION', 3 ); // joinfield, jointable empty, only components relevant
define( 'SMW_SQL2_CONJUNCTION', 4 ); // joinfield, jointable empty, only components relevant
define( 'SMW_SQL2_CLASS_HIERARCHY', 5 ); // only joinfield relevant: (disjunctive) array of unquoted values
define( 'SMW_SQL2_PROP_HIERARCHY', 6 ); // only joinfield relevant: (disjunctive) array of unquoted values

/**
 * Class for representing a single (sub)query description. Simple data
 * container.
 * @ingroup SMWStore
 */
class SMWSQLStore2Query {
	public $type = SMW_SQL2_TABLE;
	public $jointable = '';
	public $joinfield = '';
	public $from = '';
	public $where = '';
	public $components = array();

	/**
	 * The alias to be used for jointable; read-only after construct!
	 * @var string
	 */
	public $alias;

	/**
	 * property dbkey => db field; passed down during query execution.
	 * @var array
	 */
	public $sortfields = array();

	public static $qnum = 0;

	public function __construct() {
		$this->alias = 't' . self::$qnum++;
	}
}

/**
 * Class that implements query answering for SMWSQLStore2.
 * @ingroup SMWStore
 */
class SMWSQLStore2QueryEngine {

	/** Database slave to be used */
	protected $m_dbs; // TODO: should temporary tables be created on the master DB?
	/** Parent SMWSQLStore2. */
	protected $m_store;
	/** Query mode copied from given query. Some submethods act differently when in SMWQuery::MODE_DEBUG. */
	protected $m_qmode;
	/** Array of generated SMWSQLStore2Query query descriptions (index => object). */
	protected $m_queries = array();
	/** Array of arrays of executed queries, indexed by the temporary table names results were fed into. */
	protected $m_querylog = array();
	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 */
	protected $m_sortkeys;
	/** Cache of computed hierarchy queries for reuse ("catetgory/property value string" => "tablename"). */
	protected $m_hierarchies = array();
	/** Local collection of error strings, passed on to callers if possible. */
	protected $m_errors = array();

	public function __construct( &$parentstore, &$dbslave ) {
		$this->m_store = $parentstore;
		$this->m_dbs = $dbslave;
	}

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function refreshConceptCache( $concept ) {
		global $smwgQMaxLimit, $smwgQConceptFeatures, $wgDBtype;

		$cid = $this->m_store->getSMWPageID( $concept->getDBkey(), SMW_NS_CONCEPT, '' );
		$cid_c = $this->m_store->getSMWPageID( $concept->getDBkey(), SMW_NS_CONCEPT, '', false );

		if ( $cid != $cid_c ) {
			$this->m_errors[] = "Skipping redirect concept.";
			return $this->m_errors;
		}

		$dv = end( $this->m_store->getPropertyValues( $concept, SMWPropertyValue::makeProperty( '_CONC' ) ) );
		$desctxt = ( $dv !== false ) ? $dv->getWikiValue():false;
		$this->m_errors = array();

		if ( $desctxt ) { // concept found
			$this->m_qmode = SMWQuery::MODE_INSTANCES;
			$this->m_queries = array();
			$this->m_hierarchies = array();
			$this->m_querylog = array();
			$this->m_sortkeys = array();
			SMWSQLStore2Query::$qnum = 0;

			// Pre-process query:
			$qp = new SMWQueryParser( $smwgQConceptFeatures );
			$desc = $qp->getQueryDescription( $desctxt );
			$qid = $this->compileQueries( $desc );

			$this->executeQueries( $this->m_queries[$qid] ); // execute query tree, resolve all dependencies
			$qobj = $this->m_queries[$qid];

			if ( $qobj->joinfield === '' ) {
				return;
			}

			// Update database:
			$this->m_dbs->delete( 'smw_conccache', array( 'o_id' => $cid ), 'SMW::refreshConceptCache' );

			if ( $wgDBtype == 'postgres' ) { // PostgresQL: no INSERT IGNORE, check for duplicates explicitly
				$where = $qobj->where . ( $qobj->where ? ' AND ':'' ) .
				         'NOT EXISTS (SELECT NULL FROM ' . $this->m_dbs->tableName( 'smw_conccache' ) .
			             ' WHERE ' . $this->m_dbs->tablename( 'smw_conccache' ) . '.s_id = ' . $qobj->alias . '.s_id ' .
			             ' AND   ' . $this->m_dbs->tablename( 'smw_conccache' ) . '.o_id = ' . $qobj->alias . '.o_id )';
			} else { // MySQL just uses INSERT IGNORE, no extra conditions
				$where = $qobj->where;
			}

			$this->m_dbs->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? "":"IGNORE " ) . "INTO " . $this->m_dbs->tableName( 'smw_conccache' ) .
			                    " SELECT DISTINCT $qobj->joinfield AS s_id, $cid AS o_id FROM " .
			                    $this->m_dbs->tableName( $qobj->jointable ) . " AS $qobj->alias" . $qobj->from .
			                    ( $where ? " WHERE ":'' ) . $where . " LIMIT $smwgQMaxLimit",
			                    'SMW::refreshConceptCache' );

			$this->m_dbs->update( 'smw_conc2', array( 'cache_date' => strtotime( "now" ), 'cache_count' => $this->m_dbs->affectedRows() ), array( 's_id' => $cid ), 'SMW::refreshConceptCache' );
		} else { // just delete old data if there is any
			$this->m_dbs->delete( 'smw_conccache', array( 'o_id' => $cid ), 'SMW::refreshConceptCache' );
			$this->m_dbs->update( 'smw_conc2', array( 'cache_date' => null, 'cache_count' => null ), array( 's_id' => $cid ), 'SMW::refreshConceptCache' );
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
	public function deleteConceptCache( $concept ) {
		$cid = $this->m_store->getSMWPageID( $concept->getDBkey(), SMW_NS_CONCEPT, '', false );
		$this->m_dbs->delete( 'smw_conccache', array( 'o_id' => $cid ), 'SMW::refreshConceptCache' );
		$this->m_dbs->update( 'smw_conc2', array( 'cache_date' => null, 'cache_count' => null ), array( 's_id' => $cid ), 'SMW::refreshConceptCache' );
	}

	/**
	 * The new SQL store's implementation of query answering. This function
	 * works in two stages: First, the nested conditions of the given query
	 * object are preprocessed to compute an abstract representation of the
	 * SQL query that is to be executed. Since query conditions correspond to
	 * joins with property tables in most cases, this abstract representation
	 * is essentially graph-like description of how property tables are joined.
	 * Moreover, this graph is tree-shaped, since all query conditions are
	 * tree-shaped. Each part of this abstract query structure is represented
	 * by an SMWSQLStore2Query object in the array m_queries.
	 *
	 * As a second stage of processing, the thus prepared SQL query is actually
	 * executed. Typically, this means that the joins are collapsed into one
	 * SQL query to retrieve results. In some cases, such as in dbug mode, the
	 * execution might be restricted and not actually perform the whole query.
	 *
	 * The two-stage process helps to separate tasks, and it also allows for
	 * better optimisations: it is left to the execution engine how exactly the
	 * query result is to be obtained. For example, one could pre-compute
	 * partial suib-results in temporary tables (or even cache them somewhere),
	 * instead of passing one large join query to the DB (of course, it might
	 * be large only if the configuration of SMW allows it). For some DBMS, a
	 * step-wise execution of the query might lead to better performance, since
	 * it exploits the tree-structure of the joins, which is important for fast
	 * processing -- not all DBMS might be able in seeing this by themselves.
	 *
	 * @param SMWQuery $query
	 */
	public function getQueryResult( SMWQuery $query ) {
		global $smwgIgnoreQueryErrors, $smwgQSortingSupport;

		if ( !$smwgIgnoreQueryErrors && ( $query->querymode != SMWQuery::MODE_DEBUG ) && ( count( $query->getErrors() ) > 0 ) ) {
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->m_store, false );
			// NOTE: we check this here to prevent unnecessary work, but we check it after query processing below again in case more errors occurred
		} elseif ( $query->querymode == SMWQuery::MODE_NONE ) { // don't query, but return something to printer
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->m_store, true );
		}

		$this->m_qmode = $query->querymode;
		$this->m_queries = array();
		$this->m_hierarchies = array();
		$this->m_querylog = array();
		$this->m_errors = array();
		SMWSQLStore2Query::$qnum = 0;
		$this->m_sortkeys = $query->sortkeys;

		// *** First compute abstract representation of the query (compilation) ***//
		wfProfileIn( 'SMWSQLStore2Queries::compileMainQuery (SMW)' );
		$qid = $this->compileQueries( $query->getDescription() ); // compile query, build query "plan"
		wfProfileOut( 'SMWSQLStore2Queries::compileMainQuery (SMW)' );

		if ( $qid < 0 ) { // no valid/supported condition; ensure that at least only proper pages are delivered
			$qid = SMWSQLStore2Query::$qnum;
			$q = new SMWSQLStore2Query();
			$q->jointable = 'smw_ids';
			$q->joinfield = "$q->alias.smw_id";
			$q->where = "$q->alias.smw_iw!=" . $this->m_dbs->addQuotes( SMW_SQL2_SMWIW ) . " AND $q->alias.smw_iw!=" . $this->m_dbs->addQuotes( SMW_SQL2_SMWREDIIW ) . " AND $q->alias.smw_iw!=" . $this->m_dbs->addQuotes( SMW_SQL2_SMWBORDERIW ) . " AND $q->alias.smw_iw!=" . $this->m_dbs->addQuotes( SMW_SQL2_SMWINTDEFIW );
			$this->m_queries[$qid] = $q;
		}

		if ( $this->m_queries[$qid]->jointable != 'smw_ids' ) {
			// manually make final root query (to retrieve namespace,title):
			$rootid = SMWSQLStore2Query::$qnum;
			$qobj = new SMWSQLStore2Query();
			$qobj->jointable  = 'smw_ids';
			$qobj->joinfield  = "$qobj->alias.smw_id";
			$qobj->components = array( $qid => "$qobj->alias.smw_id" );
			$qobj->sortfields = $this->m_queries[$qid]->sortfields;
			$this->m_queries[$rootid] = $qobj;
		} else { // not such a common case, but worth avoiding the additional inner join:
			$rootid = $qid;
		}

		// Include order conditions (may extend query if needed for sorting):
		if ( $smwgQSortingSupport ) {
			$this->applyOrderConditions( $rootid );
		}

		if ( !$smwgIgnoreQueryErrors && ( $query->querymode != SMWQuery::MODE_DEBUG ) && ( count( $this->m_errors ) > 0 ) ) { // stop here if new errors happened
			$query->addErrors( $this->m_errors );
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->m_store, false );
		}

		// *** Now execute the computed query ***//
		wfProfileIn( 'SMWSQLStore2Queries::executeMainQuery (SMW)' );
		$this->executeQueries( $this->m_queries[$rootid] ); // execute query tree, resolve all dependencies
		wfProfileOut( 'SMWSQLStore2Queries::executeMainQuery (SMW)' );

		switch ( $query->querymode ) {
			case SMWQuery::MODE_DEBUG:
				$result = $this->getDebugQueryResult( $query, $rootid );
			break;
			case SMWQuery::MODE_COUNT:
				$result = $this->getCountQueryResult( $query, $rootid );
			break;
			default:
				$result = $this->getInstanceQueryResult( $query, $rootid );
			break;
		}

		$this->cleanUp();
		$query->addErrors( $this->m_errors );

		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper debug output for the given query.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootid
	 */
	protected function getDebugQueryResult( SMWQuery $query, $rootid ) {
		$qobj = $this->m_queries[$rootid];
		$sql_options = $this->getSQLOptions( $query, $rootid );

		list( $startOpts, $useIndex, $tailOpts ) = $this->m_dbs->makeSelectOptions( $sql_options );

		$result = '<div style="border: 1px dotted black; background: #A1FB00; padding: 20px; ">' .
		          '<b>Debug output by SMWSQLStore2</b><br />' .
		          'Generated Wiki-Query<br /><tt>' .
		          str_replace( '[', '&#x005B;', $query->getDescription()->getQueryString() ) . '</tt><br />' .
		          'Query-Size: ' . $query->getDescription()->getSize() . '<br />' .
		          'Query-Depth: ' . $query->getDescription()->getDepth() . '<br />';

		if ( $qobj->joinfield !== '' ) {
			$result .= 'SQL query<br />' .
			           "<tt>SELECT DISTINCT $qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns FROM " .
			           $this->m_dbs->tableName( $qobj->jointable ) . " AS $qobj->alias" . $qobj->from .
			           ( ( $qobj->where == '' ) ? '':' WHERE ' ) . $qobj->where . "$tailOpts LIMIT " .
			           $sql_options['LIMIT'] . ' OFFSET ' . $sql_options['OFFSET'] . ';</tt>';
		} else {
			$result .= '<b>Empty result, no SQL query created.</b>';
		}

		$errors = '';

		foreach ( $query->getErrors() as $error ) {
			$errors .= $error . '<br />';
		}

		$result .= ( $errors ) ? "<br />Errors and warnings:<br />$errors":'<br />No errors or warnings.';
		$auxtables = '';

		foreach ( $this->m_querylog as $table => $log ) {
			$auxtables .= "<li>Temporary table $table";

			foreach ( $log as $q ) {
				$auxtables .= "<br />&#160;&#160;<tt>$q</tt>";
			}

			$auxtables .= '</li>';
		}

		$result .= ( $auxtables ) ? "<br />Auxilliary tables used:<ul>$auxtables</ul>":'<br />No auxilliary tables used.';
		$result .= '</div>';

		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper counting output for the given query.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootid
	 */
	protected function getCountQueryResult( SMWQuery $query, $rootid ) {
		wfProfileIn( 'SMWSQLStore2Queries::getCountQueryResult (SMW)' );

		$qobj = $this->m_queries[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			wfProfileOut( 'SMWSQLStore2Queries::getCountQueryResult (SMW)' );
			return 0;
		}

		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );
		$res = $this->m_dbs->select( $this->m_dbs->tableName( $qobj->jointable ) . " AS $qobj->alias" . $qobj->from, "COUNT(DISTINCT $qobj->alias.smw_id) AS count", $qobj->where, 'SMW::getQueryResult', $sql_options );
		$row = $this->m_dbs->fetchObject( $res );
		$count = $row->count;
		$this->m_dbs->freeResult( $res );

		wfProfileOut( 'SMWSQLStore2Queries::getCountQueryResult (SMW)' );

		return $count;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid,
	 * compute the proper result instance output for the given query.
	 * @todo The SQL standard requires us to select all fields by which we sort, leading
	 * to wrong results regarding the given limit: the user expects limit to be applied to
	 * the number of distinct pages, but we can use DISTINCT only to whole rows. Thus, if
	 * rows contain sortfields, then pages with multiple values for that field are distinct
	 * and appear multiple times in the result. Filtering duplicates in post processing
	 * would still allow such duplicates to push aside wanted values, leading to less than
	 * "limit" results although there would have been "limit" really distinct results. For
	 * this reason, we select sortfields only for POSTGRES. MySQL is able to perform what
	 * we want here. It would be nice if we could eliminate the bug in POSTGRES as well.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootid
	 *
	 * @return SMWQueryResult
	 */
	protected function getInstanceQueryResult( SMWQuery $query, $rootid ) {
		global $wgDBtype;

		wfProfileIn( 'SMWSQLStore2Queries::getInstanceQueryResult (SMW)' );
		$qobj = $this->m_queries[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			$result = new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->m_store, false );
			wfProfileOut( 'SMWSQLStore2Queries::getInstanceQueryResult (SMW)' );
			return $result;
		}

		$sql_options = $this->getSQLOptions( $query, $rootid );

		// Selecting those is required in standard SQL (but MySQL does not require it).
		$sortfields = implode( $qobj->sortfields, ',' );

		$res = $this->m_dbs->select( $this->m_dbs->tableName( $qobj->jointable ) . " AS $qobj->alias" . $qobj->from,
			"DISTINCT $qobj->alias.smw_id AS id,$qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns,$qobj->alias.smw_iw AS iw,$qobj->alias.smw_sortkey AS sortkey" .
			  ( $wgDBtype == 'postgres' ? ( ( $sortfields ? ',' : '' ) . $sortfields ) : '' ),
			$qobj->where, 'SMW::getQueryResult', $sql_options );

		$qr = array();
		$count = 0;
		$prs = $query->getDescription()->getPrintrequests();

		while ( ( $count < $query->getLimit() ) && ( $row = $this->m_dbs->fetchObject( $res ) ) ) {
			$count++;
			$v = SMWWikiPageValue::makePage( $row->t, $row->ns, $row->sortkey );
			$qr[] = $v;
			$this->m_store->cacheSMWPageID( $row->id, $row->t, $row->ns, $row->iw );
		}

		if ( $this->m_dbs->fetchObject( $res ) ) {
			$count++;
		}

		$this->m_dbs->freeResult( $res );
		$result = new SMWQueryResult( $prs, $query, $qr, $this->m_store, ( $count > $query->getLimit() ) );

		wfProfileOut( 'SMWSQLStore2Queries::getInstanceQueryResult (SMW)' );

		return $result;
	}

	/**
	 * Create a new SMWSQLStore2Query object that can be used to obtain results
	 * for the given description. The result is stored in $this->m_queries
	 * using a numeric key that is returned as a result of the function.
	 * Returns -1 if no query was created.
	 * @todo The case of nominal classes (top-level SMWValueDescription) still
	 * makes some assumptions about the table structure, especially about the
	 * name of the joinfield (o_id). Better extend compileAttributeWhere to
	 * deal with this case.
	 *
	 * @param SMWDescription $description
	 *
	 * @return integer
	 */
	protected function compileQueries( SMWDescription $description ) {
		$qid = SMWSQLStore2Query::$qnum;
		$query = new SMWSQLStore2Query();

		if ( $description instanceof SMWSomeProperty ) {
			$this->compilePropertyCondition( $query, $description->getProperty(), $description->getDescription() );
			// Compilation has set type to NOQUERY: drop condition.
			if ( $query->type == SMW_SQL2_NOQUERY ) $qid = - 1;
		} elseif ( $description instanceof SMWNamespaceDescription ) {
			// TODO: One instance of smw_ids on s_id always suffices (swm_id is KEY)! Doable in execution ... (PERFORMANCE)
			$query->jointable = 'smw_ids';
			$query->joinfield = "$query->alias.smw_id";
			$query->where = "$query->alias.smw_namespace=" . $this->m_dbs->addQuotes( $description->getNamespace() );
		} elseif ( ( $description instanceof SMWConjunction ) || ( $description instanceof SMWDisjunction ) ) {
			$query->type = ( $description instanceof SMWConjunction ) ? SMW_SQL2_CONJUNCTION:SMW_SQL2_DISJUNCTION;

			foreach ( $description->getDescriptions() as $subdesc ) {
				$sub = $this->compileQueries( $subdesc );
				if ( $sub >= 0 ) {
					$query->components[$sub] = true;
				}
			}

			// All subconditions failed, drop this as well.
			if ( count( $query->components ) == 0 ) $qid = - 1;
		} elseif ( $description instanceof SMWClassDescription ) {
			$cqid = SMWSQLStore2Query::$qnum;
			$cquery = new SMWSQLStore2Query();
			$cquery->type = SMW_SQL2_CLASS_HIERARCHY;
			$cquery->joinfield = array();

			foreach ( $description->getCategories() as $cat ) {
				$cid = $this->m_store->getSMWPageID( $cat->getDBkey(), NS_CATEGORY, $cat->getInterwiki() );
				if ( $cid != 0 ) {
					$cquery->joinfield[] = $cid;
				}
			}

			if ( count( $cquery->joinfield ) == 0 ) { // Empty result.
				$query->type = SMW_SQL2_VALUE;
				$query->jointable = '';
				$query->joinfield = '';
			} else { // Instance query with dicjunction of classes (categories)
				$query->jointable = 'smw_inst2';
				$query->joinfield = "$query->alias.s_id";
				$query->components[$cqid] = "$query->alias.o_id";
				$this->m_queries[$cqid] = $cquery;
			}
		} elseif ( $description instanceof SMWValueDescription ) { // Only type '_wpg' objects can appear on query level (essentially as nominal classes).
			if ( $description->getDatavalue()->getTypeID() == '_wpg' ) {
				if ( $description->getComparator() == SMW_CMP_EQ ) {
					$query->type = SMW_SQL2_VALUE;
					$oid = $this->m_store->getSMWPageID( $description->getDatavalue()->getDBkey(), $description->getDatavalue()->getNamespace(), $description->getDatavalue()->getInterwiki() );
					$query->joinfield = array( $oid );
				} else { // Join with smw_ids needed for other comparators (apply to title string).
					$query->jointable = 'smw_ids';
					$query->joinfield = "$query->alias.smw_id";
					$value = $description->getDatavalue()->getSortkey();

					switch ( $description->getComparator() ) {
						case SMW_CMP_LEQ: $comp = '<='; break;
						case SMW_CMP_GEQ: $comp = '>='; break;
						case SMW_CMP_NEQ: $comp = '!='; break;
						case SMW_CMP_LIKE: case SMW_CMP_NLKE:
							$comp = ' LIKE ';
							if ( $description->getComparator() == SMW_CMP_NLKE ) $comp = " NOT{$comp}";
							$value =  str_replace( array( '%', '_', '*', '?' ), array( '\%', '\_', '%', '_' ), $value );
						break;
					}

					$query->where = "$query->alias.smw_sortkey$comp" . $this->m_dbs->addQuotes( $value );
				}
			}
		} elseif ( $description instanceof SMWConceptDescription ) { // fetch concept definition and insert it here
			$cid = $this->m_store->getSMWPageID( $description->getConcept()->getDBkey(), SMW_NS_CONCEPT, '' );
			$row = $this->m_dbs->selectRow(
				'smw_conc2',
				array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date' ),
				array( 's_id' => $cid ),
				'SMWSQLStore2Queries::compileQueries'
			);

			if ( $row === false ) { // No description found, concept does not exist.
				// keep the above query object, it yields an empty result
				// TODO: announce an error here? (maybe not, since the query processor can check for
				// non-existing concept pages which is probably the main reason for finding nothing here)
			} else {
				global $smwgQConceptCaching, $smwgQMaxSize, $smwgQMaxDepth, $smwgQFeatures, $smwgQConceptCacheLifetime;

				$may_be_computed = ( $smwgQConceptCaching == CONCEPT_CACHE_NONE ) ||
				    ( ( $smwgQConceptCaching == CONCEPT_CACHE_HARD ) && ( ( ~( ~( $row->concept_features + 0 ) | $smwgQFeatures ) ) == 0 ) &&
				      ( $smwgQMaxSize >= $row->concept_size ) && ( $smwgQMaxDepth >= $row->concept_depth ) );

				if ( $row->cache_date &&
				     ( ( $row->cache_date > ( strtotime( "now" ) - $smwgQConceptCacheLifetime * 60 ) ) ||
				       !$may_be_computed ) ) { // Cached concept, use cache unless it is dead and can be revived.

					$query->jointable = 'smw_conccache';
					$query->joinfield = "$query->alias.s_id";
					$query->where = "$query->alias.o_id=" . $this->m_dbs->addQuotes( $cid );
				} elseif ( $row->concept_txt ) { // Parse description and process it recursively.
					if ( $may_be_computed ) {
						$qp = new SMWQueryParser();

						//No defaultnamespaces here; If any, these are already in the concept.
						$desc = $qp->getQueryDescription( $row->concept_txt );
						$qid = $this->compileQueries( $desc );
						if ($qid != -1) {
							$query = $this->m_queries[$qid];
						} else { // somehow the concept query is no longer valid; maybe some syntax changed (upgrade) or global settings were modified since storing it
							smwfLoadExtensionMessages( 'SemanticMediaWiki' );
							$this->m_errors[] = wfMsg( 'smw_emptysubquery' ); // not quite the right message, but this case is very rare; let us not make detailed messages for this
						}
					} else {
						smwfLoadExtensionMessages( 'SemanticMediaWiki' );
						$this->m_errors[] = wfMsg( 'smw_concept_cache_miss', $description->getConcept()->getText() );
					}
				} // else: no cache, no description (this may happen); treat like empty concept
			}
		} else { // (e.g. SMWThingDescription)
			$qid = - 1; // no condition
		}

		if ( $qid >= 0 ) { // Success, keep query object, propagate sortkeys from subqueries.
			$this->m_queries[$qid] = $query;

			if ( $query->type != SMW_SQL2_DISJUNCTION ) { // Sortkeys are killed by disjunctions (not all parts may have them),
				// NOTE: preprocessing might try to push disjunctions downwards to safe sortkey, but this seems to be minor
				foreach ( $query->components as $cid => $field ) {
					$query->sortfields = array_merge( $this->m_queries[$cid]->sortfields, $query->sortfields );
				}
			}
		}

		return $qid;
	}

	/**
	 * Modify the given query object to account for some property condition for
	 * the given property. If it is not possible to generate a query for the
	 * given data, the query type is changed to SMW_SQL2_NOQUERY. Callers need
	 * to check for this and discard the query in this case.
	 * @todo Check if hierarchy queries work as expected.
	 */
	protected function compilePropertyCondition( SMWSQLStore2Query $query, $property, SMWDescription $valuedesc ) {
		$tableid = SMWSQLStore2::findPropertyTableID( $property );

		if ( $tableid == '' ) { // probably a type-polymorphic property
			$typeid = $valuedesc->getTypeID();
			$tableid = SMWSQLStore2::findTypeTableID( $typeid );
		} else { // normal property
			$typeid = $property->getPropertyTypeID();
		}

		if ( $tableid == '' ) { // Still no table to query? Give up.
			$query->type = SMW_SQL2_NOQUERY;
			return;
		}

		$proptables = SMWSQLStore2::getPropertyTables();
		$proptable = $proptables[$tableid];

		if ( !$proptable->idsubject ) { // no queries with such tables (there is really no demand, as only redirects are affected)
			$query->type = SMW_SQL2_NOQUERY;
			return;
		}

		list( $sig, $valueindex, $labelindex ) = SMWSQLStore2::getTypeSignature( $typeid );
		$sortkey = $property->getDBkey(); // TODO: strictly speaking, the DB key is not what we want here, since sortkey is based on a "wiki value"

		// *** Basic settings: table, joinfield, and objectfields ***//
		$query->jointable = $proptable->name;

		if ( $property->isInverse() ) { // see if we can support inverses by inverting the proptable data
			if ( ( count( $proptable->objectfields ) == 1 ) && ( reset( $proptable->objectfields ) == 'p' ) ) {
				$query->joinfield = $query->alias . '.' . reset( array_keys( $proptable->objectfields ) );
				$objectfields = array( 's_id' => 'p' );
				$valueindex = $labelindex = 3; // should normally not change, but let's be strict
			} else { // no inverses supported for this property, stop here
				$query->type = SMW_SQL2_NOQUERY;
				return;
			}
		} else { // normal forward property
			$query->joinfield = "{$query->alias}.s_id";
			$objectfields = $proptable->objectfields;
		}

		// *** Add conditions for selecting rows for this property, maybe with a hierarchy ***//
		if ( $proptable->fixedproperty == false ) {
			$pid = $this->m_store->getSMWPropertyID( $property );

			if ( !$property->getPropertyID() || ( $property->getPropertyTypeID() != '__err' ) ) {
				// also make property hierarchy (may or may not be executed later on)
				// exclude type-polymorphic properties _1, _2, ... (2nd check above suffices, but 1st is faster to check)
				// we could also exclude other cases here, if desired
				$pqid = SMWSQLStore2Query::$qnum;
				$pquery = new SMWSQLStore2Query();
				$pquery->type = SMW_SQL2_PROP_HIERARCHY;
				$pquery->joinfield = array( $pid );
				$query->components[$pqid] = "{$query->alias}.p_id";
				$this->m_queries[$pqid] = $pquery;
			} else {
				$query->where = "{$query->alias}.p_id=" . $this->m_dbs->addQuotes( $pid );
			}
		} // else: no property column, no hierarchy queries

		// *** Add conditions on the value of the property ***//
		if ( ( count( $objectfields ) == 1 ) && ( reset( $objectfields ) == 'p' ) ) { // page description, process like main query
			$sub = $this->compileQueries( $valuedesc );
			$objectfield = reset( array_keys( $objectfields ) );

			if ( $sub >= 0 ) {
				$query->components[$sub] = "{$query->alias}.{$objectfield}";
			}
		} else { // non-page value description; expressive features mainly based on value
			$this->compileAttributeWhere( $query, $valuedesc, $proptable, $valueindex );
			// (no need to pass on $objectfields since they are just as in $proptable in this case)
		}

		// *** Incorporate ordering if desired ***//
		if ( ( $valueindex >= 0 ) && array_key_exists( $sortkey, $this->m_sortkeys ) ) {
			// This code might be overly general: it supports datatypes of arbitrary signatures
			// and valueindex (sortkeys). It can even order pages by something other than their
			// sortkey (e.g. by their namespace?!), and it can handle values consisting of a page
			// and some more data fields before or after. Supporting pages in this way requires us
			// to iterate over the table fields since one page corresponds to four values in a
			// type's signature. Thankfully, signatures are short so this iteration is not notable.
			$smwidjoinfield = false;
			$fieldName = $this->getDBFieldsForDVIndex( $objectfields, $valueindex, $smwidjoinfield );

			if ( $fieldName ) {
				if ( $smwidjoinfield ) {
					// TODO: is this smw_ids possibly duplicated in the query? Can we prevent that? (PERFORMANCE)
					$query->from = ' INNER JOIN ' . $this->m_dbs->tableName( 'smw_ids' ) .
									" AS ids{$query->alias} ON ids{$query->alias}.smw_id={$query->alias}.{$smwidjoinfield}";
					$query->sortfields[$sortkey] = "ids{$query->alias}.{$fieldName}";
				} else {
					$query->sortfields[$sortkey] = "{$query->alias}.{$fieldName}";
				}
			}
		}
	}

	/**
	 * Helper function for matching an index that refers to the DB keys (and
	 * thus signature) of a datatype to the database fields of a fitting
	 * property table (the objectfields array of which is given).
	 * The $fieldname is set call-by-ref, where the parameter $smwidjoinfield
	 * is set to the field of $objectfields on which smw_ids.smw_id needs to
	 * be joined if $smwidjoinfield refers to a field in smw_ids. This might
	 * be needed for page-type values. If the value is not in smw_ids, then
	 * $fieldname refers to $objectfields and $smwidjoinfield is false. If the
	 * given index could not be matched, $fieldname is false.
	 *
	 * @param array $objectFields
	 * @param integer $index
	 * @param $smwidjoinfield
	 *
	 * @return array with at least one element or false
	 */
	protected function getDBFieldsForDVIndex( array $objectFields, $index, &$smwidjoinfield ) {
		$fieldName = false;

		$curindex = 0;
		foreach( $objectFields as $fname => $ftype ) {
			if ( $ftype == 'p' ) { // special treatment since "p" consists of 4 fields that are kept in smw_ids
				if ( $curindex + 4 >= $index ) {
					$idfieldnames = array( 'smw_title', 'smw_namespace', 'smw_iw', 'smw_sortkey' );
					$smwidjoinfield = $fname;
					$fieldName = $idfieldnames[$index - $curindex];
					break;
				}
				$curindex += 3;
			} elseif ( $curindex == $index ) {
				$smwidjoinfield = false;
				$fieldName = $fname;
				break;
			}
			$curindex++;
		}

		return $fieldName;
	}

	/**
	 * Given an SMWDescription that is just a conjunction or disjunction of
	 * SMWValueDescription objects, create and return a plain WHERE condition
	 * string for it.
	 *
	 * @param $query
	 * @param SMWDescription $description
	 * @param SMWSQLStore2Table $proptable
	 * @param integer $valueIndex
	 * @param string $operator
	 */
	protected function compileAttributeWhere(
			$query, SMWDescription $description, SMWSQLStore2Table $proptable, $valueIndex, $operator = 'AND' ) {

		$where = '';

		if ( $description instanceof SMWValueDescription ) {
			$dv = $description->getDatavalue();
			$keys = $dv->getDBkeys();

			// Try comparison based on value field and comparator.
			if ( $valueIndex >= 0 ) {
				// Find field name for comparison.
				$smwidjoinfield = false;
				$fieldName = $this->getDBFieldsForDVIndex( $proptable->objectfields, $valueIndex, $smwidjoinfield );

				// Do not support smw_id joined data for now.
				if ( $fieldName && !$smwidjoinfield ) {
					$comparator = false;
					$customSQL = false;

					// See if the getSQLCondition method exists and call it if this is the case.
					if ( method_exists( $description, 'getSQLCondition' ) ) {
						$customSQL = $description->getSQLCondition( $query->alias, array_keys( $proptable->objectfields ), $this->m_dbs );
					}

					if ( $customSQL ) {
						$where = $customSQL;
					}
					else {
						switch ( $description->getComparator() ) {
							case SMW_CMP_EQ: $comparator = '='; break;
							case SMW_CMP_LEQ: $comparator = '<='; break;
							case SMW_CMP_GEQ: $comparator = '>='; break;
							case SMW_CMP_NEQ: $comparator = '!='; break;
						}

						if ( $comparator ) {
							$where = "$query->alias.{$fieldName}{$comparator}" . $this->m_dbs->addQuotes( $keys[$valueIndex] );
						}
					}
				}
			}

			if ( $where == '' ) { // comparators did not apply; match all fields
				$i = 0;

				foreach ( $proptable->objectfields as $fname => $ftype ) {
					if ( $i >= count( $keys ) ) break;

					if ( $ftype == 'p' ) { // Special case: page id, resolve this in advance
						$oid = $this->getSMWPageID( $keys[$i], $keys[$i + 1], $keys[$i + 2] );
						$i += 3; // skip these additional values (sortkey not needed here)
						$where .= ( $where ? ' AND ' : '' ) . "{$query->alias}.$fname=" . $this->m_dbs->addQuotes( $oid );
					} elseif ( $ftype != 'l' ) { // plain value, but not a text blob
						$where .= ( $where ? ' AND ' : '' ) . "{$query->alias}.$fname=" . $this->m_dbs->addQuotes( $keys[$i] );
					}

					$i++;
				}
			}

		} elseif ( ( $description instanceof SMWConjunction ) || ( $description instanceof SMWDisjunction ) ) {
			$op = ( $description instanceof SMWConjunction ) ? 'AND' : 'OR';

			foreach ( $description->getDescriptions() as $subdesc ) {
				$this->compileAttributeWhere( $query, $subdesc, $proptable, $valueIndex, $op );
			}
		}

		if ( $where != '' ) $query->where .= ( $query->where ? " $operator " : '' ) . "($where)";
	}

	/**
	 * Process stored queries and change store accordingly. The query obj is modified
	 * so that it contains non-recursive description of a select to execute for getting
	 * the actual result.
	 *
	 * @param SMWSQLStore2Query $query
	 */
	protected function executeQueries( SMWSQLStore2Query &$query ) {
		global $wgDBtype;

		switch ( $query->type ) {
			case SMW_SQL2_TABLE: // Normal query with conjunctive subcondition.
				foreach ( $query->components as $qid => $joinfield ) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries( $subquery );

					if ( $subquery->jointable != '' ) { // Join with jointable.joinfield
						$query->from .= ' INNER JOIN ' . $this->m_dbs->tableName( $subquery->jointable ) . " AS $subquery->alias ON $joinfield=" . $subquery->joinfield;
					} elseif ( $subquery->joinfield !== '' ) { // Require joinfield as "value" via WHERE.
						$condition = '';

						foreach ( $subquery->joinfield as $value ) {
							$condition .= ( $condition ? ' OR ':'' ) . "$joinfield=" . $this->m_dbs->addQuotes( $value );
						}

						if ( count( $subquery->joinfield ) > 1 ) {
							$condition = "($condition)";
						}

						$query->where .= ( ( $query->where == '' ) ? '':' AND ' ) . $condition;
					} else { // interpret empty joinfields as impossible condition (empty result)
						$query->joinfield = ''; // make whole query false
						$query->jointable = '';
						$query->where = '';
						$query->from = '';
						break;
					}

					if ( $subquery->where != '' ) {
						$query->where .= ( ( $query->where == '' ) ? '':' AND ' ) . '(' . $subquery->where . ')';
					}

					$query->from .= $subquery->from;
				}

				$query->components = array();
			break;
			case SMW_SQL2_CONJUNCTION:
				// pick one subquery with jointable as anchor point ...
				reset( $query->components );
				$key = false;

				foreach ( $query->components as $qkey => $qid ) {
					if ( $this->m_queries[$qkey]->jointable != '' ) {
						$key = $qkey;
						break;
					}
				}

				if ( $key !== false ) {
					$result = $this->m_queries[$key];
					unset( $query->components[$key] );

					// Execute it first (may change jointable and joinfield, e.g. when making temporary tables)
					$this->executeQueries( $result );

					// ... and append to this query the remaining queries.
					foreach ( $query->components as $qid => $joinfield ) {
						$result->components[$qid] = $result->joinfield;
					}

					// Second execute, now incorporating remaining conditions.
					$this->executeQueries( $result );
				} else { // Only fixed values in conjunction, make a new value without joining.
					$key = $qkey;
					$result = $this->m_queries[$key];
					unset( $query->components[$key] );

					foreach ( $query->components as $qid => $joinfield ) {
						if ( $result->joinfield != $this->m_queries[$qid]->joinfield ) {
							$result->joinfield = ''; // all other values should already be ''
							break;
						}
					}
				}
				$query = $result;
			break;
			case SMW_SQL2_DISJUNCTION:
				if ( $this->m_qmode !== SMWQuery::MODE_DEBUG ) {
					$this->m_dbs->query( $this->getCreateTempIDTableSQL( $this->m_dbs->tableName( $query->alias ) ), 'SMW::executeQueries' );
				}

				$this->m_querylog[$query->alias] = array();

				foreach ( $query->components as $qid => $joinfield ) {
					$subquery = $this->m_queries[$qid];
					$this->executeQueries( $subquery );
					$sql = '';

					if ( $subquery->jointable != '' ) {
						$sql = 'INSERT ' . ( ( $wgDBtype == 'postgres' ) ? '':'IGNORE ' ) . 'INTO ' .
						       $this->m_dbs->tableName( $query->alias ) .
							   " SELECT $subquery->joinfield FROM " . $this->m_dbs->tableName( $subquery->jointable ) .
							   " AS $subquery->alias $subquery->from" . ( $subquery->where ? " WHERE $subquery->where":'' );
					} elseif ( $subquery->joinfield !== '' ) {
						// NOTE: this works only for single "unconditional" values without further
						// WHERE or FROM. The execution must take care of not creating any others.
						$values = '';

						foreach ( $subquery->joinfield as $value ) {
							$values .= ( $values ? ',' : '' ) . '(' . $this->m_dbs->addQuotes( $value ) . ')';
						}

						$sql = 'INSERT ' . ( ( $wgDBtype == 'postgres' ) ? '':'IGNORE ' ) .  'INTO ' . $this->m_dbs->tableName( $query->alias ) . " (id) VALUES $values";
					} // else: // interpret empty joinfields as impossible condition (empty result), ignore
					if ( $sql ) {
						$this->m_querylog[$query->alias][] = $sql;

						if ( $this->m_qmode !== SMWQuery::MODE_DEBUG ) {
							$this->m_dbs->query( $sql , 'SMW::executeQueries' );
						}
					}
				}

				$query->jointable = $query->alias;
				$query->joinfield = "$query->alias.id";
				$query->sortfields = array(); // Make sure we got no sortfields.
				// TODO: currently this eliminates sortkeys, possibly keep them (needs different temp table format though, maybe not such a good thing to do)
			break;
			case SMW_SQL2_PROP_HIERARCHY: case SMW_SQL2_CLASS_HIERARCHY: // make a saturated hierarchy
				$this->executeHierarchyQuery( $query );
			break;
			case SMW_SQL2_VALUE: break; // nothing to do
		}
	}

	/**
	 * Find subproperties or subcategories. This may require iterative computation,
	 * and temporary tables are used in many cases.
	 *
	 * @param SMWSQLStore2Query $query
	 */
	protected function executeHierarchyQuery( SMWSQLStore2Query &$query ) {
		global $wgDBtype;
		global $smwgQSubpropertyDepth, $smwgQSubcategoryDepth;

		$fname = "SMWSQLStore2Queries::executeQueries-hierarchy-$query->type (SMW)";
		wfProfileIn( $fname );

		$depth = ( $query->type == SMW_SQL2_PROP_HIERARCHY ) ? $smwgQSubpropertyDepth : $smwgQSubcategoryDepth;

		if ( $depth <= 0 ) { // treat as value, no recursion
			$query->type = SMW_SQL2_VALUE;
			wfProfileOut( $fname );
			return;
		}

		$values = '';
		$valuecond = '';

		foreach ( $query->joinfield as $value ) {
			$values .= ( $values ? ',':'' ) . '(' . $this->m_dbs->addQuotes( $value ) . ')';
			$valuecond .= ( $valuecond ? ' OR ':'' ) . 'o_id=' . $this->m_dbs->addQuotes( $value );
		}

		$smwtable = $this->m_dbs->tableName( ( $query->type == SMW_SQL2_PROP_HIERARCHY ) ? 'smw_subp2':'smw_subs2' );

		// Try to safe time (SELECT is cheaper than creating/dropping 3 temp tables):
		$res = $this->m_dbs->select( $smwtable, 's_id', $valuecond, array( 'LIMIT' => 1 ) );

		if ( !$this->m_dbs->fetchObject( $res ) ) { // no subobjects, we are done!
			$this->m_dbs->freeResult( $res );
			$query->type = SMW_SQL2_VALUE;
			wfProfileOut( $fname );
			return;
		}

		$this->m_dbs->freeResult( $res );
		$tablename = $this->m_dbs->tableName( $query->alias );
		$this->m_querylog[$query->alias] = array( "Recursively computed hierarchy for element(s) $values." );
		$query->jointable = $query->alias;
		$query->joinfield = "$query->alias.id";

		if ( $this->m_qmode == SMWQuery::MODE_DEBUG ) {
			wfProfileOut( $fname );
			return; // No real queries in debug mode.
		}

		$this->m_dbs->query( $this->getCreateTempIDTableSQL( $tablename ), 'SMW::executeHierarchyQuery' );

		if ( array_key_exists( $values, $this->m_hierarchies ) ) { // Just copy known result.
			$this->m_dbs->query( "INSERT INTO $tablename (id) SELECT id" .
								' FROM ' . $this->m_hierarchies[$values],
								'SMW::executeHierarchyQuery' );
			wfProfileOut( $fname );
			return;
		}

		// NOTE: we use two helper tables. One holds the results of each new iteration, one holds the
		// results of the previous iteration. One could of course do with only the above result table,
		// but then every iteration would use all elements of this table, while only the new ones
		// obtained in the previous step are relevant. So this is a performance measure.
		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';
		$this->m_dbs->query( $this->getCreateTempIDTableSQL( $tmpnew ), 'SMW::executeQueries' );
		$this->m_dbs->query( $this->getCreateTempIDTableSQL( $tmpres ), 'SMW::executeQueries' );
		$this->m_dbs->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? "" : "IGNORE" ) . " INTO $tablename (id) VALUES $values", 'SMW::executeHierarchyQuery' );
		$this->m_dbs->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? "" : "IGNORE" ) . " INTO $tmpnew (id) VALUES $values", 'SMW::executeHierarchyQuery' );

		for ( $i = 0; $i < $depth; $i++ ) {
			$this->m_dbs->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? '' : 'IGNORE ' ) .  "INTO $tmpres (id) SELECT s_id" . ( $wgDBtype == 'postgres' ? '::integer':'' ) . " FROM $smwtable, $tmpnew WHERE o_id=id",
						'SMW::executeHierarchyQuery' );
			if ( $this->m_dbs->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$this->m_dbs->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? '' : 'IGNORE ' ) . "INTO $tablename (id) SELECT $tmpres.id FROM $tmpres",
						'SMW::executeHierarchyQuery' );

			if ( $this->m_dbs->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$this->m_dbs->query( 'TRUNCATE TABLE ' . $tmpnew, 'SMW::executeHierarchyQuery' ); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		$this->m_hierarchies[$values] = $tablename;
		$this->m_dbs->query( ( ( $wgDBtype == 'postgres' ) ? 'DROP TABLE IF EXISTS smw_new' : 'DROP TEMPORARY TABLE smw_new' ), 'SMW::executeHierarchyQuery' );
		$this->m_dbs->query( ( ( $wgDBtype == 'postgres' ) ? 'DROP TABLE IF EXISTS smw_res' : 'DROP TEMPORARY TABLE smw_res' ), 'SMW::executeHierarchyQuery' );

		wfProfileOut( $fname );
	}

	/**
	 * This function modifies the given query object at $qid to account for all ordering conditions
	 * in the SMWQuery $query. It is always required that $qid is the id of a query that joins with
	 * smw_ids so that the field alias.smw_title is $available for default sorting.
	 *
	 * @param integer $qid
	 */
	protected function applyOrderConditions( $qid ) {
		$qobj = $this->m_queries[$qid];
		// (1) collect required extra property descriptions:
		$extraproperties = array();

		foreach ( $this->m_sortkeys as $propkey => $order ) {
			if ( !array_key_exists( $propkey, $qobj->sortfields ) ) { // Find missing property to sort by.
				if ( $propkey == '' ) { // Sort by first result column (page titles).
					$qobj->sortfields[$propkey] = "$qobj->alias.smw_sortkey";
				} else { // Try to extend query.
					$extrawhere = '';
					$sortprop = SMWPropertyValue::makeUserProperty( $propkey );

					if ( $sortprop->isValid() ) {
						$extraproperties[] = new SMWSomeProperty( $sortprop, new SMWThingDescription() );
					}
				}
			}
		}

		// (2) compile according conditions and hack them into $qobj:
		if ( count( $extraproperties ) > 0 ) {
			$desc = new SMWConjunction( $extraproperties );
			$newqid = $this->compileQueries( $desc );
			$newqobj = $this->m_queries[$newqid]; // This is always an SMW_SQL2_CONJUNCTION ...

			foreach ( $newqobj->components as $cid => $field ) { // ... so just re-wire its dependencies
				$qobj->components[$cid] = $qobj->joinfield;
				$qobj->sortfields = array_merge( $qobj->sortfields, $this->m_queries[$cid]->sortfields );
			}

			$this->m_queries[$qid] = $qobj;
		}
	}

	/**
	 * Get a SQL option array for the given query and preprocessed query object at given id.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootid
	 */
	protected function getSQLOptions( SMWQuery $query, $rootid ) {
		global $smwgQSortingSupport, $smwgQRandSortingSupport;

		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $smwgQSortingSupport ) {
			$qobj = $this->m_queries[$rootid];

			foreach ( $this->m_sortkeys as $propkey => $order ) {
				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $qobj->sortfields ) ) { // Field was successfully added.
					$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . $qobj->sortfields[$propkey] . " $order ";
				} elseif ( ( $order == 'RANDOM' ) && $smwgQRandSortingSupport ) {
					$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . ' RAND() ';
				}
			}
		}
		return $result;
	}

	/**
	 * After querying, make sure no temporary database tables are left.
	 * @todo I might be better to keep the tables and possibly reuse them later
	 * on. Being temporary, the tables will vanish with the session anyway.
	 */
	protected function cleanUp() {
		global $wgDBtype;
		if ( $this->m_qmode !== SMWQuery::MODE_DEBUG ) {
			foreach ( $this->m_querylog as $table => $log ) {
				$this->m_dbs->query( ( ( $wgDBtype == 'postgres' ) ? "DROP TABLE IF EXISTS ":"DROP TEMPORARY TABLE " ) . $this->m_dbs->tableName( $table ), 'SMW::getQueryResult' );
			}
		}
	}

	/**
	 * Get SQL code suitable to create a temporary table of the given name, used to store ids.
	 * MySQL can do that simply by creating new temporary tables. PostgreSQL first checks if such
	 * a table exists, so the code is ready to reuse existing tables if the code was modified to
	 * keep them after query answering. Also, PostgreSQL tables will use a RULE to achieve built-in
	 * duplicate elimination. The latter is done using INSERT IGNORE in MySQL.
	 *
	 * @param string $tablename
	 */
	protected function getCreateTempIDTableSQL( $tablename ) {
		global $wgDBtype;

		if ( $wgDBtype == 'postgres' ) { // PostgreSQL: no memory tables, use RULE to emulate INSERT IGNORE
			return "CREATE OR REPLACE FUNCTION create_" . $tablename . "() RETURNS void AS "
			. "$$ "
			. "BEGIN "
			. " IF EXISTS(SELECT NULL FROM pg_tables WHERE tablename='" . $tablename . "' AND schemaname = ANY (current_schemas(true))) "
			. " THEN DELETE FROM " . $tablename . "; "
			. " ELSE "
			. "  CREATE TEMPORARY TABLE " . $tablename . " (id INTEGER PRIMARY KEY); "
			. "    CREATE RULE " . $tablename . "_ignore AS ON INSERT TO " . $tablename . " WHERE  (EXISTS (SELECT NULL FROM " . $tablename
			. "	 WHERE (" . $tablename . ".id = new.id))) DO INSTEAD NOTHING; "
			. " END IF; "
			. "END; "
			. "$$ "
			. "LANGUAGE 'plpgsql'; "
			. "SELECT create_" . $tablename . "(); ";
		} else { // MySQL_ just a temporary table, use INSERT IGNORE later
			return "CREATE TEMPORARY TABLE " . $tablename . "( id INT UNSIGNED KEY ) TYPE=MEMORY";
		}
	}

}