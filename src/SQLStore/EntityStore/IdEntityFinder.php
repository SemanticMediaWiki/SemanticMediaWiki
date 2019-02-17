<?php

namespace SMW\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class IdEntityFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	 * @since 2.1
	 *
	 * @param Store $store
	 * @param IteratorFactory $iteratorFactory
	 * @param IdCacheManager $idCacheManager
	 */
	public function __construct( Store $store, IteratorFactory $iteratorFactory, IdCacheManager $idCacheManager ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
		$this->idCacheManager = $idCacheManager;
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

		if ( $idList === [] ) {
			return [];
		}

		$conditions = [
			'smw_id' => $idList,
		];

		if ( $requestOptions !== null ) {
			foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
				$conditions[] = $extraCondition;
			}
		}

		$rows = $this->fetchFromTable(
			$conditions
		);

		if ( $rows === false ) {
			return [];
		}

		return $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $rows ),
			[ $this, 'newFromRow' ]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param stdClass $row
	 *
	 * @return DIWikiPage
	 */
	public function newFromRow( $row ) {

		$dataItem = new DIWikiPage(
			$row->smw_title,
			$row->smw_namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		$dataItem->setId( $row->smw_id );

		if ( isset( $row->smw_sortkey ) ) {
			$dataItem->setSortKey( $row->smw_sortkey );
		}

		if ( isset( $row->smw_sort ) ) {
			$dataItem->setOption( 'sort', $row->smw_sort );
		}

		if ( !$this->idCacheManager->hasCache( $row->smw_hash ) ) {
			$sortkey = $row->smw_sort === null ? '' : $row->smw_sortkey;

			$this->idCacheManager->setCache(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject,
				$row->smw_id,
				$sortkey
			);
		}

		return $dataItem;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemById( $id ) {

		if ( ( $dataItem = $this->get( (int)$id ) ) !== false ) {
			return $dataItem;
		}

		return null;
	}

	private function get( $id ) {

		$cache = $this->idCacheManager->get( 'entity.lookup' );

		if ( ( $dataItem = $cache->fetch( $id ) ) !== false ) {
			return $dataItem;
		}

		$row = $this->fetchFromTable(
			[ 'smw_id' => $id ],
			true
		);

		if ( $row === false ) {
			return false;
		}

		$dataItem = $this->newFromRow( $row );
		$cache->save( $id, $dataItem );

		return $dataItem;
	}

	private function fetchFromTable( $conditions, $selectRow = false ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$fields = [
			'smw_id',
			'smw_title',
			'smw_namespace',
			'smw_iw',
			'smw_subobject',
			'smw_sortkey',
			'smw_sort',
			'smw_hash'
		];

		if ( $selectRow ) {
			return $connection->selectRow( SQLStore::ID_TABLE, $fields, $conditions, __METHOD__ );
		}

		return $connection->select( SQLStore::ID_TABLE, $fields, $conditions, __METHOD__ );
	}

}
