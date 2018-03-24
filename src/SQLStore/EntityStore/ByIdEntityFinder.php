<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Database;
use SMW\IteratorFactory;
use SMW\PropertyRegistry;
use SMW\RequestOptions;
use Onoi\Cache\Cache;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ByIdEntityFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @since 2.1
	 *
	 * @param Store $store
	 * @param IteratorFactory $iteratorFactory
	 * @param Cache $cache
	 */
	public function __construct( Store $store, IteratorFactory $iteratorFactory, Cache $cache ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
		$this->cache = $cache;
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

		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->select(
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
			return HashBuilder::createFromSegments(
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

		if ( ( $dataItem = $this->get( (int)$id ) ) !== false ) {
			return $dataItem;
		}

		return null;
	}

	private function get( $id ) {

		if ( ( $dataItem = $this->cache->fetch( $id ) ) !== false ) {
			return $dataItem;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			\SMWSQLStore3::ID_TABLE,
			[
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject',
				'smw_sortkey',
				'smw_sort'
			],
			[ 'smw_id' => $id ],
			__METHOD__
		);

		if ( $row === false ) {
			return false;
		}

		if ( $row->smw_title !== '' && $row->smw_title{0} === '_' && (int)$row->smw_namespace === SMW_NS_PROPERTY ) {
		//	$row->smw_title = str_replace( ' ', '_', PropertyRegistry::getInstance()->findPropertyLabelById( $row->smw_title ) );
		}

		$dataItem = new DIWikiPage(
			$row->smw_title,
			$row->smw_namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		$dataItem->setId( $id );
		$dataItem->setSortKey( $row->smw_sortkey );
		$dataItem->setOption( 'sort', $row->smw_sort );

		$this->cache->save( $id, $dataItem );

		return $dataItem;
	}

}
