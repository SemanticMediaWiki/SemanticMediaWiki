<?php

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\QueryOutputFormatter;
use SMW\SQLStore\QueryEngine\ConceptCache;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\SqlQueryPart as SMWSQLStore3Query;
use SMW\SQLStore\QueryEngine\SqlQueryPart;
use SMW\SQLStore\TemporaryIdTableCreator;

/**
 * Class that implements query answering for SMWSQLStore3.
 * @ingroup SMWStore
 */
class SMWSQLStore3QueryEngine {

	/**
	 * @var DatabaseBase
	 */
	private $dbs;

	/**
	 * Parent SMWSQLStore3.
	 *
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * Query mode copied from given query. Some submethods act differently when in SMWQuery::MODE_DEBUG.
	 *
	 * @var int
	 */
	private $queryMode;

	/**
	 * Array of generated SqlQueryPart query descriptions (index => object)
	 *
	 * @var SqlQueryPart[]
	 */
	private $queryParts = array();

	/**
	 * Array of arrays of executed queries, indexed by the temporary table names results were fed into.
	 *
	 * @var array
	 */
	private $executedQueries = array();

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 *
	 * @var string[]
	 */
	private $sortKeys;

	/**
	 * Cache of computed hierarchy queries for reuse ("catetgory/property value string" => "tablename").
	 *
	 * @var string[]
	 */
	private $hierarchyCache = array();

	/**
	 * Local collection of error strings, passed on to callers if possible.
	 *
	 * @var string[]
	 */
	private $errors = array();

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder = null;

	/**
	 * @var TemporaryIdTableCreator
	 */
	private $tempIdTableCreator;

	/**
	 * @param SMWSQLStore3 $parentStore
	 * @param DatabaseBase $db
	 * @param TemporaryIdTableCreator $temporaryIdTableCreator
	 */
	public function __construct( SMWSQLStore3 $parentStore, DatabaseBase $db, TemporaryIdTableCreator $temporaryIdTableCreator ) {
		$this->store = $parentStore;
		$this->dbs = $db;

		// Should be injected but for now we use the hidden construction
		$this->queryBuilder = new QueryBuilder( $this->store );

		$this->tempIdTableCreator = $temporaryIdTableCreator;
	}

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @since 1.8
	 * @param $concept Title
	 * @return array of error strings (empty if no errors occurred)
	 */
	public function refreshConceptCache( Title $concept ) {
		$conceptCache = new ConceptCache( $this, $this->store );

		$errors = $conceptCache->refresh( $concept );

		$this->cleanUp();

		return array_merge( $this->queryBuilder->getErrors(), $errors );
	}

	/**
	 * @param string $conceptDescriptionText
	 *
	 * @return SqlQueryPart|null
	 */
	public function resolveQueryTreeForQueryCondition( $conceptDescriptionText ) {
		global $smwgQConceptFeatures;

		$this->queryMode = SMWQuery::MODE_INSTANCES;
		$this->queryParts = array();
		$this->hierarchyCache = array();
		$this->executedQueries = array();
		$this->sortKeys = array();
		SqlQueryPart::$qnum = 0;

		$this->queryBuilder->setSortKeys( $this->sortKeys );

		$qp = new SMWQueryParser( $smwgQConceptFeatures );
		$this->queryBuilder->buildSqlQueryPartFor( $qp->getQueryDescription( $conceptDescriptionText ) );

		$qid = $this->queryBuilder->getLastSqlQueryPartId();
		$this->queryParts = $this->queryBuilder->getSqlQueryParts();

		if ( $qid < 0 ) {
			return null;
		}

		// execute query tree, resolve all dependencies
		$this->executeQueries( $this->queryParts[$qid] );

		return $this->queryParts[$qid];
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache( $concept ) {
		$conceptCache = new ConceptCache( $this, $this->store );
		$conceptCache->delete( $concept );
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
	 * by an SMWSQLStore3Query object in the array m_queries.
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
	 *
	 * @return mixed: depends on $query->querymode
	 */
	public function getQueryResult( SMWQuery $query ) {
		global $smwgIgnoreQueryErrors, $smwgQSortingSupport;

		if ( ( !$smwgIgnoreQueryErrors || $query->getDescription() instanceof ThingDescription ) &&
		     $query->querymode != SMWQuery::MODE_DEBUG &&
		     count( $query->getErrors() ) > 0 ) {
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, false );
			// NOTE: we check this here to prevent unnecessary work, but we check
			// it after query processing below again in case more errors occurred.
		} elseif ( $query->querymode == SMWQuery::MODE_NONE ) {
			// don't query, but return something to printer
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, true );
		}

		$this->queryMode = $query->querymode;
		$this->queryParts = array();
		$this->hierarchyCache = array();
		$this->executedQueries = array();
		$this->errors = array();
		SMWSQLStore3Query::$qnum = 0;
		$this->sortKeys = $query->sortkeys;

		// *** First compute abstract representation of the query (compilation) ***//
		$this->queryBuilder->setSortKeys( $this->sortKeys );
		$this->queryBuilder->buildSqlQueryPartFor( $query->getDescription() ); // compile query, build query "plan"

		$qid = $this->queryBuilder->getLastSqlQueryPartId();
		$this->queryParts = $this->queryBuilder->getSqlQueryParts();
		$this->errors = $this->queryBuilder->getErrors();

		if ( $qid < 0 ) { // no valid/supported condition; ensure that at least only proper pages are delivered
			$qid = SMWSQLStore3Query::$qnum;
			$q = new SMWSQLStore3Query();
			$q->joinTable = SMWSql3SmwIds::tableName;
			$q->joinfield = "$q->alias.smw_id";
			$q->where = "$q->alias.smw_iw!=" . $this->dbs->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) . " AND $q->alias.smw_iw!=" . $this->dbs->addQuotes( SMW_SQL3_SMWREDIIW ) . " AND $q->alias.smw_iw!=" . $this->dbs->addQuotes( SMW_SQL3_SMWBORDERIW ) . " AND $q->alias.smw_iw!=" . $this->dbs->addQuotes( SMW_SQL3_SMWINTDEFIW );
			$this->queryParts[$qid] = $q;
		}

