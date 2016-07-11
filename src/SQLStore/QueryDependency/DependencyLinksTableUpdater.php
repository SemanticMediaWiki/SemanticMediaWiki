<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DependencyLinksTableUpdater {

	/**
	 * @var array
	 */
	private static $updateList = array();

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var Database
	 */
	private $connection = null;

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
	 * @since 2.4
	 */
	public function clear() {
		self::$updateList = array();
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $sid
	 * @param array|null $dependencyList
	 */
	public function addToUpdateList( $sid, array $dependencyList = null ) {

		if ( $sid == 0 || $dependencyList === null || $dependencyList === array() ) {
			return null;
		}

		if ( !isset( self::$updateList[$sid] ) ) {
			return self::$updateList[$sid] = $dependencyList;
		}

		self::$updateList[$sid] = array_merge( self::$updateList[$sid], $dependencyList );
	}

	/**
	 * @since 2.4
	 */
	public function doUpdate() {
		foreach ( self::$updateList as $sid => $dependencyList ) {

			if ( $dependencyList === array() ) {
				continue;
			}

			$this->updateDependencyList( $sid, $dependencyList );
			self::$updateList[$sid] = array();
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param array $dependencyList
	 */
	public function deleteDependenciesFromList( array $deleteIdList ) {

		wfDebugLog( 'smw', __METHOD__ . ' ' . implode( ' ,', $deleteIdList ) . "\n" );

		$this->connection->beginAtomicTransaction( __METHOD__ );

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			array(
				's_id' => $deleteIdList
			),
			__METHOD__
		);

		$this->connection->endAtomicTransaction( __METHOD__ );
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

		$id = $this->store->getObjectIds()->getIDFor(
			$subject
		);

		return $id;
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
