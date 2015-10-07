<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\DebugOutputFormatter;
use SMW\SQLStore\TemporaryIdTableCreator;
use SMW\DIWikiPage;
use SMWSQLStore3 as SQLStore;
use SMWQuery as Query;
use SMWSql3SmwIds;
use SMWQueryResult as QueryResult;
use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;
use SMW\InvalidPredefinedPropertyException;
use RuntimeException;

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
class QueryEngine {

	/**
	 * @var SQLStore
	 */
	private $store;

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
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder = null;

	/**
	 * @var QuerySegmentListProcessor
	 */
	private $querySegmentListProcessor = null;

	/**
	 * @var EngineOptions
	 */
	private $engineOptions = null;

	/**
	 * @since 2.2
	 *
	 * @param SQLStore $parentStore
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 * @param QuerySegmentListProcessor $querySegmentListProcessor
	 * @param EngineOptions $engineOptions
	 */
	public function __construct( SQLStore $parentStore, QuerySegmentListBuilder $querySegmentListBuilder, QuerySegmentListProcessor $querySegmentListProcessor, EngineOptions $engineOptions ) {
		$this->store = $parentStore;
		$this->querySegmentListBuilder = $querySegmentListBuilder;
		$this->querySegmentListProcessor = $querySegmentListProcessor;
		$this->engineOptions = $engineOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @return QuerySegmentListBuilder
	 */
	public function getQuerySegmentListBuilder() {
		return $this->querySegmentListBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return QuerySegmentListProcessor
	 */
	public function getQuerySegmentListProcessor() {
		return $this->querySegmentListProcessor;
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
	 * by an QuerySegment object in the array m_queries.
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
			return new QueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, false );
			// NOTE: we check this here to prevent unnecessary work, but we check
			// it after query processing below again in case more errors occurred.
		} elseif ( $query->querymode == Query::MODE_NONE ) {
			// don't query, but return something to printer
			return new QueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, true );
		}

		$db = $this->store->getConnection( 'mw.db' );

		$this->queryMode = $query->querymode;
		$this->querySegmentList = array();

		$this->errors = array();
		QuerySegment::$qnum = 0;
		$this->sortKeys = $query->sortkeys;

		// *** First compute abstract representation of the query (compilation) ***//
		$this->querySegmentListBuilder->setSortKeys( $this->sortKeys );
		$this->querySegmentListBuilder->buildQuerySegmentFor( $query->getDescription() ); // compile query, build query "plan"

		$qid = $this->querySegmentListBuilder->getLastQuerySegmentId();
		$this->querySegmentList = $this->querySegmentListBuilder->getQuerySegmentList();
		$this->errors = $this->querySegmentListBuilder->getErrors();

