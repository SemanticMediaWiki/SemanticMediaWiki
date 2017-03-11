<?php

namespace SMW\SQLStore;

use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Database;
use SMW\IteratorFactory;
use SMW\RequestOptions;

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
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var InMemoryPoolCache
	 */
	private $inMemoryPoolCache;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( Database $connection, IteratorFactory $iteratorFactory ) {
		$this->connection = $connection;
		$this->iteratorFactory = $iteratorFactory;
		$this->inMemoryPoolCache = ApplicationFactory::getInstance()->getInMemoryPoolCache();
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 * @param string $hash
	 */
	public function saveToCache( $id, $hash ) {
		$this->inMemoryPoolCache->getPoolCacheById( self::POOLCACHE_ID  )->save( $id, $hash );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 */
	public function deleteFromCache( $id ) {
		$this->inMemoryPoolCache->getPoolCacheById( self::POOLCACHE_ID )->delete( $id );
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->inMemoryPoolCache->resetPoolCacheById( self::POOLCACHE_ID );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $idList
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DIWikiPage[]
	 */
	public function getDataItemsFromList( array $idList, RequestOptions $requestOptions = null ) {

		$conditions = array(
			'smw_id' => $idList,
		);

		if ( $requestOptions !== null ) {
			foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
				$conditions[] = $extraCondition;
			}
		}

		$rows = $this->connection->select(
			\SMWSQLStore3::ID_TABLE,
			array(
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			),
			$conditions,
			__METHOD__
		);

		$resultIterator = $this->iteratorFactory->newResultIterator( $rows );

		$mappingIterator = $this->iteratorFactory->newMappingIterator( $resultIterator, function( $row ) {
			return HashBuilder::createHashIdFromSegments(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);
		} );

		return $mappingIterator;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemById( $id ) {

		$poolCache = $this->inMemoryPoolCache->getPoolCacheById( self::POOLCACHE_ID );

		if ( !$poolCache->contains( $id ) && !$this->canMatchById( $id ) ) {
			return null;
		}

		$wikiPage = HashBuilder::newDiWikiPageFromHash(
			$poolCache->fetch( $id )
		);

		$wikiPage->setId( $id );

		return $wikiPage;
	}

	private function canMatchById( $id ) {

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
			return false;
		}

		$hash = HashBuilder::createHashIdFromSegments(
			$row->smw_title,
			$row->smw_namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		$this->saveToCache( $id, $hash );

		return true;
	}

}
