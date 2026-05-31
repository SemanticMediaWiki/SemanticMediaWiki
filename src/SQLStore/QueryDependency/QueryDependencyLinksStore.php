<?php

namespace SMW\SQLStore\QueryDependency;

use MediaWiki\Deferred\DeferredUpdates;
use Psr\Log\LoggerAwareTrait;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\NamespaceExaminer;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\RequestOptions;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\Timer;
use Throwable;

/**
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class QueryDependencyLinksStore {

	use LoggerAwareTrait;

	private Store $store;

	private bool $isEnabled = true;

	/**
	 * Per-request buffer of dependency-update work registered by
	 * `updateDependencies()`. The first registration in a request queues a
	 * single deferred drain (see `runPendingDependencyUpdates()`) that
	 * processes the buffer and flushes the accumulated `$updateList` in
	 * `DependencyLinksTableUpdater` once. Buffering lets two `#ask` queries
	 * that share a query-id hash (same conditions, different printouts) both
	 * contribute their printout-derived dependencies to `smw_query_links`
	 * rather than have the second `DELETE`-then-`INSERT` overwrite the first.
	 *
	 * Caveat: in CLI/maintenance scripts where `tryOpportunisticExecute()`
	 * may fire the drain synchronously between successive registrations
	 * (no active DB transaction), the buffer can be drained before the
	 * second registration appends. Within `runJobs.php` and inside any
	 * `Store::updateData` section transaction this is moot; bare maintenance
	 * loops calling the resolver outside a transaction may still observe
	 * per-call flushes.
	 */
	private static array $pendingDependencyUpdates = [];

	/**
	 * Set to true once a drain is queued for the current request and
	 * cleared from the drain's `finally` so the next registration after a
	 * successful (or thrown) drain can queue a fresh one. Guards against
	 * scheduling N redundant drains in a single request.
	 */
	private static bool $flushScheduled = false;

	/**
	 * Time factor to be used to determine whether an update should actually occur
	 * or not. The comparison is made against the page_touched timestamp to a
	 * previous update to avoid unnecessary DB transactions if it takes place
	 * within the computed time frame.
	 */
	private int $skewFactorForDependencyUpdateInSeconds = 10;

	/**
	 * @since 2.3
	 */
	public function __construct(
		private QueryResultDependencyListResolver $queryResultDependencyListResolver,
		private DependencyLinksTableUpdater $dependencyLinksTableUpdater,
		private readonly NamespaceExaminer $namespaceExaminer,
	) {
		$this->store = $this->dependencyLinksTableUpdater->getStore();
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ): void {
		$this->store = $store;
	}

	/**
	 * @since 2.3
	 *
	 * @return bool
	 */
	public function isEnabled(): bool {
		return $this->isEnabled;
	}

	/**
	 * @since 2.3
	 *
	 * @param bool $isEnabled
	 */
	public function setEnabled( $isEnabled ): void {
		$this->isEnabled = (bool)$isEnabled;
	}

	/**
	 * This method is called from the `SMW::SQLStore::AfterDataUpdateComplete` hook and
	 * removes outdated query ID's from the table if the diff contains a `delete`
	 * entry for the _ask table.
	 *
	 * @since 2.3
	 *
	 * @param ChangeOp $changeOp
	 */
	public function pruneOutdatedTargetLinks( ChangeOp $changeOp ): ?bool {
		if ( !$this->isEnabled() ) {
			return null;
		}

		Timer::start( __METHOD__ );
		$hash = null;

		$tableName = $this->store->getPropertyTableInfoFetcher()->findTableIdForProperty(
			new Property( '_ASK' )
		);

		$tableChangeOps = $changeOp->getTableChangeOps( $tableName );

		// Remove any dependency for queries that are no longer used
		foreach ( $tableChangeOps as $tableChangeOp ) {

			if ( !$tableChangeOp->hasChangeOp( 'delete' ) ) {
				continue;
			}

			$deleteIdList = [];

			foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $fieldChangeOp ) {
				$deleteIdList[] = $fieldChangeOp->get( 'o_id' );
			}

			$this->dependencyLinksTableUpdater->deleteDependenciesFromList( $deleteIdList );
		}

		$subject = $changeOp->getSubject();
		if ( $subject !== null ) {
			$hash = $subject->getHash();
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'developer',
			'origin' => $hash,
			'procTime' => Timer::getElapsedTime( __METHOD__, 7 )
		];

		$this->logger->info(
			'[QueryDependency] Prune links completed: {origin} (procTime in sec: {procTime})',
			$context
		);

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $subject
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return array
	 */
	public function findEmbeddedQueryIdListBySubject( WikiPage $subject, ?RequestOptions $requestOptions = null ): array {
		$embeddedQueryIdList = [];

		$dataItems = $this->store->getPropertyValues(
			$subject,
			new Property( '_ASK' ),
			$requestOptions
		);

		foreach ( $dataItems as $dataItem ) {
			$embeddedQueryIdList[$dataItem->getHash()] = $this->dependencyLinksTableUpdater->getId( $dataItem );
		}

		return $embeddedQueryIdList;
	}

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $subject
	 * @param RequestOptions $requestOptions
	 *
	 * @return array
	 */
	public function findDependencyTargetLinksForSubject( WikiPage $subject, RequestOptions $requestOptions ) {
		return $this->findDependencyTargetLinks(
			[ $this->dependencyLinksTableUpdater->getId( $subject ) ],
			$requestOptions
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param int|array $id
	 *
	 * @return int
	 */
	public function countDependencies( $id ): int {
		$count = 0;
		$ids = !is_array( $id ) ? (array)$id : $id;

		if ( $ids === [] || !$this->isEnabled() ) {
			return $count;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->newSelectQueryBuilder()
			->select( [ 'COUNT(s_id) AS count' ] )
			->from( SQLStore::QUERY_LINKS_TABLE )
			->where( [ 'o_id' => $ids ] )
			->caller( __METHOD__ )
			->fetchRow();

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	/**
	 * Finds a partial list (given limit and offset) of registered subjects that
	 * that represent a dependency on something like a subject in a query list,
	 * a property, or a printrequest.
	 *
	 * `s_id` contains the subject id that links to the query that fulfills one
	 * of the conditions cited above.
	 *
	 * Prefetched Ids are turned into a hash list that can later be split into
	 * chunks to work either in online or batch mode without creating a huge memory
	 * foothold.
	 *
	 * @note Select a list is crucial for performance as any selectRow would /
	 * single Id select would strain the system on large list connected to a
	 * query
	 *
	 * @since 2.3
	 *
	 * @param array $idlist
	 * @param RequestOptions $requestOptions
	 *
	 * @return array
	 */
	public function findDependencyTargetLinks( array $idlist, RequestOptions $requestOptions ) {
		if ( $idlist === [] || !$this->isEnabled() ) {
			return [];
		}

		$conditions = [
			'o_id' => $idlist
		];

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
			$conditions[] = $extraCondition;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->newSelectQueryBuilder()
			->select( [ 's_id' ] )
			->distinct()
			->from( SQLStore::QUERY_LINKS_TABLE )
			->where( $conditions )
			->limit( $requestOptions->getLimit() )
			->offset( $requestOptions->getOffset() )
			->caller( __METHOD__ )
			->fetchResultSet();

		$targetLinksIdList = [];

		foreach ( $rows as $row ) {
			$targetLinksIdList[] = $row->s_id;
		}

		if ( $targetLinksIdList === [] ) {
			return [];
		}

		// Return the expected count of targets
		$requestOptions->setOption( 'links.count', count( $targetLinksIdList ) );

		$poolRequestOptions = new RequestOptions();

		$poolRequestOptions->addExtraCondition(
			'smw_iw !=' . $connection->addQuotes( SMW_SQL3_SMWREDIIW ) . ' AND ' .
			'smw_iw !=' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW )
		);

		return $this->store->getObjectIds()->getDataItemsFromList(
			$targetLinksIdList,
			$poolRequestOptions
		);
	}

	/**
	 * This method is called from the `SMW::Store::AfterQueryResultLookupComplete` hook
	 * to resolve and update dependencies fetched from an embedded query and its
	 * QueryResult object.
	 *
	 * @since 2.3
	 *
	 * @param QueryResult|string $queryResult
	 */
	public function updateDependencies( $queryResult ) {
		if ( !$this->canUpdateDependencies( $queryResult ) ) {
			return null;
		}

		Timer::start( __METHOD__ );

		$subject = $queryResult->getQuery()->getContextPage();
		$hash = $queryResult->getQuery()->getQueryId();

		if ( $subject === null ) {
			return null;
		}

		$sid = $this->dependencyLinksTableUpdater->getId(
			$subject,
			$hash
		);

		$context = [
			'method' => __METHOD__,
			'role' => 'developer',
			'id' => $sid
		];

		if ( $this->isRegistered( $sid, $subject ) ) {
			return $this->logger->info(
				'[QueryDependency] Skipping update: {id} (already registered, no dependency update)',
				$context
			);
		}

		$origin = $subject->getHash();

		// Buffer this work and schedule a batch drain. Same-`$sid` registrations
		// from sibling `#ask` queries (same conditions, different printouts ->
		// same query-id hash -> same `$sid`) all land in the buffer; the drain
		// calls `doUpdate()` for each, then flushes the accumulated
		// `DependencyLinksTableUpdater::$updateList` once. A per-callback flush
		// would have the second callback's `DELETE`-then-`INSERT` overwrite
		// the first callback's deps.
		//
		// `$flushScheduled` keeps a single drain queued per request; the
		// drain's `finally` resets it so a thrown drain does not deadlock
		// subsequent registrations (self-healing on leaked schedules).
		self::$pendingDependencyUpdates[] = [ $queryResult, $subject, $sid, $hash ];

		if ( !self::$flushScheduled ) {
			self::$flushScheduled = true;
			DeferredUpdates::addCallableUpdate( function (): void {
				try {
					$this->runPendingDependencyUpdates();
				} finally {
					self::$flushScheduled = false;
				}
			} );
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'developer',
			'origin' => $origin,
			'procTime' => Timer::getElapsedTime( __METHOD__, 7 )
		];

		$this->logger->info(
			'[QueryDependency] Update dependencies registered: {origin} (procTime in sec: {procTime})',
			$context
		);

		return true;
	}

	/**
	 * Drain the per-request `$pendingDependencyUpdates` buffer.
	 *
	 * Ordering is drain-then-clear-then-iterate so a recursive
	 * `updateDependencies()` triggered from inside `doUpdate()` (e.g. via
	 * lazy resolution of `getDependencyListFrom`) sees an empty buffer and
	 * queues its own fresh drain rather than re-entering this one. After
	 * every buffered item's deps are merged into
	 * `DependencyLinksTableUpdater::$updateList`, a single flush writes the
	 * union to `smw_query_links`. Per-item exceptions are caught and logged
	 * so one failing query does not silently drop the rest of the batch.
	 */
	private function runPendingDependencyUpdates(): void {
		if ( self::$pendingDependencyUpdates === [] ) {
			return;
		}

		$batch = self::$pendingDependencyUpdates;
		self::$pendingDependencyUpdates = [];

		foreach ( $batch as [ $queryResult, $subject, $sid, $hash ] ) {
			try {
				$this->doUpdate( $queryResult, $subject, $sid, $hash );
			} catch ( Throwable $e ) {
				$this->logger->error(
					'[QueryDependency] doUpdate failed for {origin}: {message}',
					[
						'method' => __METHOD__,
						'role' => 'production',
						'origin' => $hash,
						'message' => $e->getMessage(),
					]
				);
			}
		}

		$this->dependencyLinksTableUpdater->doUpdate();
	}

	private function doUpdate( $queryResult, $subject, $sid, $hash ) {
		$dependencyList = $this->queryResultDependencyListResolver->getDependencyListFrom(
			$queryResult
		);

		// Add extra dependencies which we only get "late" after the QueryResult
		// object as been resolved by the ResultPrinter, this is done to
		// avoid having to process the QueryResult recursively on its own
		// (which would carry a performance penalty)
		$dependencyListByLateRetrieval = $this->queryResultDependencyListResolver->getDependencyListByLateRetrievalFrom(
			$queryResult
		);

		$context = [
			'method' => __METHOD__,
			'role' => 'developer',
			'origin' => $hash
		];

		if ( $dependencyList === [] && $dependencyListByLateRetrieval === [] ) {
			return $this->logger->info(
				'[QueryDependency] no update: {origin} (no dependency list available)',
				$context
			);
		}

		if ( !$subject instanceof WikiPage ) {
			return;
		}

		// SID < 0 means the storage update/process has not been finalized
		// (new object hasn't been registered)
		if ( $sid >= 1 ) {
			$sid = $this->dependencyLinksTableUpdater->getId( $subject, $hash );
		}

		if ( $sid < 1 ) {
			$sid = $this->dependencyLinksTableUpdater->createId( $subject, $hash );
		}

		$this->dependencyLinksTableUpdater->addToUpdateList(
			$sid,
			$dependencyList
		);

		$this->dependencyLinksTableUpdater->addToUpdateList(
			$sid,
			$dependencyListByLateRetrieval
		);
	}

	private function canUpdateDependencies( $queryResult ): bool {
		if ( !$this->isEnabled() || !$queryResult instanceof QueryResult ) {
			return false;
		}

		$query = $queryResult->getQuery();

		$actions = [
			// #2484 Avoid any update activities during a stashedit API access
			'stashedit',

			// Avoid update on `submit` during a preview
			'submit',

			// Avoid update on `parse` during a wikieditor preview
			'parse'
		];

		if ( in_array( $query->getOption( 'request.action' ), $actions ) ) {
			return false;
		}

		$subject = $query->getContextPage();
		if ( $subject === null ) {
			return false;
		}

		// Make sure that when a query is embedded in a not supported NS to bail
		// out
		if ( !$this->namespaceExaminer->isSemanticEnabled( $subject->getNamespace() ) ) {
			return false;
		}

		if ( $subject->getTitle() === null || !$subject->getTitle()->exists() ) {
			return false;
		}

		return $query->getLimit() > 0 && $query->getOption( Query::NO_DEPENDENCY_TRACE ) !== true;
	}

	private function isRegistered( $sid, $subject ): bool {
		static $suppressUpdateCache = [];
		$hash = $subject->getHash();

		if ( $sid < 1 ) {
			return false;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->newSelectQueryBuilder()
			->select( [ 's_id' ] )
			->from( SQLStore::QUERY_LINKS_TABLE )
			->where( [ 's_id' => $sid ] )
			->caller( __METHOD__ )
			->fetchRow();

		$title = $subject->getTitle();

		// https://phabricator.wikimedia.org/T167943
		if ( !isset( $suppressUpdateCache[$hash] ) && $title !== null ) {
			$suppressUpdateCache[$hash] = (string)( (int)wfTimestamp( TS_MW, $title->getTouched() ) + $this->skewFactorForDependencyUpdateInSeconds );
		}

		// Check whether the query has already been registered and only then
		// check for a possible divergent time
		return $row !== false && $suppressUpdateCache[$hash] > wfTimestamp( TS_MW );
	}

}
