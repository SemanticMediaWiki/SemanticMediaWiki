<?php

namespace SMW\SQLStore\QueryDependency;

use Psr\Log\LoggerAwareTrait;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\Timer;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryDependencyLinksStore {

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DependencyLinksTableUpdater
	 */
	private $dependencyLinksTableUpdater;

	/**
	 * @var QueryResultDependencyListResolver
	 */
	private $queryResultDependencyListResolver;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * Time factor to be used to determine whether an update should actually occur
	 * or not. The comparison is made against the page_touched timestamp to a
	 * previous update to avoid unnecessary DB transactions if it takes place
	 * within the computed time frame.
	 *
	 * @var integer
	 */
	private $skewFactorForDependencyUpdateInSeconds = 10;

	/**
	 * @since 2.3
	 *
	 * @param QueryResultDependencyListResolver $queryResultDependencyListResolver
	 * @param DependencyLinksTableUpdater $dependencyLinksTableUpdater
	 */
	public function __construct( QueryResultDependencyListResolver $queryResultDependencyListResolver, DependencyLinksTableUpdater $dependencyLinksTableUpdater ) {
		$this->queryResultDependencyListResolver = $queryResultDependencyListResolver;
		$this->dependencyLinksTableUpdater = $dependencyLinksTableUpdater;
		$this->store = $this->dependencyLinksTableUpdater->getStore();
		$this->namespaceExaminer = ApplicationFactory::getInstance()->getNamespaceExaminer();
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 2.3
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->isEnabled;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $isEnabled
	 */
	public function setEnabled( $isEnabled ) {
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
	public function pruneOutdatedTargetLinks( ChangeOp $changeOp ) {

		if ( !$this->isEnabled() ) {
			return null;
		}

		Timer::start( __METHOD__ );
		$hash = null;

		$tableName = $this->store->getPropertyTableInfoFetcher()->findTableIdForProperty(
			new DIProperty( '_ASK' )
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

		if ( ( $subject = $changeOp->getSubject() ) !== null ) {
			$hash = $subject->getHash();
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'developer',
			'origin' =>  $hash,
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
	 * @param DIWikiPage $subject
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return array
	 */
	public function findEmbeddedQueryIdListBySubject( DIWikiPage $subject, RequestOptions $requestOptions = null ) {

		$embeddedQueryIdList = [];

		$dataItems = $this->store->getPropertyValues(
			$subject,
			new DIProperty( '_ASK' ),
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
	 * @param DIWikiPage $subject
	 * @param RequestOptions $requestOptions
	 *
	 * @return array
	 */
	public function findDependencyTargetLinksForSubject( DIWikiPage $subject, RequestOptions $requestOptions ) {
		return $this->findDependencyTargetLinks(
			[ $this->dependencyLinksTableUpdater->getId( $subject ) ],
			$requestOptions
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param integer|array $id
	 *
	 * @return integer
	 */
	public function countDependencies( $id ) {

		$count = 0;
		$ids = !is_array( $id ) ? (array)$id : $id;

		if ( $ids === [] || !$this->isEnabled() ) {
			return $count;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			SQLStore::QUERY_LINKS_TABLE,
			[
				'COUNT(s_id) AS count'
			],
			[
				'o_id' => $ids
			],
			__METHOD__
		);

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

		$options = [
			'LIMIT'     => $requestOptions->getLimit(),
			'OFFSET'    => $requestOptions->getOffset(),
		] + [ 'DISTINCT' ];

		$conditions = [
			'o_id' => $idlist
		];

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
			$conditions[] = $extraCondition;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->select(
			SQLStore::QUERY_LINKS_TABLE,
			[ 's_id' ],
			$conditions,
			__METHOD__,
			$options
		);

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
			'smw_iw !=' . $connection->addQuotes( SMW_SQL3_SMWREDIIW ) . ' AND '.
			'smw_iw !=' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW )
		);

		return $this->store->getObjectIds()->getDataItemPoolHashListFor(
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

		// Executed as DeferredTransactionalUpdate
		$callback = function() use( $queryResult, $subject, $sid, $hash ) {
			$this->doUpdate( $queryResult, $subject, $sid, $hash );
		};

		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate(
			$callback
		);

		$origin = $subject->getHash();

		$deferredTransactionalUpdate->setOrigin( [ __METHOD__, $origin ] );
		$deferredTransactionalUpdate->markAsPending( $this->isCommandLineMode );
		$deferredTransactionalUpdate->setFingerprint( $hash );

		$deferredTransactionalUpdate->enabledDeferredUpdate( true );
		$deferredTransactionalUpdate->waitOnTransactionIdle();

		$deferredTransactionalUpdate->pushUpdate();

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

		// SID < 0 means the storage update/process has not been finalized
		// (new object hasn't been registered)
		if ( $sid < 1 || ( $sid = $this->dependencyLinksTableUpdater->getId( $subject, $hash ) ) < 1 ) {
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

		$this->dependencyLinksTableUpdater->doUpdate();
	}

	private function canUpdateDependencies( $queryResult ) {

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

		if ( $query === null || ( $subject = $query->getContextPage() ) === null ) {
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

	private function isRegistered( $sid, $subject ) {

		static $suppressUpdateCache = [];
		$hash = $subject->getHash();

		if ( $sid < 1 ) {
			return false;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			SQLStore::QUERY_LINKS_TABLE,
			[
				's_id'
			],
			[ 's_id' => $sid ],
			__METHOD__
		);

		$title = $subject->getTitle();

		// https://phabricator.wikimedia.org/T167943
		if ( !isset( $suppressUpdateCache[$hash] ) && $title !== null ) {
			$suppressUpdateCache[$hash] = wfTimestamp( TS_MW, $title->getTouched() ) + $this->skewFactorForDependencyUpdateInSeconds;
		}

		// Check whether the query has already been registered and only then
		// check for a possible divergent time
		return $row !== false && $suppressUpdateCache[$hash] > wfTimestamp( TS_MW );
	}

}
