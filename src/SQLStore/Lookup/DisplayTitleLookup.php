<?php

namespace SMW\SQLStore\Lookup;

use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\DIProperty;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DisplayTitleLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param Iterator|array $dataItems
	 *
	 * @return Iterator|array
	 */
	public function prefetchFromList( array $dataItems ) {

		$list = [];
		$prefetch = [];

		foreach ( $dataItems as $dataItem ) {
			$id = $subject = $this->store->getObjectIds()->getId(
				$dataItem
			);

			$list[$id] = $dataItem;
		}

		list( $rows, $unescape_bytea ) = $this->fetchFromTable( $list );

		foreach ( $rows as $row ) {

			if ( !isset( $list[$row->s_id] ) ) {
				continue;
			}

			$dataItem = $list[$row->s_id];

			if ( $row->o_blob !== null ) {
				$displayTitle = $unescape_bytea ? pg_unescape_bytea( $row->o_blob ) : $row->o_blob;
			} else {
				$displayTitle = $row->o_hash;
			}

			unset( $list[$row->s_id] );
			$prefetch[$dataItem->getSha1()] = $displayTitle;
		}

		// Those that don't have a DisplayTitle are marked with a NULL
		foreach ( $list as $id => $dataItem ) {
			$prefetch[$dataItem->getSha1()] = null;
		}

		return $prefetch;
	}

	private function fetchFromTable( $list ) {

		$property = new DIProperty( '_DTITLE' );
		$connection = $this->store->getConnection( 'mw.db' );

		$propTableId = $this->store->findPropertyTableID(
			$property
		);

		$propTables = $this->store->getPropertyTables();

		if ( !isset( $propTables[$propTableId] ) ) {
			throw new RuntimeException( "Missing $propTableId!" );
		}

		$propTable = $propTables[$propTableId];

		$rows = $connection->select(
			$connection->tablename( $propTable->getName() ),
			[
				's_id',
				'o_hash',
				'o_blob'
			],
			[
				's_id' => array_keys( $list )
			],
			__METHOD__
		);

		$unescape_bytea = $connection->isType( 'postgres' );

		return [ $rows, $unescape_bytea ];
	}

}
