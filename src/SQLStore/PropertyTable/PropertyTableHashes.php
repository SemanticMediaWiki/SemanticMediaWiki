<?php

namespace SMW\SQLStore\PropertyTable;

use RuntimeException;
use SMW\DIProperty;
use SMW\MediaWiki\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\EntityStore\IdCacheManager;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PropertyTableHashes {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	* @since 3.1
	*
	* @param Database $connection
	* @param IdCacheManager $idCacheManager
	*/
	public function __construct( Database $connection, IdCacheManager $idCacheManager ) {
		$this->connection = $connection;
		$this->idCacheManager = $idCacheManager;
	}

	/**
	 * Update the proptable_hash for a given page.
	 *
	 * @since 3.1
	 *
	 * @param integer $id ID of the page as stored in SMW IDs table
	 * @param string[] of hash values with table names as keys
	*/
	public function setPropertyTableHashes( $id, $hash = null ) {

		$update = [];

		if ( $hash === null ) {
			$update = [ 'smw_proptable_hash' => $hash, 'smw_rev' => null ];
		} elseif ( is_array( $hash ) ) {
			$update = [ 'smw_proptable_hash' => serialize( $hash ) ];
		} else {
			throw new RuntimeException( "Expected a null or an array as value!");
		}

		$this->connection->update(
			SQLStore::ID_TABLE,
			$update,
			[
				'smw_id' => $id
			],
			__METHOD__
		);

/*
		$smw_proptable = $hash === null ? null : serialize( $hash );

		$this->connection->upsert(
			SQLStore::ID_AUXILIARY_TABLE,
			[
				'smw_id' => $id,
				'smw_proptable' => $smw_proptable
			],
			[ 'smw_id' ],
			[
				'smw_id' => $id,
				'smw_proptable' => $smw_proptable
			],
			__METHOD__
		);
*/
		$this->setPropertyTableHashesCache( $id, $hash );

		if ( $hash === null ) {
			$this->idCacheManager->deleteCacheById( $id );
		}
	}

	/**
	 * Return an array of hashes with table names as keys. These
	 * hashes are used to compare new data with old data for each
	 * property-value table when updating data
	 *
	 * @since 1.8
	 *
	 * @param integer $id ID of the page as stored in the SMW IDs table
	 *
	 * @return array
	 */
	public function getPropertyTableHashesById( $id ) {

		if ( $id == 0 ) {
			return [];
		}

		$hash = null;
		$cache = $this->idCacheManager->get( 'propertytable.hash' );

		if ( ( $hash = $cache->fetch( $id ) ) !== false ) {
			return $hash;
		}

		$row = $this->connection->selectRow(
			SQLStore::ID_TABLE,
			[
				'smw_proptable_hash'
			],
			[
				'smw_id' => $id
			],
			__METHOD__
		);

		if ( $row !== false ) {
			$hash = $row->smw_proptable_hash;
		}

		if ( $hash !== null && $hash !== false ) {
			$hash = $this->connection->unescape_bytea( $hash );
		}

		$hash = $hash === null || $hash === false ? [] : unserialize( $hash );
		$cache->save( $id, $hash );

		return $hash;
	}

	/**
	 * @since 3.1
	 *
	 * @param $id integer
	 */
	public function clearPropertyTableHashCacheById( $id ) {
		$this->setPropertyTableHashesCache( $id, null );
	}

	/**
	 * @since 3.1
	 *
	 * @param $id integer
	 * @param string|null $hash
	 */
	public function setPropertyTableHashesCache( $id, $hash = null ) {

		// never cache 0
		if ( $id == 0 ) {
			return;
		}

		if ( $hash === null ) {
			$hash = [];
		} elseif ( is_string( $hash ) ) {
			$hash = unserialize( $hash );
		}

		$this->idCacheManager->get( 'propertytable.hash' )->save( $id, $hash );
	}

}