		if ( $qid < 0 ) { // no valid/supported condition; ensure that at least only proper pages are delivered
			$qid = QuerySegment::$qnum;
			$q = new QuerySegment();
			$q->joinTable = SMWSql3SmwIds::TABLE_NAME;
			$q->joinfield = "$q->alias.smw_id";
			$q->where = "$q->alias.smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) . " AND $q->alias.smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWREDIIW ) . " AND $q->alias.smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWBORDERIW ) . " AND $q->alias.smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWINTDEFIW );
			$this->querySegmentList[$qid] = $q;
		}

		if ( isset( $this->querySegmentList[$qid]->joinTable ) && $this->querySegmentList[$qid]->joinTable != SMWSql3SmwIds::TABLE_NAME ) {
			// manually make final root query (to retrieve namespace,title):
			$rootid = QuerySegment::$qnum;
			$qobj = new QuerySegment();
			$qobj->joinTable  = SMWSql3SmwIds::TABLE_NAME;
			$qobj->joinfield  = "$qobj->alias.smw_id";
			$qobj->components = array( $qid => "$qobj->alias.smw_id" );
			$qobj->sortfields = $this->querySegmentList[$qid]->sortfields;
			$this->querySegmentList[$rootid] = $qobj;
		} else { // not such a common case, but worth avoiding the additional inner join:
			$rootid = $qid;
		}

		// Include order conditions (may extend query if needed for sorting):
		if ( $this->engineOptions->get( 'smwgQSortingSupport' ) ) {
			$this->applyOrderConditions( $rootid );
		}

		// Possibly stop if new errors happened:
		if ( !$this->engineOptions->get( 'smwgIgnoreQueryErrors' ) &&
				$query->querymode != Query::MODE_DEBUG &&
				count( $this->errors ) > 0 ) {
			$query->addErrors( $this->errors );
			return new QueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, false );
		}

		// *** Now execute the computed query ***//
		$this->querySegmentListProcessor->setQueryMode( $this->queryMode );
		$this->querySegmentListProcessor->setQuerySegmentList( $this->querySegmentList );

		// execute query tree, resolve all dependencies
		$this->querySegmentListProcessor->doExecuteSubqueryJoinDependenciesFor( $rootid );

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

		if ( isset( $qobj->joinfield ) && $qobj->joinfield !== '' ) {
			$this->doPrepareDebugQueryResult( $qobj, $sqlOptions, $entries );
		} else {
			$entries['SQL Query'] = 'Empty result, no SQL query created.';
		}

		$auxtables = '';

		foreach ( $this->querySegmentListProcessor->getListOfResolvedQueries() as $table => $log ) {
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

		return DebugOutputFormatter::formatOutputFor( 'SQLStore', $entries, $query );
	}

	private function doPrepareDebugQueryResult( $qobj, $sqlOptions, &$entries ) {

		$db = $this->store->getConnection();
		list( $startOpts, $useIndex, $tailOpts ) = $db->makeSelectOptions( $sqlOptions );

		$entries['SQL Query'] =
		           "SELECT DISTINCT $qobj->alias.smw_id AS id,$qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns,$qobj->alias.smw_iw AS iw,$qobj->alias.smw_subobject AS so,$qobj->alias.smw_sortkey AS sortkey FROM " .
		           $db->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from .
		           ( ( $qobj->where === '' ) ? '':' WHERE ' ) . $qobj->where . "$tailOpts $startOpts $useIndex LIMIT " .
		           $sqlOptions['LIMIT'] . ' OFFSET ' . $sqlOptions['OFFSET'];

		$res = $db->query(
			'EXPLAIN '. $entries['SQL Query'],
			__METHOD__
		);

		// https://dev.mysql.com/doc/refman/5.0/en/explain-output.html
		$entries['SQL Explain'] = "<table><tr><th>ID</th><th>select_type</th><th>table</th><th>type</th><th>possible_keys</th><th>key</th><th>key_len</th><th>ref</th><th>rows</th><th>Extra</th></tr>";

		$hasEntry = false;
		foreach ( $res as $row ) {

			// sqlite doesn't support this, psql does something else
			if ( !isset( $row->id ) ) {
				continue;
			}

			$hasEntry = true;
			$entries['SQL Explain'] .= "<tr><td>". $row->id . "</td><td>" . $row->select_type . "</td><td>" . $row->table . "</td><td>" . $row->type  . "</td><td>" . $row->possible_keys . "</td><td>" .
			$row->key . "</td><td>" . $row->key_len . "</td><td>" . $row->ref . "</td><td>" . $row->rows .  "</td><td>" . $row->Extra . "</td></tr>";
		}

		if ( $hasEntry ) {
			$entries['SQL Explain'] .= '</table>';
		} else {
			$entries['SQL Explain'] = 'Not supported.';
		}

		$entries['SQL Query'] = '<div class="smwpre">' . $entries['SQL Query'] . '</div>';

		$entries['SQL Query'] =  str_replace(
			array( "SELECT DISTINCT", "FROM", "INNER JOIN", "WHERE", "ORDER BY", "LIMIT", "OFFSET" ),
			array( "SELECT DISTINCT<br>&nbsp;", "<br>FROM<br>&nbsp;", "<br>INNER JOIN<br>&nbsp;", "<br>WHERE<br>&nbsp;", "<br>ORDER BY<br>&nbsp;", "<br>LIMIT<br>&nbsp;", "<br>OFFSET<br>&nbsp;" ),
			$entries['SQL Query']
		);

		$db->freeResult( $res );
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

		$queryResult = new QueryResult(
			$query->getDescription()->getPrintrequests(),
			$query,
			array(),
			$this->store,
			false
		);

		$queryResult->setCountValue( 0 );

		$qobj = $this->querySegmentList[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			return 0;
		}

		$db = $this->store->getConnection( 'mw.db' );

		$sql_options = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		$res = $db->select(
			$db->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from,
			"COUNT(DISTINCT $qobj->alias.smw_id) AS count",
			$qobj->where,
			__METHOD__,
			$sql_options
		);

		$row = $db->fetchObject( $res );

		$count = $row->count;
		$db->freeResult( $res );

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
		global $wgDBtype;

		$db = $this->store->getConnection();

		$qobj = $this->querySegmentList[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			$result = new QueryResult( $query->getDescription()->getPrintrequests(), $query, array(), $this->store, false );
			return $result;
		}

		$sql_options = $this->getSQLOptions( $query, $rootid );

		// Selecting those is required in standard SQL (but MySQL does not require it).
		$sortfields = implode( $qobj->sortfields, ',' );

		$res = $db->select(
			$db->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from,
			"DISTINCT $qobj->alias.smw_id AS id,$qobj->alias.smw_title AS t,$qobj->alias.smw_namespace AS ns,$qobj->alias.smw_iw AS iw,$qobj->alias.smw_subobject AS so,$qobj->alias.smw_sortkey AS sortkey" .
			  ( $wgDBtype == 'postgres' ? ( ( $sortfields ? ',' : '' ) . $sortfields ) : '' ),
			$qobj->where,
			__METHOD__,
			$sql_options
		);

		$qr = array();

		$count = 0; // the number of fetched results ( != number of valid results in array $qr)
		$missedCount = 0;
		$dataItemCache = array();
		$logToTable = array();
		$hasFurtherResults = false;

		$prs = $query->getDescription()->getPrintrequests();

		$diHandler = $this->store->getDataItemHandlerForDIType( DataItem::TYPE_WIKIPAGE );

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
				} catch ( InvalidPredefinedPropertyException $e ) {
					$logToTable[$row->t] = "issue creating a {$row->t} dataitem from a database row";
					wfDebugLog( 'smw', __METHOD__ . ' ' . $e->getMessage() . "\n" );
					$dataItem = '';
				}

				if ( $dataItem instanceof DIWikiPage && !isset( $dataItemCache[$dataItem->getHash()] ) ) {
					$count++;
					$dataItemCache[$dataItem->getHash()] = true;
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
			wfDebugLog( 'smw', __METHOD__ . ' ' . implode( ',', $logToTable ) . "\n" );
		}

		if ( $count > $query->getLimit() || ( $count + $missedCount ) > $query->getLimit() ) {
			$hasFurtherResults = true;
		};

		$db->freeResult( $res );
		$result = new QueryResult( $prs, $query, $qr, $this->store, $hasFurtherResults );

		return $result;
	}

	/**
	 * This function modifies the given query object at $qid to account for all ordering conditions
	 * in the Query $query. It is always required that $qid is the id of a query that joins with
	 * SMW IDs table so that the field alias.smw_title is $available for default sorting.
	 *
	 * @param integer $qid
	 */
	private function applyOrderConditions( $qid ) {
		$qobj = $this->querySegmentList[$qid];

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
					$sortprop = PropertyValue::makeUserProperty( $propkey );

					if ( $sortprop->isValid() ) {
						$extraProperties[] = new SomeProperty( $sortprop->getDataItem(), new ThingDescription() );
					}
				}
			}
		}

		return $extraProperties;
	}

	private function compileAccordingConditionsAndHackThemIntoQobj( array $extraProperties, $qobj, $qid ) {
		$this->querySegmentListBuilder->setSortKeys( $this->sortKeys );
		$this->querySegmentListBuilder->buildQuerySegmentFor( new Conjunction( $extraProperties ) );

		$newQuerySegmentId = $this->querySegmentListBuilder->getLastQuerySegmentId();
		$this->querySegmentList = $this->querySegmentListBuilder->getQuerySegmentList();
		$this->errors = $this->querySegmentListBuilder->getErrors();

		$newQuerySegment = $this->querySegmentList[$newQuerySegmentId]; // This is always an QuerySegment::Q_CONJUNCTION ...

		foreach ( $newQuerySegment->components as $cid => $field ) { // ... so just re-wire its dependencies
			$qobj->components[$cid] = $qobj->joinfield;
			$qobj->sortfields = array_merge( $qobj->sortfields, $this->querySegmentList[$cid]->sortfields );
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

		$result = array( 'LIMIT' => $query->getLimit() + 5, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $this->engineOptions->get( 'smwgQSortingSupport' ) ) {
			$qobj = $this->querySegmentList[$rootId];

			foreach ( $this->sortKeys as $propkey => $order ) {

				if ( !is_string( $propkey ) ) {
					throw new RuntimeException( "Expected a string value as sortkey" );
				}

				// #835
				// SELECT DISTINCT and ORDER BY RANDOM causes an issue for postgres
				// Disable RANDOM support for postgres
				if ( $this->store->getConnection()->getType() === 'postgres' ) {
					$this->engineOptions->set( 'smwgQRandSortingSupport', false );
				}

				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $qobj->sortfields ) ) { // Field was successfully added.
					$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . $qobj->sortfields[$propkey] . " $order ";
				} elseif ( ( $order == 'RANDOM' ) && $this->engineOptions->get( 'smwgQRandSortingSupport' ) ) {
					$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . ' RAND() ';
				}
			}
		}
		return $result;
	}

}
