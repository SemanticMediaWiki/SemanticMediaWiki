<?php

namespace SMW\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\PropertyTableInfoFetcher;
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
	 * @param string|null $name
	 *
	 * @return Iterator|[]
	 */
	public function findDuplicates( $table = null ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );

		if ( $table === null ) {
			$table = SQLStore::ID_TABLE;
		}

		if ( $table === SQLStore::ID_TABLE ) {
			$this->id_table( $table, $query );
		} elseif( $table === PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) ) {
			$this->common_table( $table, $query );
		} elseif( $table === RedirectStore::TABLE_NAME ) {
			$this->common_table( $table, $query );
		}

		$rows = $query->execute( __METHOD__ );
		$fname = __METHOD__;

		if ( $rows === false ) {
			return [];
		}

		$callback = function( $row ) use( $connection, $table, $fname ) {

			$map = self::mapRow( $table, $row );
			$map = [ 'count'=> $row->count ] + $map;

			if ( $table === PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) ) {
				$row = $connection->selectRow(
					SQLStore::ID_TABLE,
					[ 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw', 'smw_subobject' ],
					[ 'smw_id' => $row->s_id ],
					$fname
				);

				$map += [
					'entity' => [
						'smw_id'=> $row->smw_id,
						'smw_title'=> $row->smw_title,
						'smw_namespace'=> $row->smw_namespace,
						'smw_iw'=> $row->smw_iw,
						'smw_subobject'=> $row->smw_subobject,
					]
				];
			}

			return $map;
		};

		$mappingIterator = $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $rows ),
			$callback
		);

		return $mappingIterator;
	}

	private function id_table( $table, $query ) {
		$fields = self::fields( $table );

		$query->table( $table );
		$query->fields( array_merge( [ 'COUNT(*) as count' ], $fields ) );

		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( 'smw_iw', SMW_SQL3_SMWDELETEIW ) );

		$query->options(
			[
				'GROUP BY' => implode( ',', $fields ),
				'HAVING' => 'count(*) > 1'
			]
		);
	}

	private function common_table( $table, $query ) {
		$fields = self::fields( $table );

		$query->table( $table );
		$query->fields( array_merge( [ 'COUNT(*) as count' ], $fields ) );

		$query->options(
			[
				'GROUP BY' => implode( ',', $fields ),
				'HAVING' => 'count(*) > 1'
			]
		);
	}

	private static function fields( $tableName ) {

		$fieldsDef = self::fieldsDef();

		if ( !isset( $fieldsDef[$tableName] ) ) {
			return [];
		}

		return $fieldsDef[$tableName];
	}

	private static function mapRow( $tableName, $row ) {

		$fieldsDef = self::fieldsDef();

		if ( !isset( $fieldsDef[$tableName] ) ) {
			return [];
		}

		$fields = $fieldsDef[$tableName];

		foreach ( $fields as $field ) {
			$map[$field] = $row->{$field};
		}

		return $map;
	}

	private static function fieldsDef() {
		return [
			SQLStore::ID_TABLE => [
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			RedirectStore::TABLE_NAME => [
				's_title',
				's_namespace',
				'o_id'
			],
			PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) => [
				's_id',
				'p_id',
				'o_id'
			]
		];
	}

}
