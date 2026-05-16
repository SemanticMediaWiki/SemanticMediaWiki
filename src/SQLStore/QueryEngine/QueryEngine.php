<?php

namespace SMW\SQLStore\QueryEngine;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SMW\DataItems\DataItem;
use SMW\DataItems\WikiPage;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\OptionsBuilder;
use SMW\Query\DebugFormatter;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\QueryEngine as QueryEngineInterface;
use SMW\QueryFactory;
use SMW\SQLStore\KeysetPredicateBuilder;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\Platform\ISQLPlatform;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Class that implements query answering for SQLStore.
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QueryEngine implements QueryEngineInterface, LoggerAwareInterface {

	private ?LoggerInterface $logger = null;

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
	private $querySegmentList = [];

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
	private array $errors = [];

	private QueryFactory $queryFactory;

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly SQLStore $store,
		private readonly ConditionBuilder $conditionBuilder,
		private readonly QuerySegmentListProcessor $querySegmentListProcessor,
		private readonly EngineOptions $engineOptions,
		private readonly SubqueryQueryBuilder $subqueryQueryBuilder,
	) {
		$this->queryFactory = new QueryFactory();
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ): void {
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
			return $this->queryFactory->newQueryResult( $this->store, $query, [], false );
			// NOTE: we check this here to prevent unnecessary work, but we check
			// it after query processing below again in case more errors occurred.
		} elseif ( $query->querymode == Query::MODE_NONE || $query->getLimit() < 1 ) {
			// don't query, but return something to printer
			return $this->queryFactory->newQueryResult( $this->store, $query, [], true );
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		$this->queryMode = $query->querymode;
		$this->querySegmentList = [];

		$this->errors = [];
		QuerySegment::$qnum = 0;
		$this->sortKeys = $query->sortkeys;

		$rootid = $this->conditionBuilder->buildCondition(
			$query
		);

		$this->querySegmentList = $this->conditionBuilder->getQuerySegmentList();
		$this->sortKeys = $this->conditionBuilder->getSortKeys();
		$this->errors = $this->conditionBuilder->getErrors();

		// Possibly stop if new errors happened:
		if ( !$this->engineOptions->get( 'smwgIgnoreQueryErrors' ) &&
				$query->querymode != Query::MODE_DEBUG &&
				count( $this->errors ) > 0 ) {
			$query->addErrors( $this->errors );
			return $this->queryFactory->newQueryResult( $this->store, $query, [], false );
		}

		// *** Now execute the computed query ***//
		$this->querySegmentListProcessor->setQueryMode(
			$this->queryMode
		);

		$this->querySegmentListProcessor->setQuerySegmentList(
			$this->querySegmentList
		);

		// execute query tree, resolve all dependencies
		$this->querySegmentListProcessor->process(
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
			$this->engineOptions->set(
				'smwgQSortFeatures',
				(int)$this->engineOptions->get( 'smwgQSortFeatures' ) & ~SMW_QSORT_RANDOM
			);
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
	 * @param int $rootid
	 *
	 * @return string
	 */
	private function getDebugQueryResult( Query $query, $rootid ): string {
		$qobj = $this->querySegmentList[$rootid] ?? 0;
		$entries = [];

		$debugFormatter = new DebugFormatter(
			$this->store->getConnection( 'mw.db.queryengine' )->getType()
		);

		$debugFormatter->setName( 'SQLStore' );

		$sqlOptions = $this->getSQLOptions( $query, $rootid );

		$entries['SQL Query'] = '';
		$entries['SQL Explain'] = '';

		$this->doExecuteDebugQueryResult( $debugFormatter, $qobj, $sqlOptions, $entries );
		$auxtables = '';

		foreach ( $this->querySegmentListProcessor->getExecutedQueries() as $table => $log ) {
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

		return $debugFormatter->buildHTML( $entries, $query );
	}

	private function doExecuteDebugQueryResult( DebugFormatter $debugFormatter, $qobj, array $sqlOptions, array &$entries ) {
		if ( !is_object( $qobj ) || !$qobj->joinfield ) {
			$entries['SQL Query'] = 'Empty result, no SQL query created.';
			return $entries['SQL Query'];
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		if ( $this->engineOptions->get( 'smwgQUseLegacyQuery' ) ) {
			[ $startOpts, $useIndex, $tailOpts ] = OptionsBuilder::makeSelectOptions( $connection, $sqlOptions );

			$sortfields = implode( ',', $qobj->sortfields );
			$sortfields = $sortfields ? ', ' . $sortfields : '';

			$sql = "SELECT DISTINCT " .
				"$qobj->alias.smw_id AS id," .
				"$qobj->alias.smw_title AS t," .
				"$qobj->alias.smw_namespace AS ns," .
				"$qobj->alias.smw_iw AS iw," .
				"$qobj->alias.smw_subobject AS so," .
				"$qobj->alias.smw_sortkey AS sortkey" .
				"$sortfields " .
				"FROM " .
				$connection->tableName( $qobj->joinTable ) . " AS $qobj->alias" . $qobj->from .
				( $qobj->where === '' ? '' : ' WHERE ' ) . $qobj->where . "$tailOpts $startOpts $useIndex " .
				"LIMIT " . $sqlOptions['LIMIT'] . ' ' .
				"OFFSET " . $sqlOptions['OFFSET'];
		} else {
			$sql = $this->subqueryQueryBuilder->buildInstanceQuerySQL(
				$qobj,
				$sqlOptions,
				''
			);
		}

		if ( $connection->isType( 'sqlite' ) ) {
			$query = "EXPLAIN QUERY PLAN $sql";
		} else {
			$format = $debugFormatter->getFormat();
			$query = "EXPLAIN $format $sql";
		}

		$res = $connection->query( $query, __METHOD__, ISQLPlatform::QUERY_CHANGE_NONE );

		$entries['SQL Explain'] = $debugFormatter->prettifyExplain( new ResultIterator( $res ) );
		$entries['SQL Query'] = $debugFormatter->prettifySQL( $sql, $qobj->alias );

		$res->free();
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid, compute
	 * the proper counting output for the given query.
	 *
	 * @param Query $query
	 * @param int $rootid
	 *
	 * @return QueryResult
	 */
	private function getCountQueryResult( Query $query, $rootid ) {
		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			[],
			false
		);

		$queryResult->setCountValue( 0 );

		$qobj = $this->querySegmentList[$rootid];

		if ( $qobj->joinfield === '' ) { // empty result, no query needed
			return $queryResult;
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		$sql_options = [ 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() ];

		if ( $this->engineOptions->get( 'smwgQUseLegacyQuery' ) ) {
			$qb = $connection->newSelectQueryBuilder()
				->select( [ 'count' => "COUNT(DISTINCT $qobj->alias.smw_id)" ] )
				->rawTables( array_merge( [ $qobj->alias => $qobj->joinTable ], $qobj->fromTables ) )
				->joinConds( $qobj->joinConditions )
				->options( $sql_options )
				->caller( __METHOD__ );

			if ( $qobj->where !== '' ) {
				$qb->where( [ $qobj->where ] );
			}

			$res = $qb->fetchResultSet();
		} else {
			$sql = $this->subqueryQueryBuilder->buildCountQuerySQL(
				$qobj,
				$sql_options,
				''
			);
			$res = $connection->readQuery( $sql, __METHOD__, ISQLPlatform::QUERY_CHANGE_NONE );
		}

		$row = $res->fetchObject();
		$count = 0;

		if ( $row !== false ) {
			$count = $row->count;
		}

		$res->free();

		$queryResult->setCountValue( $count );

		return $queryResult;
	}

	/**
	 * Using a preprocessed internal query description referenced by $rootid,
	 * compute the proper result instance output for the given query.
	 *
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
	 * @param int $rootid
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
				[],
				false
			);
		}

		$sql_options = $this->getSQLOptions( $query, $rootid );
		$cursorMode = $query->getCursorAfter() !== null;
		$cursorSortPropKeys = [];
		$cursorSortColumns = [];
		$cursorSortOrders = [];

		if ( $cursorMode ) {
			// The active sort keys are determined by `$query->sortkeys`
			// (set by `QueryCreator` from `sort=`), not by the incoming
			// cursor payload. The cursor's `sort_prop` was already
			// cross-checked against `sort=` in `QueryCreator`. Phase
			// 3b-iii allows per-level directions across multiple custom
			// sort keys; the engine carries them as a parallel array
			// to `$cursorSortColumns` so the predicate builder can flip
			// the operator at each level independently.
			foreach ( $query->sortkeys as $propKey => $order ) {
				if ( $propKey !== '' ) {
					$cursorSortPropKeys[] = $propKey;
					$cursorSortOrders[] = $order === 'DESC' ? 'DESC' : 'ASC';
				}
			}
			if ( $cursorSortPropKeys === [] ) {
				$defaultOrder = ( $query->sortkeys[''] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';
				$cursorSortOrders = [ $defaultOrder ];
			}

			if ( $cursorSortPropKeys !== [] ) {
				foreach ( $cursorSortPropKeys as $key ) {
					$sortExpr = $qobj->sortfields[$key] ?? '';
					if ( $sortExpr === '' ) {
						// User asked for `sort=Foo` but `Foo` could not
						// be resolved to a sortfield column (invalid
						// property, or the joined table never got set
						// up). Surface explicitly rather than silently
						// fall back, since the next cursor anchor would
						// be meaningless.
						$query->addErrors( [
							"Cursor mode could not resolve `sort=$key` to a query sortfield."
						] );
						return $this->queryFactory->newQueryResult(
							$this->store, $query, [], false
						);
					}
					if ( strpos( $sortExpr, ',' ) !== false ) {
						// Defensive: `OrderCondition` writes a
						// comma-separated multi-column expression for
						// the special `#` sort key (matches multiple
						// `smw_*` columns at once). The keyset
						// predicate's `<sortExpr> OP <value>` can't
						// usefully target a column list. Cursor mode
						// for `#` is undefined; reject rather than
						// emit malformed SQL.
						$query->addErrors( [
							"Cursor mode is not supported for the multi-column sort expression at `sort=$key`."
						] );
						return $this->queryFactory->newQueryResult(
							$this->store, $query, [], false
						);
					}
					$cursorSortColumns[] = $sortExpr;
				}
			} else {
				// Default sort (Phase 3 spike compatible). The empty
				// key maps to `smw_sort` and stays a single column.
				$cursorSortColumns = [ "$qobj->alias.smw_sort" ];
			}

			// Validate the cursor payload's sort tuple length matches
			// the active sort column count. `QueryCreator` cross-checks
			// `sort_prop` shape against `sort=`, so reaching here with
			// a mismatch means either a malformed payload or a `Query`
			// constructed bypassing `QueryCreator`. Either way, silently
			// running without the keyset predicate would return page 1
			// of the full result set, looping a paginating client.
			$cursorPayload = $query->getCursorAfter();
			if ( isset( $cursorPayload['sort'] ) ) {
				$payloadSortValues = is_array( $cursorPayload['sort'] )
					? $cursorPayload['sort']
					: [ $cursorPayload['sort'] ];
				if ( count( $payloadSortValues ) !== count( $cursorSortColumns ) ) {
					$query->addErrors( [
						'Cursor payload shape does not match the query sort: cursor has ' .
						count( $payloadSortValues ) . ' sort value(s) but query has ' .
						count( $cursorSortColumns ) . ' sort column(s).'
					] );
					return $this->queryFactory->newQueryResult(
						$this->store, $query, [], false
					);
				}
			}

			// Override sort and offset for cursor mode. Keyset is
			// total-ordered on `(<col_1>, ..., <col_n>, smw_id)`.
			// Each level uses its own direction; the smw_id tiebreak
			// adopts the LAST level's direction so the tied-tuple walk
			// chains naturally off the final sort column. For uniform
			// requests, this collapses to "everything in one direction"
			// (the pre-3b-iii behaviour).
			$orderByParts = [];
			foreach ( $cursorSortColumns as $i => $col ) {
				$orderByParts[] = "$col {$cursorSortOrders[$i]}";
			}
			$tiebreakDir = $cursorSortOrders[count( $cursorSortOrders ) - 1];
			$orderByParts[] = "$qobj->alias.smw_id $tiebreakDir";
			$sql_options['ORDER BY'] = implode( ', ', $orderByParts );
			unset( $sql_options['OFFSET'] );
		}

		if ( $this->engineOptions->get( 'smwgQUseLegacyQuery' ) ) {
			$selectFields = [
				'id' => "$qobj->alias.smw_id",
				't' => "$qobj->alias.smw_title",
				'ns' => "$qobj->alias.smw_namespace",
				'iw' => "$qobj->alias.smw_iw",
				'so' => "$qobj->alias.smw_subobject",
				'sortkey' => "$qobj->alias.smw_sortkey",
			];
			if ( $cursorMode ) {
				// Alias each sort column under `cursor_sort_N` so the
				// row loop can read `$row->cursor_sort_0`,
				// `$row->cursor_sort_1`, ... regardless of whether the
				// user specified one or multiple sort properties.
				foreach ( $cursorSortColumns as $i => $col ) {
					$selectFields["cursor_sort_$i"] = $col;
				}
			}

			$qb = $connection->newSelectQueryBuilder()
				->select( array_merge(
					$selectFields,
					array_values( $qobj->sortfields ) # TODO strange to only keep values, but it was like that before rewriting select() with arrays
				) )
				->rawTables( array_merge( [ $qobj->alias => $qobj->joinTable ], $qobj->fromTables ) )
				->joinConds( $qobj->joinConditions )
				->options( $sql_options )
				->distinct()
				->caller( __METHOD__ );

			if ( $qobj->where !== '' ) {
				$qb->where( [ $qobj->where ] );
			}

			if ( $cursorMode ) {
				$this->applyKeysetPredicate( $qb, $query, $qobj, $connection, $cursorSortColumns, $cursorSortOrders );
			}

			$res = $qb->fetchResultSet();
		} else {
			// Phase 3c: cursor mode now uses `SubqueryQueryBuilder`'s
			// derived-table rewrite. The cursor predicate is built as a
			// raw SQL string and ANDed into the inner WHERE.
			$cursorPredicate = '';
			if ( $cursorMode ) {
				$cursorPredicate = $this->buildKeysetPredicateString(
					$query, $qobj, $connection, $cursorSortColumns, $cursorSortOrders
				);
			}
			$sql = $this->subqueryQueryBuilder->buildInstanceQuerySQL(
				$qobj,
				$sql_options,
				'',
				$cursorPredicate,
				$cursorMode ? $cursorSortColumns : []
			);
			$res = $connection->readQuery( $sql, __METHOD__, ISQLPlatform::QUERY_CHANGE_NONE );
		}

		$results = [];
		$dataItemCache = [];

		$logToTable = [];
		$hasFurtherResults = false;

		// Cursor-mode bookkeeping: track the sort values + smw_id of
		// the last accepted row so we can mint the next-page anchor.
		// Length matches `$cursorSortColumns` (one value per sort field).
		$lastCursorSorts = null;
		$lastCursorId = null;

		// Number of fetched results ( != number of valid results in
		// array $results)
		$count = 0;
		$missedCount = 0;

		$diHandler = $this->store->getDataItemHandlerForDIType(
			DataItem::TYPE_WIKIPAGE
		);

		$row = $res->fetchObject();
		while ( ( $count < $query->getLimit() ) && $row ) {
			if ( $row->iw === '' || $row->iw[0] != ':' ) {

				// Catch exception for non-existing predefined properties that
				// still registered within non-updated pages (@see bug 48711)
				try {
					$dataItem = $diHandler->dataItemFromDBKeys( [
						$row->t,
						intval( $row->ns ),
						$row->iw,
						$row->sortkey ?? '',
						$row->so
					] );

					// Register the ID in an event the post-proceesing
					// fails (namespace no longer valid etc.)
					$dataItem->setId( $row->id );
				} catch ( PredefinedPropertyLabelMismatchException $e ) {
					$logToTable[$row->t] = "issue creating a {$row->t} dataitem from a database row";
					$this->log( __METHOD__ . ' ' . $e->getMessage() );
					$dataItem = '';
				}

				if ( $dataItem instanceof WikiPage && !isset( $dataItemCache[$dataItem->getHash()] ) ) {
					$count++;
					$dataItemCache[$dataItem->getHash()] = true;
					$results[] = $dataItem;
					// These IDs are usually needed for displaying the page (esp. if more property values are displayed):
					$this->store->smwIds->setCache( $row->t, $row->ns, $row->iw, $row->so, $row->id, $row->sortkey );

					if ( $cursorMode ) {
						$lastCursorSorts = [];
						foreach ( $cursorSortColumns as $i => $_col ) {
							$lastCursorSorts[] = $row->{"cursor_sort_$i"} ?? null;
						}
						$lastCursorId = (int)$row->id;
					}
				} else {
					$missedCount++;
					$logToTable[$row->t] = "skip result for {$row->t} existing cache entry / query " . $query->getHash();
				}
			} else {
				$missedCount++;
				$logToTable[$row->t] = "skip result for {$row->t} due to an internal `{$row->iw}` pointer / query " . $query->getHash();
			}
			if ( $count < $query->getLimit() ) {
				$row = $res->fetchObject();
			}
		}

		if ( $res->fetchObject() ) {
			$count++;
		}

		if ( $logToTable !== [] ) {
			$this->log( __METHOD__ . ' ' . implode( ',', $logToTable ) );
		}

		if ( $count > $query->getLimit() || ( $count + $missedCount ) > $query->getLimit() ) {
			$hasFurtherResults = true;
		}

		$res->free();

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			$results,
			$hasFurtherResults
		);

		// Cursor minted from a row whose sort value is NULL would be
		// rejected by `applyKeysetPredicate()` on the next request
		// (the `isset()` guard there treats null as "no anchor" and
		// silently serves page 1, looping the crawler). Same for
		// multi-sort: if ANY sort level is NULL the cursor anchor is
		// undefined. Skip emission so the client stops paginating
		// instead of looping. Phase 3c can add explicit `IS NULL`
		// handling when the cursor format supports nullability flags.
		$hasNullSort = $lastCursorSorts !== null
			&& in_array( null, $lastCursorSorts, true );
		if ( $cursorMode && $hasFurtherResults && $lastCursorId !== null && $lastCursorSorts !== null && !$hasNullSort ) {
			// Single-sort cursors keep the spike's scalar `sort`
			// payload shape (round-trippable with Phase 3 spike + 3a
			// + 3b-i cursors). Multi-sort cursors use array shape.
			$payload = [
				'sort' => count( $lastCursorSorts ) === 1
					? $lastCursorSorts[0]
					: $lastCursorSorts,
				'id'   => $lastCursorId,
			];
			// `sort_prop` matches the shape of `sort`: scalar for
			// single-sort (one custom key), array for multi-sort.
			// Omitted entirely for default-sort queries to keep the
			// spike's `{"v":1,"sort":...,"id":...}` shape compatible.
			if ( $cursorSortPropKeys !== [] ) {
				$payload['sort_prop'] = count( $cursorSortPropKeys ) === 1
					? $cursorSortPropKeys[0]
					: $cursorSortPropKeys;
			}
			// Round-trip the sort order: a cursor minted for a given
			// direction must be rejected by a request specifying a
			// different direction (the predicate would seek the wrong
			// way at that level).
			//
			//   - All-ASC: omit `sort_order` (keeps spike/3a ASC
			//     cursors round-trippable as-is).
			//   - Uniform DESC: emit `sort_order` as the scalar string
			//     "DESC" (keeps 3b-i/3b-ii uniform-DESC cursors
			//     round-trippable as-is).
			//   - Mixed per-level: emit `sort_order` as a per-level
			//     array (Phase 3b-iii shape).
			$uniformOrder = count( array_unique( $cursorSortOrders ) ) === 1
				? $cursorSortOrders[0]
				: null;
			if ( $uniformOrder === 'DESC' ) {
				$payload['sort_order'] = 'DESC';
			} elseif ( $uniformOrder === null ) {
				$payload['sort_order'] = $cursorSortOrders;
			}
			$queryResult->setNextCursor( CursorEncoder::encode( $payload ) );
		}

		return $queryResult;
	}

	private function applyExtraWhereCondition( $connection, $qid ): void {
		if ( !isset( $this->querySegmentList[$qid] ) ) {
			return;
		}

		$qobj = $this->querySegmentList[$qid];

		// Filter elements that should never appear in a result set
		$extraWhereCondition = [
			'del'  => "$qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) . " AND $qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
			'redi' => "$qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWREDIIW )
		];

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
	 * @param int $rootId
	 *
	 * @return array
	 */
	private function getSQLOptions( Query $query, $rootId ): array {
		$result = [
			'LIMIT' => $query->getLimit() + 5,
			'OFFSET' => $query->getOffset()
		];

		if ( !$this->engineOptions->isFlagSet( 'smwgQSortFeatures', SMW_QSORT ) ) {
			return $result;
		}

		// Build ORDER BY options using discovered sorting fields.
		$qobj = $this->querySegmentList[$rootId];

		// Postgres masks off SMW_QSORT_RANDOM in getQueryResult() before
		// reaching this branch (SELECT DISTINCT + ORDER BY RANDOM() is invalid
		// in Postgres, see #835), so the RANDOM dispatch below only ever runs
		// for MySQL/MariaDB or SQLite.
		$randomToken = $this->store->getConnection( 'mw.db.queryengine' )->getType() === 'sqlite'
			? 'RANDOM()'
			: 'RAND()';

		foreach ( $this->sortKeys as $propkey => $order ) {

			if ( !is_string( $propkey ) ) {
				throw new RuntimeException( "Expected a string value as sortkey" );
			}

			if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $qobj->sortfields ) ) { // Field was successfully added.

				$list = $qobj->sortfields[$propkey];

				// Contains a compound list of sortfields without order?
				if ( strpos( $list, ',' ) !== false && strpos( $list, $order ) === false ) {
					$list = str_replace( ',', " $order,", $list );
				}

				$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . $list . " $order ";
			} elseif ( ( $order == 'RANDOM' ) && $this->engineOptions->isFlagSet( 'smwgQSortFeatures', SMW_QSORT_RANDOM ) ) {
				$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . " $randomToken ";
			}
		}

		return $result;
	}

	private function log( string $message, array $context = [] ): void {
		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

	/**
	 * Attach the keyset predicate for the query's cursor payload to the
	 * `SelectQueryBuilder` (the legacy single-query path). A no-op when
	 * the payload has no anchor: that is the first page in cursor mode,
	 * where the cursor only opts into a deterministic ordering and there
	 * is nothing to seek past yet.
	 *
	 * Thin wrapper over `buildKeysetPredicateString()`.
	 */
	private function applyKeysetPredicate(
		SelectQueryBuilder $queryBuilder,
		Query $query,
		QuerySegment $qobj,
		$connection,
		array $sortColumns,
		array $sortOrders
	): void {
		$predicate = $this->buildKeysetPredicateString(
			$query, $qobj, $connection, $sortColumns, $sortOrders
		);
		if ( $predicate !== '' ) {
			$queryBuilder->where( $predicate );
		}
	}

	/**
	 * Build the keyset predicate for the query's cursor payload as a raw
	 * SQL string. Used by `applyKeysetPredicate()` (legacy path, attaches
	 * to `SelectQueryBuilder`) and by `SubqueryQueryBuilder` (derived-table
	 * path, ANDs into the inner WHERE).
	 *
	 * Returns an empty string when the cursor has no anchor (the bootstrap
	 * `{"v":1}` payload), so the first cursor-mode page runs without a
	 * seek predicate.
	 *
	 * `$sortColumns` holds the SQL column expressions for the active sort
	 * fields, resolved by the caller from `$qobj->sortfields` (custom
	 * sort) or hardcoded to `[<alias>.smw_sort]` (default sort).
	 * `$sortOrders` is a parallel array of "ASC"/"DESC", one entry per
	 * column, so mixed `order=asc,desc` requests seek each level the
	 * right way. The smw_id tiebreak adopts the last level's direction so
	 * the tied-tuple walk chains off the final sort column.
	 *
	 * This method handles only the cursor-payload extraction; clause
	 * assembly is delegated to `KeysetPredicateBuilder`, shared with
	 * `KeysetPaginationTrait` so every SQLStore cursor path emits the
	 * same predicate form.
	 */
	private function buildKeysetPredicateString(
		Query $query,
		QuerySegment $qobj,
		$connection,
		array $sortColumns,
		array $sortOrders
	): string {
		$payload = $query->getCursorAfter();
		if ( !isset( $payload['sort'] ) || !isset( $payload['id'] ) ) {
			return '';
		}

		// Normalise payload `sort` to an array. Phase 3 spike + 3a +
		// 3b-i cursors carry scalar `sort`; 3b-ii/3b-iii multi-sort
		// cursors carry an array. The shape is validated by the caller
		// (`getInstanceQueryResult`) before reaching here, so by the
		// time this method runs, the count match is guaranteed.
		$sortValues = is_array( $payload['sort'] ) ? $payload['sort'] : [ $payload['sort'] ];

		$levels = [];
		foreach ( $sortColumns as $i => $column ) {
			$levels[] = [
				'column' => $column,
				'value' => $sortValues[$i],
				'order' => $sortOrders[$i],
			];
		}

		// Tiebreak direction is the last sort level's, matching the
		// `smw_id` term of the ORDER BY built in getInstanceQueryResult().
		return KeysetPredicateBuilder::build(
			$connection,
			$levels,
			"$qobj->alias.smw_id",
			(int)$payload['id'],
			$sortOrders[count( $sortOrders ) - 1]
		);
	}

}
