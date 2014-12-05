<?php

namespace SMW\SQLStore;

use SMW\Cache\FixedInMemoryCache;
use SMW\MediaWiki\Database;
use SMW\Cache\Cache;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HashBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DataItemByIdFinder {

	/**
	 * @var Database|null
	 */
	private $connection = null;

	/**
	 * @var string
	 */
	private $tableName = '';

	/**
	 * @var Cache|null
	 */
	private $cache = null;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 * @param string $tableName
	 * @param Cache|null $cache
	 */
	public function __construct( Database $connection, $tableName, Cache $cache = null ) {
		$this->connection = $connection;
		$this->tableName = $tableName;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = new FixedInMemoryCache( 500 );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 * @param string $hash
	 */
	public function saveToCache( $id, $hash ) {
		$this->cache->save( $id, $hash );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 */
	public function deleteFromCache( $id ) {
		$this->cache->delete( $id );
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->cache = new FixedInMemoryCache( 500 );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemForId( $id ) {

		if ( !$this->cache->contains( $id ) ) {

			$row = $this->connection->selectRow(
				$this->tableName,
				array(
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject'
				),
				array( 'smw_id' => $id ),
				__METHOD__
			);

			if ( $row === false ) {
				return null;
			}

			$hash = HashBuilder::createHashIdFromSegments(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);

			$this->saveToCache( $id, $hash );
		}

		return HashBuilder::newDiWikiPageFromHash( $this->cache->fetch( $id ) );
	}

}
