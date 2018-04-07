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
use SMW\SQLStore\SQLStore;

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
	 * @since 3.0
	 *
	 * @return Iterator|[]
	 */
	public function findDuplicates() {

		$connection = $this->store->getConnection( 'mw.db' );

		$tableName = $connection->tableName(
			SQLStore::ID_TABLE
		);

		$condition = " smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED );
		$condition .= " AND smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW );

		$query = "SELECT " .
		"COUNT(*) as count, smw_title, smw_namespace, smw_iw, smw_subobject " .
		"FROM $tableName " .
		"WHERE $condition " .
		"GROUP BY smw_title, smw_namespace, smw_iw, smw_subobject " .
		"HAVING count(*) > 1";

		// @see https://stackoverflow.com/questions/8119489/postgresql-where-count-condition
		// "HAVING count > 1"; doesn't work with postgres

		$rows = $connection->query(
			$query,
			__METHOD__
		);

		if ( $rows === false ) {
			return [];
		}

		$resultIterator = $this->iteratorFactory->newResultIterator(
			$rows
		);

		$mappingIterator = $this->iteratorFactory->newMappingIterator( $resultIterator, function( $row ) {
			return [
				'count'=> $row->count,
				'smw_title'=> $row->smw_title,
				'smw_namespace'=> $row->smw_namespace,
				'smw_iw'=> $row->smw_iw,
				'smw_subobject'=> $row->smw_subobject
			];
		} );

		return $mappingIterator;
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

		$connection = $this->store->getConnection( 'mw.db' );

		$rows = $connection->select(
			\SMWSQLStore3::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			$conditions,
			__METHOD__
		);

		return $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $rows ),
			[ $this, 'newDIWikiPageFromRow' ]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param stdClass $row
	 *
	 * @return DIWikiPage
	 */
	public function newDIWikiPageFromRow( $row ) {

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

		$row->smw_id = $id;
		$dataItem = $this->newDIWikiPageFromRow( $row );

		$this->cache->save( $id, $dataItem );

		return $dataItem;
	}

}
