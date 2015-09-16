<?php

namespace SMW\SQLStore;

use SMW\MediaWiki\Database;
use SMW\InMemoryPoolCache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HashBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ByIdDataItemFinder {

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
		$this->inMemoryPoolCache->getPoolCacheFor( 'sql.store.dataitem.finder' )->save( $id, $hash );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 */
	public function deleteFromCache( $id ) {
		$this->inMemoryPoolCache->getPoolCacheFor( 'sql.store.dataitem.finder' )->delete( $id );
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->inMemoryPoolCache->resetPoolCacheFor( 'sql.store.dataitem.finder' );
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

		$poolCache = $this->inMemoryPoolCache->getPoolCacheFor( 'sql.store.dataitem.finder' );

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
