<?php

namespace SMW\SQLStore\QueryDependency;

use Psr\Log\LoggerAwareTrait;
use SMW\DataItems\WikiPage;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DependencyLinksTableUpdater {

	use LoggerAwareTrait;

	private static array $updateList = [];

	/**
	 * @since 2.4
	 */
	public function __construct( private Store $store ) {
	}

	/**
	 * @since 2.4
	 *
	 * @return Store
	 */
	public function getStore(): Store {
		return $this->store;
	}

	/**
	 * @since 2.4
	 */
	public function clear(): void {
		self::$updateList = [];
	}

	/**
	 * @since 2.4
	 *
	 * @param int $sid
	 * @param array|null $dependencyList
	 */
	public function addToUpdateList( $sid, ?array $dependencyList = null ) {
		if ( $sid == 0 || $dependencyList === null || $dependencyList === [] ) {
			return null;
		}

		if ( !isset( self::$updateList[$sid] ) ) {
			self::$updateList[$sid] = $dependencyList;
			return self::$updateList[$sid];
		}

		self::$updateList[$sid] = array_merge( self::$updateList[$sid], $dependencyList );
	}

	/**
	 * @since 2.4
	 */
	public function doUpdate(): void {
		foreach ( self::$updateList as $sid => $dependencyList ) {

			if ( $dependencyList === [] ) {
				continue;
			}

			$this->updateDependencyList( $sid, $dependencyList );
			self::$updateList[$sid] = [];
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param array $deleteIdList
	 */
	public function deleteDependenciesFromList( array $deleteIdList ): void {
		$this->logger->info(
			[ 'QueryDependency', 'Delete dependencies: {list}' ],
			[ 'method' => __METHOD__, 'role' => 'developer', 'list' => json_encode( $deleteIdList ) ]
		);

		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			[
				's_id' => $deleteIdList
			],
			__METHOD__
		);

		$connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 2.4
	 *
	 * @param int $sid
	 * @param array $dependencyList
	 *
	 * @return void
	 */
	private function updateDependencyList( int|string $sid, array $dependencyList ) {
		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_touched' => $connection->timestamp()
			],
			[
				'smw_id' => $sid
			],
			__METHOD__
		);

		// Before an insert, delete all entries that for the criteria which is
		// cheaper then doing an individual upsert or selectRow, this also ensures
		// that entries are self-corrected for dependencies matched
		$connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			[
				's_id' => $sid
			],
			__METHOD__
		);

		if ( $sid == 0 ) {
			$connection->endAtomicTransaction( __METHOD__ );
			return;
		}

		$inserts = [];

		foreach ( $dependencyList as $dependency ) {

			if ( !$dependency instanceof WikiPage ) {
				continue;
			}

			$oid = $this->getId( $dependency );

			// If the ID_TABLE didn't contained an valid ID then we create one ourselves
			// to ensure that object entities are tracked from the start
			// This can happen when a query is added with object reference that have not
			// yet been referenced as annotation and therefore do not recognized as
			// value annotation
			if ( $oid < 1 && ( ( $oid = $this->createId( $dependency ) ) < 1 ) ) {
				continue;
			}

			$inserts[$sid . $oid] = [
				's_id' => $sid,
				'o_id' => $oid
			];
		}

		if ( $inserts === [] ) {
			$connection->endAtomicTransaction( __METHOD__ );
			return;
		}

		// MW's multi-array insert needs a numeric dimensional array but the key
		// was used with a hash to avoid duplicate entries hence the re-copy
		$inserts = array_values( $inserts );

		$this->logger->info(
			[ 'QueryDependency', 'Table insert: {id} ID' ],
			[ 'method' => __METHOD__, 'role' => 'developer', 'id' => $sid ]
		);

		$connection->insert(
			SQLStore::QUERY_LINKS_TABLE,
			$inserts,
			__METHOD__
		);

		$connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 2.4
	 *
	 * @param WikiPage $subject
	 * @param string $subobjectName
	 *
	 * @return int
	 */
	public function getId( WikiPage $subject, $subobjectName = '' ) {
		if ( $subobjectName !== '' ) {
			$subject = new WikiPage(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$subobjectName
			);
		}

		$id = $this->store->getObjectIds()->getId(
			$subject
		);

		return $id;
	}

	/**
	 * @since 2.4
	 *
	 * @param WikiPage $subject
	 * @param string $subobjectName
	 */
	public function createId( WikiPage $subject, $subobjectName = '' ) {
		$id = $this->store->getObjectIds()->makeSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subobjectName,
			false
		);

		$this->logger->info(
			[ 'QueryDependency', 'Table update: new {id} ID; {origin}' ],
			[ 'method' => __METHOD__, 'role' => 'developer', 'id' => $id, 'origin' => $subject->getHash() . $subobjectName ]
		);

		return $id;
	}

}
