<?php

namespace SMW\SQLStore;

/**
 * Class responsible for the clean-up (aka disposal) of any outdated table entries
 * that are contained in either the ID_TABLE or related property tables with
 * reference to a matchable ID.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceDisposer {

	/**
	 * @var SQLStore
	 */
	private $store = null;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * Use case: After a property changed its type (_wpg -> _txt), object values in the
	 * ID table are not removed at the time of the conversion process.
	 *
	 * Before an attempt to remove the ID from entity tables, it is secured that no
	 * references exists for the ID.
	 *
	 * @note This method does not check for an ID being object or subject value
	 * and has to be done prior calling this routine.
	 *
	 * @since 2.4
	 *
	 * @param integer $id
	 */
	public function tryToRemoveOutdatedIDFromEntityTables( $id ) {

		if ( $this->store->getPropertyTableIdReferenceFinder()->hasResidualReferenceFor( $id ) ) {
			return null;
		}

		$this->removeIDFromEntityReferenceTables( $id );
	}

	/**
	 * @note This method does not make any assumption about the ID state and therefore
	 * has to be validated before this method is called.
	 *
	 * @since 2.4
	 *
	 * @param integer $id
	 */
	public function cleanUpTableEntriesFor( $id ) {

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			if ( $proptable->usesIdSubject() ) {
				$this->connection->delete(
					$proptable->getName(),
					array( 's_id' => $id ),
					__METHOD__
				);
			}

			if ( !$proptable->isFixedPropertyTable() ) {
				$this->connection->delete(
					$proptable->getName(),
					array( 'p_id' => $id ),
					__METHOD__
				);
			}

			$fields = $proptable->getFields( $this->store );

			// Match tables (including ftp_redi) that contain an object reference
			if ( isset( $fields['o_id'] ) ) {
				$this->connection->delete(
					$proptable->getName(),
					array( 'o_id' => $id ),
					__METHOD__
				);
			}
		}

		$this->removeIDFromEntityReferenceTables( $id );
	}

	private function removeIDFromEntityReferenceTables( $id ) {

		$this->connection->delete(
			SQLStore::ID_TABLE,
			array( 'smw_id' => $id ),
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			array( 'p_id' => $id ),
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			array( 's_id' => $id ),
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			array( 'o_id' => $id ),
			__METHOD__
		);
	}

}
