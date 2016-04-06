<?php

namespace SMW\SQLStore;

use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\InMemoryPoolCache;
use SMW\MediaWiki\Database;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class IdToDataItemMatchFinder {

	const POOLCACHE_ID = 'sql.store.id.dataitem.finder';

	/**
	 * @var Database|null
	 */
	private $connection = null;

	/**
	 * @var InMemoryPoolCache
	 */
	private $inMemoryPoolCache;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
		$this->inMemoryPoolCache = InMemoryPoolCache::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 * @param string $hash
	 */
	public function saveToCache( $id, $hash ) {
		$this->inMemoryPoolCache->getPoolCacheFor( self::POOLCACHE_ID  )->save( $id, $hash );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 */
	public function deleteFromCache( $id ) {
		$this->inMemoryPoolCache->getPoolCacheFor( self::POOLCACHE_ID )->delete( $id );
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->inMemoryPoolCache->resetPoolCacheFor( self::POOLCACHE_ID );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $idList
	 *
	 * @return DIWikiPage[]
	 */
	public function getDataItemPoolHashListFor( array $idList ) {

		$rows = $this->connection->select(
			\SMWSQLStore3::ID_TABLE,
			array(
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			),
			array( 'smw_id' => $idList ),
			__METHOD__
		);

		$dataItemPoolHashList = array();

		foreach ( $rows as $row ) {
			$dataItemPoolHashList[] = HashBuilder::createHashIdFromSegments(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);
		}

		return $dataItemPoolHashList;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemForId( $id ) {

		$poolCache = $this->inMemoryPoolCache->getPoolCacheFor( self::POOLCACHE_ID );

		if ( !$poolCache->contains( $id ) ) {

			$row = $this->connection->selectRow(
				\SMWSQLStore3::ID_TABLE,
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

		return HashBuilder::newDiWikiPageFromHash(
			$poolCache->fetch( $id )
		);
	}

}
