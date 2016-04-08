<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\EventHandler;
use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\Store;
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
	public function setEnabledState( $isEnabled ) {
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
	public function pruneOutdatedTargetLinks( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		if ( !$this->isEnabled() ) {
			return null;
		}

		$tableName = $this->store->getPropertyTableInfoFetcher()->findTableIdForProperty(
			new DIProperty( '_ASK' )
		);

		$diff = $compositePropertyTableDiffIterator->getOrderedDiffByTable( $tableName );

		// Remove any dependency for queries that are no longer used
		if ( isset( $diff[$tableName]['delete'] ) ) {

			$deleteIdList = array();

			foreach ( $diff[$tableName]['delete'] as $delete ) {
				$deleteIdList[] = $delete['o_id'];
			}

			$this->dependencyLinksTableUpdater->deleteDependenciesFromList(  $deleteIdList );
		}

		// Dispatch any event registered earlier during the QueryResult processing
		// that didn't match a sid
		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'deferred.embedded.query.dep.update'
		);

		EventHandler::getInstance()->getEventDispatcher()->removeListener(
			'deferred.embedded.query.dep.update'
		);

		return true;
	}

	/**
	 * @since 2.3
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 * @param array $propertyDependencyExemptionlist
	 *
	 * @return array
	 */
	public function buildParserCachePurgeJobParametersFrom( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator, array $propertyDependencyExemptionlist ) {

		if ( !$this->isEnabled() ) {
			return array();
		}

		$mapCombinedIdListOfChangedEntities = array_flip( $compositePropertyTableDiffIterator->getCombinedIdListOfChangedEntities() );
		$mapPropertyDependencyExemptionlist = array_flip( $propertyDependencyExemptionlist );

		foreach ( $compositePropertyTableDiffIterator->getFixedPropertyRecords() as $table => $record ) {

			if ( !isset( $mapPropertyDependencyExemptionlist[$record['key']] ) ) {
				continue;
			}

			$this->removeBlacklistedPropertyReferencesFromParserCachePurgeJobChangeList(
				$compositePropertyTableDiffIterator,
				$mapCombinedIdListOfChangedEntities,
				$table,
				$record
			);
		}

		return array(
			'idlist' => array_keys( $mapCombinedIdListOfChangedEntities )
		);
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
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return array
	 */
	public function findPartialEmbeddedQueryTargetLinksHashListFor( array $idlist, $limit, $offset ) {

		if ( $idlist === array() || !$this->isEnabled() ) {
			return array();
		}

		$options = array(
			'LIMIT'     => $limit,
			'OFFSET'    => $offset,
			'GROUP BY'  => 's_id',
			'ORDER BY'  => 's_id',
			'DISTINCT'  => true
		);

		$rows = $this->connection->select(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array( 's_id' ),
			array(
				'o_id' => $idlist
			),
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

		$subject = $queryResultDependencyListResolver->getSubject();
		$hash = $queryResultDependencyListResolver->getQueryId();

		$sid = $this->getIdForSubject(
			$subject,
			$hash
		);

		if ( $this->canSuppressUpdateOnSkewFactorFor( $sid, $subject ) ) {
			return wfDebugLog( 'smw', __METHOD__ . " suppressed (skewed time) for SID " . $sid . "\n" );
		}

		$dependencyList = $queryResultDependencyListResolver->getDependencyList();

		if ( $dependencyList === array() ) {
			return null;
		}

		$dependencyLinksTableUpdater = $this->dependencyLinksTableUpdater;
		$dependencyLinksTableUpdater->addUpdateList( $sid, $dependencyList );

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $dependencyLinksTableUpdater ) {
			wfDebugLog( 'smw', 'DeferredCallableUpdate on QueryDependencyLinksStore' );
			$dependencyLinksTableUpdater->doUpdate();
		} );

		$deferredCallableUpdate->enabledDeferredUpdate( true );

		if ( $sid > 0 ) {
			return $deferredCallableUpdate->pushToDeferredUpdateList();
		}

		// SID == 0 means the storage update/process has not been finalized
		// (new object hasn't been registered) hence an event is registered to
		// update the list after the update process has been completed
		EventHandler::getInstance()->addCallbackListener( 'deferred.embedded.query.dep.update', function() use ( $dependencyLinksTableUpdater, $dependencyList, $deferredCallableUpdate, $subject, $hash ) {

			wfDebugLog( 'smw', __METHOD__ . ' deferred.embedded.query.dep.update for ' . $hash . "\n" );

			$dependencyLinksTableUpdater->addUpdateList(
				$dependencyLinksTableUpdater->getIdForSubject( $subject, $hash ),
				$dependencyList
			);

			$deferredCallableUpdate->pushToDeferredUpdateList();
		} );
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject, $subobjectName
	 * @param string $subobjectName
	 */
	public function getIdForSubject( DIWikiPage $subject, $subobjectName = '' ) {
		return $this->store->getObjectIds()->getSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subobjectName,
			false
		);
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

	private function removeBlacklistedPropertyReferencesFromParserCachePurgeJobChangeList( $compositePropertyTableDiffIterator, &$mapCombinedIdListOfChangedEntities, $table, $record ) {

		// Remove matched blacklisted property reference
		unset( $mapCombinedIdListOfChangedEntities[$record['p_id']] );

		// Try to find any referenced subject ID for the property
		$orderedDiffByTable = $compositePropertyTableDiffIterator->getOrderedDiffByTable( $table );

		if ( isset( $orderedDiffByTable[$table]['insert'] ) ) {
			foreach ( $orderedDiffByTable[$table]['insert'] as $insert ) {
				unset( $mapCombinedIdListOfChangedEntities[$insert['s_id']] );
			}
		}

		if ( isset( $orderedDiffByTable[$table]['delete'] ) ) {
			foreach ( $orderedDiffByTable[$table]['delete'] as $delete ) {
				unset( $mapCombinedIdListOfChangedEntities[$delete['s_id']] );
			}
		}
	}

}
