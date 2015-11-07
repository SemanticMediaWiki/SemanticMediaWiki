<?php

namespace SMW\SQLStore;

use SMW\Store;
use SMWQueryResult as QueryResult;
use SMWSQLStore3;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\HashBuilder;
use SMW\SemanticData;
use SMW\ApplicationFactory;
use SMW\EventHandler;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EmbeddedQueryDependencyLinksStore {

	/**
	 * @var Store
	 */
	private $store = null;

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
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
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

			wfDebugLog( 'smw', __METHOD__ . ' remove ' . implode( ',', $deleteIdList ) . "\n" );

			$this->connection->beginAtomicTransaction( __METHOD__ );

			$this->connection->delete(
				SMWSQLStore3::QUERY_LINKS_TABLE,
				array(
					's_id' => $deleteIdList
				),
				__METHOD__
			);

			$this->connection->endAtomicTransaction( __METHOD__ );
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
	 */
	public function buildParserCachePurgeJobParametersFrom( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		if ( !$this->isEnabled() ) {
			return array();
		}

		return array(
			'idlist' => $compositePropertyTableDiffIterator->getCombinedIdListOfChangedEntities()
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
	 * to resolve and update dependencies fetched fro an embedded query and its
	 * QueryResult object.
	 *
	 * @since 2.3
	 *
	 * @param EmbeddedQueryDependencyListResolver $embeddedQueryDependencyListResolver
	 */
	public function addDependencyList( EmbeddedQueryDependencyListResolver $embeddedQueryDependencyListResolver ) {

		if ( !$this->isEnabled() || $embeddedQueryDependencyListResolver->getSubject() === null ) {
			return null;
		}

		$subject = $embeddedQueryDependencyListResolver->getSubject();
		$hash = $embeddedQueryDependencyListResolver->getQueryId();

		$sid = $this->getIdForSubject(
			$subject,
			$hash
		);

		if ( $this->canSuppressUpdateOnSkewFactorFor( $sid, $subject ) ) {
			return wfDebugLog( 'smw', __METHOD__ . " suppressed (skewed time) for SID " . $sid . "\n" );
		}

		$dependencyList = $embeddedQueryDependencyListResolver->getQueryDependencySubjectList();

		if ( $dependencyList === array() ) {
			return null;
		}

		if ( $sid > 0 ) {
			return $this->updateDependencyList( $sid, $dependencyList );
		}

		// SID == 0 means the storage update/process has not been finalized
		// (new object hasn't been registered) hence an event is registered to
		// update the list after the update process has been completed

		// PHP 5.3 compatibility
		$embeddedQueryResultLinksUpdater = $this;

		EventHandler::getInstance()->addCallbackListener( 'deferred.embedded.query.dep.update', function() use ( $embeddedQueryResultLinksUpdater, $dependencyList, $subject, $hash ) {

			wfDebugLog( 'smw', __METHOD__ . ' deferred.embedded.query.dep.update for ' . $hash . "\n" );

			$embeddedQueryResultLinksUpdater->updateDependencyList(
				$embeddedQueryResultLinksUpdater->getIdForSubject( $subject, $hash ),
				$dependencyList
			);
		} );
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $sid
	 * @param array $dependencyList
	 */
	public function updateDependencyList( $sid, array $dependencyList ) {

		$this->connection->beginAtomicTransaction( __METHOD__ );

		// Before an insert, delete all entries that for the criteria which is
		// cheaper then doing an individual upsert or selectRow, this also ensures
		// that entries are self-corrected for dependencies matched
		$this->connection->delete(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array(
				's_id' => $sid
			),
			__METHOD__
		);

		if ( $sid == 0 ) {
			return $this->connection->endAtomicTransaction( __METHOD__ );
		}

		$inserts = array();

		foreach ( $dependencyList as $dependency ) {

			$oid = $this->getIdForSubject( $dependency );

			// If the ID_TABLE didn't contained an valid ID then we create one ourselves
			// to ensure that object entities are tracked from the start
			// This can happen when a query is added with object reference that have not
			// yet been referenced as annotation and therefore do not recognized as
			// value annotation
			if ( $oid < 1 && ( ( $oid = $this->tryToMakeIdForSubject( $dependency ) ) < 1 ) ) {
				continue;
			}

			$inserts[$sid . $oid] = array(
				's_id' => $sid,
				'o_id' => $oid
			);
		}

		if ( $inserts === array() ) {
			return $this->connection->endAtomicTransaction( __METHOD__ );
		}

		// MW's multi-array insert needs a numeric dimensional array but the key
		// was used with a hash to avoid duplicate entries hence the re-copy
		$inserts = array_values( $inserts );

		wfDebugLog( 'smw', __METHOD__ . ' insert for SID ' . $sid . "\n" );

		$this->connection->insert(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			$inserts,
			__METHOD__
		);

		$this->connection->endAtomicTransaction( __METHOD__ );
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

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject, $subobjectName
	 * @param string $subobjectName
	 */
	public function tryToMakeIdForSubject( DIWikiPage $subject, $subobjectName = '' ) {

		if ( $subject->getNamespace() !== NS_CATEGORY && $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return 0;
		}

		$id = $this->store->getObjectIds()->makeSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subobjectName,
			false
		);

		wfDebugLog( 'smw', __METHOD__ . " add new {$id} ID for " . $subject->getHash() . " \n" );

		return $id;
	}

	private function canSuppressUpdateOnSkewFactorFor( $sid, $subject ) {

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

		$skewedTouchedTimesamp = $subject->getTitle()->getTouched() + $this->skewFactorForDepedencyUpdateInSeconds;

		// Check whether the query has already been registered and only then
		// check for a possible divergent time
		return $row !== false && $skewedTouchedTimesamp > wfTimestamp( TS_MW );
	}

}
