<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\EventHandler;
use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\Store;
use SMW\RequestOptions;
use SMWSQLStore3;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryDependencyLinksStore {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var DependencyLinksTableUpdater
	 */
	private $dependencyLinksTableUpdater = null;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * Time factor to be used to determine whether an update should actually occur
	 * or not. The comparison is made against the page_touched timestamp (updated
	 * by the ParserCachePurgeJob) to a previous update to avoid unnecessary DB
	 * transactions if it takes place within the computed time frame.
	 *
	 * @var integer
	 */
	private $skewFactorForDepedencyUpdateInSeconds = 10;

	/**
	 * @since 2.3
	 *
	 * @param DependencyLinksTableUpdater $dependencyLinksTableUpdater
	 */
	public function __construct( DependencyLinksTableUpdater $dependencyLinksTableUpdater ) {
		$this->dependencyLinksTableUpdater = $dependencyLinksTableUpdater;
		$this->store = $this->dependencyLinksTableUpdater->getStore();
		$this->connection = $this->store->getConnection( 'mw.db' );
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
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 */
	public function pruneOutdatedTargetLinks( DIWikiPage $subject, CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		if ( !$this->isEnabled() ) {
			return null;
		}

		$start = microtime( true );
		$tableName = $this->store->getPropertyTableInfoFetcher()->findTableIdForProperty(
			new DIProperty( '_ASK' )
		);

		$tableChangeOps = $compositePropertyTableDiffIterator->getTableChangeOps( $tableName );

		// Remove any dependency for queries that are no longer used
		foreach ( $tableChangeOps as $tableChangeOp ) {

			if ( !$tableChangeOp->hasChangeOp( 'delete' ) ) {
				continue;
			}

			$deleteIdList = array();

			foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $fieldChangeOp ) {
				$deleteIdList[] = $fieldChangeOp->get( 'o_id' );
			}

			$this->dependencyLinksTableUpdater->deleteDependenciesFromList( $deleteIdList );
		}

		wfDebugLog( 'smw', __METHOD__ . ' finished on ' . $subject->getHash() . ' with procTime (sec): ' . round( ( microtime( true ) - $start ) ,7 ) );

		return true;
	}

	/**
	 * Build the ParserCachePurgeJob parameters on filtered entities to minimize
	 * necessary update work.
	 *
	 * @since 2.3
	 *
	 * @param EntityIdListRelevanceDetectionFilter $entityIdListRelevanceDetectionFilter
	 *
	 * @return array
	 */
	public function buildParserCachePurgeJobParametersFrom( EntityIdListRelevanceDetectionFilter $entityIdListRelevanceDetectionFilter ) {

		if ( !$this->isEnabled() ) {
			return array();
		}

		return array(
			'idlist' => $entityIdListRelevanceDetectionFilter->getFilteredIdList()
		);
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

		$embeddedQueryIdList = array();

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
	public function findEmbeddedQueryTargetLinksHashListFor( array $idlist, RequestOptions $requestOptions ) {

		if ( $idlist === array() || !$this->isEnabled() ) {
			return array();
		}

		$options = array(
			'LIMIT'     => $requestOptions->getLimit(),
			'OFFSET'    => $requestOptions->getOffset(),
			'GROUP BY'  => 's_id',
			'ORDER BY'  => 's_id',
			'DISTINCT'  => true
		);

		$conditions = array(
			'o_id' => $idlist
		);

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
			$conditions += $extraCondition;
		}

		$rows = $this->connection->select(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array( 's_id' ),
			$conditions,
			__METHOD__,
			$options
		);

		$targetLinksIdList = array();

		foreach ( $rows as $row ) {
			$targetLinksIdList[] = $row->s_id;
		}

		if ( $targetLinksIdList === array() ) {
			return array();
		}

		return $this->store->getObjectIds()->getDataItemPoolHashListFor(
			$targetLinksIdList
		);
	}

	/**
	 * This method is called from the `SMW::Store::AfterQueryResultLookupComplete` hook
	 * to resolve and update dependencies fetched from an embedded query and its
	 * QueryResult object.
	 *
	 * @since 2.3
	 *
	 * @param QueryResultDependencyListResolver $queryResultDependencyListResolver
	 */
	public function doUpdateDependenciesBy( QueryResultDependencyListResolver $queryResultDependencyListResolver ) {

		if ( !$this->isEnabled() || $queryResultDependencyListResolver->getSubject() === null ) {
			return null;
		}

		$start = microtime( true );

		$subject = $queryResultDependencyListResolver->getSubject();
		$hash = $queryResultDependencyListResolver->getQueryId();

		$sid = $this->dependencyLinksTableUpdater->getId(
			$subject,
			$hash
		);

		if ( $this->canSuppressUpdateOnSkewFactorFor( $sid, $subject ) ) {
			return wfDebugLog( 'smw', __METHOD__ . " suppressed (skewed time) for SID " . $sid . "\n" );
		}

		$dependencyLinksTableUpdater = $this->dependencyLinksTableUpdater;

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $subject, $sid, $hash, $dependencyLinksTableUpdater, $queryResultDependencyListResolver ) {

			$dependencyList = $queryResultDependencyListResolver->getDependencyList();

			// Add extra dependencies which we only get "late" after the QueryResult
			// object as been resolved by the ResultPrinter, this is done to
			// avoid having to process the QueryResult recursively on its own
			// (which would carry a performance penalty)
			$dependencyListByLateRetrieval = $queryResultDependencyListResolver->getDependencyListByLateRetrieval();

			if ( $dependencyList === array() && $dependencyListByLateRetrieval === array() ) {
				return wfDebugLog( 'smw', 'No dependency list available ' . $hash );
			}

			// SID < 0 means the storage update/process has not been finalized
			// (new object hasn't been registered)
			if ( $sid < 1 || ( $sid = $dependencyLinksTableUpdater->getId( $subject, $hash ) ) < 1 ) {
				$sid = $dependencyLinksTableUpdater->createId( $subject, $hash );
			}

			$dependencyLinksTableUpdater->addToUpdateList(
				$sid,
				$dependencyList
			);

			$dependencyLinksTableUpdater->addToUpdateList(
				$sid,
				$dependencyListByLateRetrieval
			);

			$dependencyLinksTableUpdater->doUpdate();
		} );

		$deferredCallableUpdate->setOrigin( __METHOD__ );

		// https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
		// Indicates whether MW is running in command-line mode.
		$deferredCallableUpdate->markAsPending( $GLOBALS['wgCommandLineMode'] );
		$deferredCallableUpdate->enabledDeferredUpdate( true );
		$deferredCallableUpdate->pushToUpdateQueue();

		wfDebugLog( 'smw', __METHOD__ . ' procTime (sec): ' . round( ( microtime( true ) - $start ), 7 ) );
	}

	private function canSuppressUpdateOnSkewFactorFor( $sid, $subject ) {

		static $suppressUpdateCache = array();
		$hash = $subject->getHash();
		if ( $sid < 1 ) {
			return false;
		}

		$row = $this->connection->selectRow(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array(
				's_id'
			),
			array( 's_id' => $sid ),
			__METHOD__
		);

		if ( !isset( $suppressUpdateCache[$hash] ) ) {
			$suppressUpdateCache[$hash] = $subject->getTitle()->getTouched() + $this->skewFactorForDepedencyUpdateInSeconds;
		}

		// Check whether the query has already been registered and only then
		// check for a possible divergent time
		return $row !== false && $suppressUpdateCache[$hash] > wfTimestamp( TS_MW );
	}

}