		if ( $this->queryParts[$qid]->joinTable != SMWSql3SmwIds::tableName ) {
			// manually make final root query (to retrieve namespace,title):
			$rootid = SMWSQLStore3Query::$qnum;
			$qobj = new SMWSQLStore3Query();
			$qobj->joinTable  = SMWSql3SmwIds::tableName;
			$qobj->joinfield  = "$qobj->alias.smw_id";
			$qobj->components = array( $qid => "$qobj->alias.smw_id" );
			$qobj->sortfields = $this->queryParts[$qid]->sortfields;
			$this->queryParts[$rootid] = $qobj;
		} else { // not such a common case, but worth avoiding the additional inner join:
			$rootid = $qid;
		}

		// Include order conditions (may extend query if needed for sorting):
		if ( $smwgQSortingSupport ) {
			$this->applyOrderConditions( $rootid );
		}

		// Possibly stop if new errors happened:
		if ( !$smwgIgnoreQueryErrors &&
                     $query->querymode != SMWQuery::MODE_DEBUG &&
                     count( $this->errors ) > 0 ) {
			$query->addErrors( $this->errors );
			return new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, false );
		}

		// *** Now execute the computed query ***//
		$this->executeQueries( $this->queryParts[$rootid] ); // execute query tree, resolve all dependencies

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
		$query->addErrors( $this->errors );

		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper debug output for the given query.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootid
	 *
	 * @return string
	 */
	private function getDebugQueryResult( SMWQuery $query, $rootid ) {
		$qobj = $this->queryParts[$rootid];

		$db = $this->store->getConnection();

		$entries = array();

		$sql_options = $this->getSQLOptions( $query, $rootid );
		list( $startOpts, $useIndex, $tailOpts ) = $db->makeSelectOptions( $sql_options );

		if ( $qobj->joinfield !== '' ) {
			$entries['SQL Query'] =
			           "<tt>SELECT DISTINCT $qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns FROM " .
			           $db->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from .
			           ( ( $qobj->where === '' ) ? '':' WHERE ' ) . $qobj->where . "$tailOpts LIMIT " .
			           $sql_options['LIMIT'] . ' OFFSET ' . $sql_options['OFFSET'] . ';</tt>';
		} else {
			$entries['SQL Query'] = 'Empty result, no SQL query created.';
		}

		$auxtables = '';
		foreach ( $this->executedQueries as $table => $log ) {
			$auxtables .= "<li>Temporary table $table";
			foreach ( $log as $q ) {
				$auxtables .= "<br />&#160;&#160;<tt>$q</tt>";
			}
			$auxtables .= '</li>';
		}
		if ( $auxtables ) {
			$entries['Auxilliary Tables Used'] = "<ul>$auxtables</ul>";
		} else {
			$entries['Auxilliary Tables Used'] = 'No auxilliary tables used.';
		}

		return QueryOutputFormatter::formatDebugOutput( 'SMWSQLStore3', $entries, $query );
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper counting output for the given query.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootid
	 *
	 * @return integer
	 */
	private function getCountQueryResult( SMWQuery $query, $rootid ) {

		$qobj = $this->queryParts[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			return 0;
		}

		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );
		$res = $this->dbs->select( $this->dbs->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from, "COUNT(DISTINCT $qobj->alias.smw_id) AS count", $qobj->where, 'SMW::getQueryResult', $sql_options );
		$row = $this->dbs->fetchObject( $res );

		$count = $row->count;
		$this->dbs->freeResult( $res );


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
	private function getInstanceQueryResult( SMWQuery $query, $rootid ) {
		global $wgDBtype;

		$db = $this->store->getConnection();

		$qobj = $this->queryParts[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			$result = new SMWQueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, false );
			return $result;
		}

		$sql_options = $this->getSQLOptions( $query, $rootid );

		// Selecting those is required in standard SQL (but MySQL does not require it).
		$sortfields = implode( $qobj->sortfields, ',' );

		$res = $db->select( $db->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from,
			"DISTINCT $qobj->alias.smw_id AS id,$qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns,$qobj->alias.smw_iw AS iw,$qobj->alias.smw_subobject AS so,$qobj->alias.smw_sortkey AS sortkey" .
			  ( $wgDBtype == 'postgres' ? ( ( $sortfields ? ',' : '' ) . $sortfields ) : '' ),
			$qobj->where, 'SMW::getQueryResult', $sql_options );

		$qr = array();

		$count = 0; // the number of fetched results ( != number of valid results in array $qr)
		$missedCount = 0;
		$dataItemCache = array();
		$logToTable = array();
		$hasFurtherResults = false;

		$prs = $query->getDescription()->getPrintrequests();

		$diHandler = $this->store->getDataItemHandlerForDIType( SMWDataItem::TYPE_WIKIPAGE );

		while ( ( $count < $query->getLimit() ) && ( $row = $db->fetchObject( $res ) ) ) {
			if ( $row->iw === '' || $row->iw{0} != ':' )  {

				// Catch exception for non-existing predefined properties that
				// still registered within non-updated pages (@see bug 48711)
				try {
					$dataItem = $diHandler->dataItemFromDBKeys( array(
						$row->t,
						intval( $row->ns ),
						$row->iw,
						'',
						$row->so
					) );
				} catch ( \SMW\InvalidPredefinedPropertyException $e ) {
					$logToTable[$row->t] = "issue creating a {$row->t} dataitem from a database row";
					$this->store->getLogger()->log( __METHOD__, $e->getMessage() );
				}

				if ( $dataItem instanceof SMWDIWikiPage && !isset( $dataItemCache[ $dataItem->getHash() ] ) ) {
					$count++;
					$dataItemCache[ $dataItem->getHash() ] = true;
					$qr[] = $dataItem;
					// These IDs are usually needed for displaying the page (esp. if more property values are displayed):
					$this->store->smwIds->setCache( $row->t, $row->ns, $row->iw, $row->so, $row->id, $row->sortkey );
				} else {
					$missedCount++;
					$logToTable[$row->t] = "skip result for {$row->t} existing cache entry / query " . $query->getHash();
				}
			} else {
				$missedCount++;
				$logToTable[$row->t] = "skip result for {$row->t} due to an internal `{$row->iw}` pointer / query " . $query->getHash();
			}
		}

		if ( $db->fetchObject( $res ) ) {
			$count++;
		}

		if ( $logToTable !== array() ) {
			foreach ( $logToTable as $key => $entry ) {
				$this->store->getLogger()->logToTable( 'sqlstore-query-execution', 'query performer', $key, $entry );
			}
		}

		if ( $count > $query->getLimit() || ( $count + $missedCount ) > $query->getLimit() ) {
			$hasFurtherResults = true;
		};

		$db->freeResult( $res );
		$result = new SMWQueryResult( $prs, $query, $qr, $this->store, $hasFurtherResults );

		return $result;
	}

	/**
	 * Process stored queries and change store accordingly. The query obj is modified
	 * so that it contains non-recursive description of a select to execute for getting
	 * the actual result.
	 *
	 * @param SMWSQLStore3Query $query
	 */
	private function executeQueries( SMWSQLStore3Query &$query ) {
		global $wgDBtype;

		$db = $this->store->getConnection();

		switch ( $query->type ) {
			case SMWSQLStore3Query::Q_TABLE: // Normal query with conjunctive subcondition.
				foreach ( $query->components as $qid => $joinfield ) {
					$subquery = $this->queryParts[$qid];
					$this->executeQueries( $subquery );

					if ( $subquery->joinTable !== '' ) { // Join with jointable.joinfield
						$query->from .= ' INNER JOIN ' . $db->tableName( $subquery->joinTable ) . " AS $subquery->alias ON $joinfield=" . $subquery->joinfield;
					} elseif ( $subquery->joinfield !== '' ) { // Require joinfield as "value" via WHERE.
						$condition = '';

						foreach ( $subquery->joinfield as $value ) {
							$condition .= ( $condition ? ' OR ':'' ) . "$joinfield=" . $db->addQuotes( $value );
						}

						if ( count( $subquery->joinfield ) > 1 ) {
							$condition = "($condition)";
						}

						$query->where .= ( ( $query->where === '' ) ? '':' AND ' ) . $condition;
					} else { // interpret empty joinfields as impossible condition (empty result)
						$query->joinfield = ''; // make whole query false
						$query->joinTable = '';
						$query->where = '';
						$query->from = '';
						break;
					}

					if ( $subquery->where !== '' ) {
						$query->where .= ( ( $query->where === '' ) ? '':' AND ' ) . '(' . $subquery->where . ')';
					}

					$query->from .= $subquery->from;
				}

				$query->components = array();
			break;
			case SMWSQLStore3Query::Q_CONJUNCTION:
				// pick one subquery with jointable as anchor point ...
				reset( $query->components );
				$key = false;

				foreach ( $query->components as $qkey => $qid ) {
					if ( $this->queryParts[$qkey]->joinTable !== '' ) {
						$key = $qkey;
						break;
					}
				}

				if ( $key !== false ) {
					$result = $this->queryParts[$key];
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
					$result = $this->queryParts[$key];
					unset( $query->components[$key] );

					foreach ( $query->components as $qid => $joinfield ) {
						if ( $result->joinfield != $this->queryParts[$qid]->joinfield ) {
							$result->joinfield = ''; // all other values should already be ''
							break;
						}
					}
				}
				$query = $result;
			break;
			case SMWSQLStore3Query::Q_DISJUNCTION:
				if ( $this->queryMode !== SMWQuery::MODE_DEBUG ) {
					$db->query( $this->getCreateTempIDTableSQL( $db->tableName( $query->alias ) ), 'SMW::executeQueries' );
				}

				$this->executedQueries[$query->alias] = array();

				foreach ( $query->components as $qid => $joinfield ) {
					$subquery = $this->queryParts[$qid];
					$this->executeQueries( $subquery );
					$sql = '';

					if ( $subquery->joinTable !== '' ) {
						$sql = 'INSERT ' . ( ( $wgDBtype == 'postgres' ) ? '':'IGNORE ' ) . 'INTO ' .
						       $db->tableName( $query->alias ) .
							   " SELECT $subquery->joinfield FROM " . $db->tableName( $subquery->joinTable ) .
							   " AS $subquery->alias $subquery->from" . ( $subquery->where ? " WHERE $subquery->where":'' );
					} elseif ( $subquery->joinfield !== '' ) {
						// NOTE: this works only for single "unconditional" values without further
						// WHERE or FROM. The execution must take care of not creating any others.
						$values = '';

						foreach ( $subquery->joinfield as $value ) {
							$values .= ( $values ? ',' : '' ) . '(' . $db->addQuotes( $value ) . ')';
						}

						$sql = 'INSERT ' . ( ( $wgDBtype == 'postgres' ) ? '':'IGNORE ' ) .  'INTO ' . $db->tableName( $query->alias ) . " (id) VALUES $values";
					} // else: // interpret empty joinfields as impossible condition (empty result), ignore
					if ( $sql ) {
						$this->executedQueries[$query->alias][] = $sql;

						if ( $this->queryMode !== SMWQuery::MODE_DEBUG ) {
							$db->query( $sql , 'SMW::executeQueries' );
						}
					}
				}

				$query->joinTable = $query->alias;
				$query->joinfield = "$query->alias.id";
				$query->sortfields = array(); // Make sure we got no sortfields.
				// TODO: currently this eliminates sortkeys, possibly keep them (needs different temp table format though, maybe not such a good thing to do)
			break;
			case SMWSQLStore3Query::Q_PROP_HIERARCHY:
			case SMWSQLStore3Query::Q_CLASS_HIERARCHY: // make a saturated hierarchy
				$this->executeHierarchyQuery( $query );
			break;
			case SMWSQLStore3Query::Q_VALUE: break; // nothing to do
		}
	}

	/**
	 * Find subproperties or subcategories. This may require iterative computation,
	 * and temporary tables are used in many cases.
	 *
	 * @param SMWSQLStore3Query $query
	 */
	private function executeHierarchyQuery( SMWSQLStore3Query &$query ) {
		global $wgDBtype, $smwgQSubpropertyDepth, $smwgQSubcategoryDepth;

		$db = $this->store->getConnection();

		$depth = ( $query->type == SMWSQLStore3Query::Q_PROP_HIERARCHY ) ? $smwgQSubpropertyDepth : $smwgQSubcategoryDepth;

		if ( $depth <= 0 ) { // treat as value, no recursion
			$query->type = SMWSQLStore3Query::Q_VALUE;
			return;
		}

		$values = '';
		$valuecond = '';

		foreach ( $query->joinfield as $value ) {
			$values .= ( $values ? ',':'' ) . '(' . $db->addQuotes( $value ) . ')';
			$valuecond .= ( $valuecond ? ' OR ':'' ) . 'o_id=' . $db->addQuotes( $value );
		}

		$propertyKey = ( $query->type == SMWSQLStore3Query::Q_PROP_HIERARCHY ) ? '_SUBP' : '_SUBC';
		$smwtable = $db->tableName(
				$this->store->findPropertyTableID( new SMWDIProperty( $propertyKey ) ) );

		// Try to safe time (SELECT is cheaper than creating/dropping 3 temp tables):
		$res = $db->select( $smwtable, 's_id', $valuecond, __METHOD__, array( 'LIMIT' => 1 ) );

		if ( !$db->fetchObject( $res ) ) { // no subobjects, we are done!
			$db->freeResult( $res );
			$query->type = SMWSQLStore3Query::Q_VALUE;
			return;
		}

		$db->freeResult( $res );
		$tablename = $db->tableName( $query->alias );
		$this->executedQueries[$query->alias] = array( "Recursively computed hierarchy for element(s) $values." );
		$query->joinTable = $query->alias;
		$query->joinfield = "$query->alias.id";

		if ( $this->queryMode == SMWQuery::MODE_DEBUG ) {
			return; // No real queries in debug mode.
		}

		$db->query( $this->getCreateTempIDTableSQL( $tablename ), 'SMW::executeHierarchyQuery' );

		if ( array_key_exists( $values, $this->hierarchyCache ) ) { // Just copy known result.
			$db->query( "INSERT INTO $tablename (id) SELECT id" .
								' FROM ' . $this->hierarchyCache[$values],
								'SMW::executeHierarchyQuery' );
			return;
		}

		// NOTE: we use two helper tables. One holds the results of each new iteration, one holds the
		// results of the previous iteration. One could of course do with only the above result table,
		// but then every iteration would use all elements of this table, while only the new ones
		// obtained in the previous step are relevant. So this is a performance measure.
		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';
		$db->query( $this->getCreateTempIDTableSQL( $tmpnew ), 'SMW::executeQueries' );
		$db->query( $this->getCreateTempIDTableSQL( $tmpres ), 'SMW::executeQueries' );
		$db->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? "" : "IGNORE" ) . " INTO $tablename (id) VALUES $values", 'SMW::executeHierarchyQuery' );
		$db->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? "" : "IGNORE" ) . " INTO $tmpnew (id) VALUES $values", 'SMW::executeHierarchyQuery' );

		for ( $i = 0; $i < $depth; $i++ ) {
			$db->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? '' : 'IGNORE ' ) .  "INTO $tmpres (id) SELECT s_id" . ( $wgDBtype == 'postgres' ? '::integer':'' ) . " FROM $smwtable, $tmpnew WHERE o_id=id",
						'SMW::executeHierarchyQuery' );
			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$db->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? '' : 'IGNORE ' ) . "INTO $tablename (id) SELECT $tmpres.id FROM $tmpres",
						'SMW::executeHierarchyQuery' );

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$db->query( 'TRUNCATE TABLE ' . $tmpnew, 'SMW::executeHierarchyQuery' ); // empty "new" table
			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		$this->hierarchyCache[$values] = $tablename;
		$db->query( ( ( $wgDBtype == 'postgres' ) ? 'DROP TABLE IF EXISTS smw_new' : 'DROP TEMPORARY TABLE smw_new' ), 'SMW::executeHierarchyQuery' );
		$db->query( ( ( $wgDBtype == 'postgres' ) ? 'DROP TABLE IF EXISTS smw_res' : 'DROP TEMPORARY TABLE smw_res' ), 'SMW::executeHierarchyQuery' );

	}

	/**
	 * This function modifies the given query object at $qid to account for all ordering conditions
	 * in the SMWQuery $query. It is always required that $qid is the id of a query that joins with
	 * SMW IDs table so that the field alias.smw_title is $available for default sorting.
	 *
	 * @param integer $qid
	 */
	private function applyOrderConditions( $qid ) {
		$qobj = $this->queryParts[$qid];

		$extraProperties = $this->collectedRequiredExtraPropertyDescriptions( $qobj );

		if ( count( $extraProperties ) > 0 ) {
			$this->compileAccordingConditionsAndHackThemIntoQobj( $extraProperties, $qobj, $qid );
		}
	}

	private function collectedRequiredExtraPropertyDescriptions( $qobj ) {
		$extraProperties = array();

		foreach ( $this->sortKeys as $propkey => $order ) {

			if ( !is_string( $propkey ) ) {
				throw new RuntimeException( "Expected a string value as sortkey" );
			}

			if ( !array_key_exists( $propkey, $qobj->sortfields ) ) { // Find missing property to sort by.
				if ( $propkey === '' ) { // Sort by first result column (page titles).
					$qobj->sortfields[$propkey] = "$qobj->alias.smw_sortkey";
				} else { // Try to extend query.
					$sortprop = SMWPropertyValue::makeUserProperty( $propkey );

					if ( $sortprop->isValid() ) {
						$extraProperties[] = new SomeProperty( $sortprop->getDataItem(), new ThingDescription() );
					}
				}
			}
		}

		return $extraProperties;
	}

	private function compileAccordingConditionsAndHackThemIntoQobj( array $extraProperties, $qobj, $qid ) {
		$this->queryBuilder->setSortKeys( $this->sortKeys );
		$this->queryBuilder->buildSqlQueryPartFor( new Conjunction( $extraProperties ) );

		$newQueryPartId = $this->queryBuilder->getLastSqlQueryPartId();
		$this->queryParts = $this->queryBuilder->getSqlQueryParts();
		$this->errors = $this->queryBuilder->getErrors();

		$newQueryPart = $this->queryParts[$newQueryPartId]; // This is always an SMWSQLStore3Query::Q_CONJUNCTION ...

		foreach ( $newQueryPart->components as $cid => $field ) { // ... so just re-wire its dependencies
			$qobj->components[$cid] = $qobj->joinfield;
			$qobj->sortfields = array_merge( $qobj->sortfields, $this->queryParts[$cid]->sortfields );
		}

		$this->queryParts[$qid] = $qobj;
	}

	/**
	 * Get a SQL option array for the given query and preprocessed query object at given id.
	 *
	 * @param SMWQuery $query
	 * @param integer $rootId
	 *
	 * @return array
	 */
	private function getSQLOptions( SMWQuery $query, $rootId ) {
		global $smwgQSortingSupport, $smwgQRandSortingSupport;

		$result = array( 'LIMIT' => $query->getLimit() + 5, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $smwgQSortingSupport ) {
			$qobj = $this->queryParts[$rootId];

			foreach ( $this->sortKeys as $propkey => $order ) {

				if ( !is_string( $propkey ) ) {
					throw new RuntimeException( "Expected a string value as sortkey" );
				}

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
	private function cleanUp() {
		global $wgDBtype;

		$db = $this->store->getConnection();

		if ( $this->queryMode !== SMWQuery::MODE_DEBUG ) {
			foreach ( $this->executedQueries as $table => $log ) {
				$db->query( ( ( $wgDBtype == 'postgres' ) ? "DROP TABLE IF EXISTS ":"DROP TEMPORARY TABLE " ) . $db->tableName( $table ), 'SMW::getQueryResult' );
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
	 * @param string $tableName
	 *
	 * @return string
	 */
	private function getCreateTempIDTableSQL( $tableName ) {
		return $this->tempIdTableCreator->getSqlToCreate( $tableName );
	}

}
