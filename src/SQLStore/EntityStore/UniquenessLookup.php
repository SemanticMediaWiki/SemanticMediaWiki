<?php

namespace SMW\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use InvalidArgumentException;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class UniquenessLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( Store $store, IteratorFactory $iteratorFactory ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
	}

	/**
	 * @since 3.0
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	public function isUnique( DataItem $dataItem ) {

		$type = $dataItem->getDIType();

		if ( $type !== DataItem::TYPE_WIKIPAGE && $type !== DataItem::TYPE_PROPERTY ) {
			throw new InvalidArgumentException( 'Expects a DIProperty or DIWikiPage object.' );
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );
		$query->options( [ 'LIMIT' => 2 ] );

		$query->table( SQLStore::ID_TABLE );

		// Only find entities
		$query->fields( [ 'smw_id', 'smw_sortkey' ] );

		if ( $type === DataItem::TYPE_WIKIPAGE ) {
			$query->condition( $query->eq( 'smw_title', $dataItem->getDBKey() ) );
			$query->condition( $query->eq( 'smw_namespace', $dataItem->getNamespace() ) );
			$query->condition( $query->eq( 'smw_subobject', $dataItem->getSubobjectName() ) );
		} else {
			$query->condition( $query->eq( 'smw_sortkey', $dataItem->getCanonicalLabel() ) );
			$query->condition( $query->eq( 'smw_namespace', SMW_NS_PROPERTY ) );
			$query->condition( $query->eq( 'smw_subobject', '' ) );
		}

		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWDELETEIW ) );
		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWREDIIW ) );

		$res = $connection->query(
			$query,
			__METHOD__
		);

		return $res->numRows() < 2;
	}

	/**
	 * @since 3.0
	 *
	 * @return Iterator|[]
	 */
	public function findDuplicates() {

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );
		$query->table( SQLStore::ID_TABLE );

		$query->fields(
			[
				'COUNT(*) as count',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			]
		);

		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWDELETEIW ) );

		$query->options(
			[
				'GROUP BY' => 'smw_title, smw_namespace, smw_iw, smw_subobject',

				// @see https://stackoverflow.com/questions/8119489/postgresql-where-count-condition
				// "HAVING count > 1"; doesn't work with postgres
				'HAVING' => 'count(*) > 1'
			]
		);

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

}
