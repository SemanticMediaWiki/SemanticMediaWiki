<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\Store;

use SMW\SQLStore\SQLStore;
use SMW\DIWikiPage;

use DeferrableUpdate;
use DeferredUpdates;

/**
 * Implements MW's DeferrableUpdate to avoid complains from the`TransactionProfiler`
 * which started to appear with 1.26 due to our updates (insert/delete) on the
 * QUERY_LINKS_TABLE taken place on a page view event for reparsed #ask results.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DeferredDependencyLinksUpdater implements DeferrableUpdate {

	/**
	 * @var array
	 */
	private static $deferrableUpdates = array();

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
	private $disabledDeferredUpdate = false;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @since 2.4
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @note Only used during unit testing to disable DeferredUpdates
	 *
	 * @since 2.4
	 */
	public function disableDeferredUpdate() {
		$this->disabledDeferredUpdate = true;
	}

	/**
	 * @since 2.4
	 */
	public function clear() {
		self::$deferrableUpdates = array();
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $sid
	 * @param array $dependencyList
	 */
	public function addToDeferredUpdateList( $sid, array $dependencyList ) {

		if ( $this->disabledDeferredUpdate ) {
			self::$deferrableUpdates[$sid] = $dependencyList;
		}

		// An updater for the ID is unkown, register it!
		if ( !isset( self::$deferrableUpdates[$sid] ) ) {
			self::$deferrableUpdates[$sid] = $dependencyList;
			wfDebugLog( 'smw', __METHOD__ . " for " . $sid  );
			return DeferredUpdates::addUpdate( $this );
		}

		self::$deferrableUpdates[$sid] = array_merge( self::$deferrableUpdates[$sid], $dependencyList );
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 2.4
	 */
	public function doUpdate() {
		foreach ( self::$deferrableUpdates as $sid => $dependencyList ) {

			if ( $dependencyList === array() ) {
				continue;
			}

			wfDebugLog( 'smw', __METHOD__ . " for " . $sid  );

			$this->updateDependencyList( $sid, $dependencyList );
			self::$deferrableUpdates[$sid] = array();
		}
	}

	/**
	 * @since 2.4
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
			SQLStore::QUERY_LINKS_TABLE,
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

			if ( !$dependency instanceof DIWikiPage ) {
				continue;
			}

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
			SQLStore::QUERY_LINKS_TABLE,
			$inserts,
			__METHOD__
		);

		$this->connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 2.4
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
	 * @since 2.4
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

}
