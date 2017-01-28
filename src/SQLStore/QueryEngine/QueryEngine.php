<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\DIWikiPage;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Query\DebugOutputFormatter as QueryDebugOutputFormatter;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMWSql3SmwIds;
use SMWSQLStore3 as SQLStore;
use SMW\QueryEngine as QueryEngineInterface;
use SMW\QueryFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Class that implements query answering for SQLStore.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QueryEngine implements QueryEngineInterface, LoggerAwareInterface {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Query mode copied from given query. Some submethods act differently when
	 * in Query::MODE_DEBUG.
	 *
	 * @var int
	 */
	private $queryMode;

	/**
	 * Array of generated QuerySegment query descriptions (index => object)
	 *
	 * @var QuerySegment[]
	 */
	private $querySegmentList = array();

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during
	 * query processing (where these property names are searched while compiling
	 * the query conditions).
	 *
	 * @var string[]
	 */
	private $sortKeys;

	/**
	 * Local collection of error strings, passed on to callers if possible.
	 *
	 * @var string[]
	 */
	private $errors = array();

	/**
	 * @var QuerySegmentListBuildManager
	 */
	private $querySegmentListBuildManager;

	/**
	 * @var QuerySegmentListProcessor
	 */
	private $querySegmentListProcessor;

	/**
	 * @var EngineOptions
	 */
	private $engineOptions;

	/**
	 * @var OrderConditionsComplementor
	 */
	private $orderConditionsComplementor;

	/**
	 * @var QueryFactory
	 */
	private $queryFactory;

	/**
	 * @since 2.2
	 *
	 * @param SQLStore $store
	 * @param QuerySegmentListBuildManager $querySegmentListBuildManager
	 * @param QuerySegmentListProcessor $querySegmentListProcessor
	 * @param EngineOptions $engineOptions
	 */
	public function __construct( SQLStore $store, QuerySegmentListBuildManager $querySegmentListBuildManager, QuerySegmentListProcessor $querySegmentListProcessor, EngineOptions $engineOptions ) {
		$this->store = $store;
		$this->querySegmentListBuildManager = $querySegmentListBuildManager;
		$this->querySegmentListProcessor = $querySegmentListProcessor;
		$this->engineOptions = $engineOptions;
		$this->queryFactory = new QueryFactory();
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
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
	 * by an QuerySegment object in the array querySegmentList.
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
	 * @param Query $query
	 *
	 * @return mixed depends on $query->querymode
	 */
	public function getQueryResult( Query $query ) {

		if ( ( !$this->engineOptions->get( 'smwgIgnoreQueryErrors' ) || $query->getDescription() instanceof ThingDescription ) &&
		     $query->querymode != Query::MODE_DEBUG &&
		     count( $query->getErrors() ) > 0 ) {
			return $this->queryFactory->newQueryResult( $this->store, $query, array(), false );
			// NOTE: we check this here to prevent unnecessary work, but we check
			// it after query processing below again in case more errors occurred.
		} elseif ( $query->querymode == Query::MODE_NONE || $query->getLimit() < 1 ) {
			// don't query, but return something to printer
			return $this->queryFactory->newQueryResult( $this->store, $query, array(), true );
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		$this->queryMode = $query->querymode;
		$this->querySegmentList = array();

		$this->errors = array();
		QuerySegment::$qnum = 0;
		$this->sortKeys = $query->sortkeys;

		$rootid = $this->querySegmentListBuildManager->getQuerySegmentFrom(
			$query
		);

		$this->querySegmentList = $this->querySegmentListBuildManager->getQuerySegmentList();
		$this->sortKeys = $this->querySegmentListBuildManager->getSortKeys();
		$this->errors = $this->querySegmentListBuildManager->getErrors();

		// Possibly stop if new errors happened:
		if ( !$this->engineOptions->get( 'smwgIgnoreQueryErrors' ) &&
				$query->querymode != Query::MODE_DEBUG &&
				count( $this->errors ) > 0 ) {
			$query->addErrors( $this->errors );
			return $this->queryFactory->newQueryResult( $this->store, $query, array(), false );
		}

		// *** Now execute the computed query ***//
		$this->querySegmentListProcessor->setQueryMode( $this->queryMode );
		$this->querySegmentListProcessor->setQuerySegmentList( $this->querySegmentList );

		// execute query tree, resolve all dependencies
		$this->querySegmentListProcessor->doResolveQueryDependenciesById(
			$rootid
		);

		$this->applyExtraWhereCondition(
			$connection,
			$rootid
		);

		// #835
		// SELECT DISTINCT and ORDER BY RANDOM causes an issue for postgres
		// Disable RANDOM support for postgres
		if ( $connection->isType( 'postgres' ) ) {
			$this->engineOptions->set( 'smwgQRandSortingSupport', false );
		}

		switch ( $query->querymode ) {
			case Query::MODE_DEBUG:
				$result = $this->getDebugQueryResult( $query, $rootid );
			break;
			case Query::MODE_COUNT:
				$result = $this->getCountQueryResult( $query, $rootid );
			break;
			default:
				$result = $this->getInstanceQueryResult( $query, $rootid );
			break;
		}

		$this->querySegmentListProcessor->cleanUp();
		$query->addErrors( $this->errors );

		return $result;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper debug output for the given query.
	 *
	 * @param Query $query
	 * @param integer $rootid
	 *
	 * @return string
	 */
	private function getDebugQueryResult( Query $query, $rootid ) {

		$qobj = $this->querySegmentList[$rootid];
		$entries = array();

		$sqlOptions = $this->getSQLOptions( $query, $rootid );

		$entries['SQL Query'] = '';
		$entries['SQL Explain'] = '';

		$this->doExecuteDebugQueryResult( $qobj, $sqlOptions, $entries );
		$auxtables = '';

		foreach ( $this->querySegmentListProcessor->getListOfResolvedQueries() as $table => $log ) {
			$auxtables .= "<li>Temporary table $table";
			foreach ( $log as $q ) {
				$auxtables .= "<br />&#160;&#160;<tt>$q</tt>";
			}
			$auxtables .= '</li>';
		}

		if ( $auxtables ) {
			$entries['Auxilliary Tables'] = "<ul>$auxtables</ul>";
		} else {
			$entries['Auxilliary Tables'] = 'No auxilliary tables used.';
		}

		return QueryDebugOutputFormatter::getStringFrom( 'SQLStore', $entries, $query );
	}

	private function doExecuteDebugQueryResult( $qobj, $sqlOptions, &$entries ) {

		if ( !isset( $qobj->joinfield ) || $qobj->joinfield === '' ) {
			return $entries['SQL Query'] = 'Empty result, no SQL query created.';
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );
		list( $startOpts, $useIndex, $tailOpts ) = $connection->makeSelectOptions( $sqlOptions );

		$sortfields = implode( $qobj->sortfields, ',' );
		$sortfields = $sortfields ? ', ' . $sortfields : '';

		$sql = "SELECT DISTINCT ".
			"$qobj->alias.smw_id AS id," .
			"$qobj->alias.smw_title AS t," .
			"$qobj->alias.smw_namespace AS ns," .
			"$qobj->alias.smw_iw AS iw," .
			"$qobj->alias.smw_subobject AS so," .
			"$qobj->alias.smw_sortkey AS sortkey" .
			"$sortfields " .
			"FROM " .
			$connection->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from .
			( $qobj->where === '' ? '':' WHERE ' ) . $qobj->where . "$tailOpts $startOpts $useIndex ".
			"LIMIT " . $sqlOptions['LIMIT'] . ' ' .
			"OFFSET " . $sqlOptions['OFFSET'];

		$res = $connection->query(
			'EXPLAIN '. $sql,
			__METHOD__
		);

		$entries['SQL Explain'] = QueryDebugOutputFormatter::doFormatSQLExplainOutput( $connection->getType(), $res );
		$entries['SQL Query'] = QueryDebugOutputFormatter::doFormatSQLStatement( $sql, $qobj->alias );

		$connection->freeResult( $res );
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper counting output for the given query.
	 *
	 * @param Query $query
	 * @param integer $rootid
	 *
	 * @return integer
	 */
	private function getCountQueryResult( Query $query, $rootid ) {

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			array(),
			false
		);

		$queryResult->setCountValue( 0 );

		$qobj = $this->querySegmentList[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			return $queryResult;
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		$res = $connection->select(
			$connection->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from,
			"COUNT(DISTINCT $qobj->alias.smw_id) AS count",
			$qobj->where,
			__METHOD__,
			$sql_options
		);

		$row = $connection->fetchObject( $res );
		$count = 0;

		if ( $row !== false ) {
			$count = $row->count;
		}

		$connection->freeResult( $res );

		$queryResult->setCountValue( $count );

		return $queryResult;
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
	 * @param Query $query
	 * @param integer $rootid
	 *
	 * @return QueryResult
	 */
	private function getInstanceQueryResult( Query $query, $rootid ) {

		$connection = $this->store->getConnection( 'mw.db.queryengine' );
		$qobj = $this->querySegmentList[$rootid];

		// Empty result, no query needed
		if ( $qobj->joinfield === '' ) {
			return $this->queryFactory->newQueryResult(
				$this->store,
				$query,
				array(),
				false
			);
		}

		$sql_options = $this->getSQLOptions( $query, $rootid );

		// Selecting those is required in standard SQL (but MySQL does not require it).
		$sortfields = implode( $qobj->sortfields, ',' );
		$sortfields = $sortfields ? ',' . $sortfields : '';

		$res = $connection->select(
			$connection->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from,
			"DISTINCT ".
			"$qobj->alias.smw_id AS id," .
			"$qobj->alias.smw_title AS t," .
			"$qobj->alias.smw_namespace AS ns," .
			"$qobj->alias.smw_iw AS iw," .
			"$qobj->alias.smw_subobject AS so," .
			"$qobj->alias.smw_sortkey AS sortkey" .
			"$sortfields",
			$qobj->where,
			__METHOD__,
			$sql_options
		);

		$results = array();
		$dataItemCache = array();

		$logToTable = array();
		$hasFurtherResults = false;

		 // Number of fetched results ( != number of valid results in
		 // array $results)
		$count = 0;
		$missedCount = 0;

		$diHandler = $this->store->getDataItemHandlerForDIType(
			DataItem::TYPE_WIKIPAGE
		);

		while ( ( $count < $query->getLimit() ) && ( $row = $connection->fetchObject( $res ) ) ) {
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
				} catch ( PredefinedPropertyLabelMismatchException $e ) {
					$logToTable[$row->t] = "issue creating a {$row->t} dataitem from a database row";
					$this->log( __METHOD__ . ' ' . $e->getMessage() );
					$dataItem = '';
				}

				if ( $dataItem instanceof DIWikiPage && !isset( $dataItemCache[$dataItem->getHash()] ) ) {
					$count++;
					$dataItemCache[$dataItem->getHash()] = true;
					$results[] = $dataItem;
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

		if ( $connection->fetchObject( $res ) ) {
			$count++;
		}

		if ( $logToTable !== array() ) {
			$this->log( __METHOD__ . ' ' . implode( ',', $logToTable ) );
		}

		if ( $count > $query->getLimit() || ( $count + $missedCount ) > $query->getLimit() ) {
			$hasFurtherResults = true;
		};

		$connection->freeResult( $res );

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			$results,
			$hasFurtherResults
		);

		return $queryResult;
	}

	private function applyExtraWhereCondition( $connection, $qid ) {

		if ( !isset( $this->querySegmentList[$qid] ) ) {
			return null;
		}

		$qobj = $this->querySegmentList[$qid];

		// Filter elements that should never appear in a result set
		$extraWhereCondition = array(
			'del'  => "$qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) . " AND $qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
			'redi' => "$qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWREDIIW )
		);

		if ( strpos( $qobj->where, SMW_SQL3_SMWIW_OUTDATED ) === false ) {
			$qobj->where .= $qobj->where === '' ? $extraWhereCondition['del'] : " AND " . $extraWhereCondition['del'];
		}

		if ( strpos( $qobj->where, SMW_SQL3_SMWREDIIW ) === false ) {
			$qobj->where .= $qobj->where === '' ? $extraWhereCondition['redi'] : " AND " . $extraWhereCondition['redi'];
		}

		$this->querySegmentList[$qid] = $qobj;
	}

	/**
	 * Get a SQL option array for the given query and preprocessed query object at given id.
	 *
	 * @param Query $query
	 * @param integer $rootId
	 *
	 * @return array
	 */
	private function getSQLOptions( Query $query, $rootId ) {

		$result = array(
			'LIMIT' => $query->getLimit() + 5,
			'OFFSET' => $query->getOffset()
		);

		if ( !$this->engineOptions->get( 'smwgQSortingSupport' ) ) {
			return $result;
		}

		// Build ORDER BY options using discovered sorting fields.
		$qobj = $this->querySegmentList[$rootId];

		foreach ( $this->sortKeys as $propkey => $order ) {

			if ( !is_string( $propkey ) ) {
				throw new RuntimeException( "Expected a string value as sortkey" );
			}

			if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $qobj->sortfields ) ) { // Field was successfully added.
				$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . $qobj->sortfields[$propkey] . " $order ";
			} elseif ( ( $order == 'RANDOM' ) && $this->engineOptions->get( 'smwgQRandSortingSupport' ) ) {
				$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . ' RAND() ';
			}
		}

		return $result;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
