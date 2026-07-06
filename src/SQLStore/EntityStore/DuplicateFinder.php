<?php

namespace SMW\SQLStore\EntityStore;

use InvalidArgumentException;
use Iterator;
use SMW\DataItems\DataItem;
use SMW\IteratorFactory;
use SMW\Iterators\MappingIterator;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateFinder {

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly IteratorFactory $iteratorFactory,
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @param DataItem $dataItem
	 *
	 * @return bool
	 */
	public function hasDuplicate( DataItem $dataItem ): bool {
		$type = $dataItem->getDIType();

		if ( $type !== DataItem::TYPE_WIKIPAGE && $type !== DataItem::TYPE_PROPERTY ) {
			throw new InvalidArgumentException( 'Expects a DIProperty or DIWikiPage object.' );
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$qb = $connection->newSelectQueryBuilder()
			->from( SQLStore::ID_TABLE )
			->select( [ 'smw_id', 'smw_sortkey' ] )
			->limit( 2 );

		if ( $type === DataItem::TYPE_WIKIPAGE ) {
			$qb->where( [
				'smw_title' => $dataItem->getDBKey(),
				'smw_namespace' => $dataItem->getNamespace(),
				'smw_subobject' => $dataItem->getSubobjectName(),
			] );
		} else {
			$qb->where( [
				'smw_sortkey' => $dataItem->getCanonicalLabel(),
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_subobject' => '',
			] );
		}

		foreach ( [ SMW_SQL3_SMWIW_OUTDATED, SMW_SQL3_SMWDELETEIW, SMW_SQL3_SMWREDIIW ] as $iw ) {
			$qb->andWhere( $connection->expr( 'smw_iw', '!=', $iw ) );
		}

		return $qb->caller( __METHOD__ )->fetchResultSet()->numRows() > 1;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $table
	 *
	 * @return MappingIterator|array
	 */
	public function findDuplicates( $table = null ): Iterator|array {
		$connection = $this->store->getConnection( 'mw.db' );

		if ( $table === null ) {
			$table = SQLStore::ID_TABLE;
		}

		$qb = $connection->newSelectQueryBuilder()->from( $table );

		if ( $table === SQLStore::ID_TABLE ) {
			$this->id_table( $table, $qb );
		} elseif ( $table === PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) ) {
			$this->common_table( $table, $qb );
		} elseif ( $table === RedirectStore::TABLE_NAME ) {
			$this->common_table( $table, $qb );
		}

		$rows = $qb->caller( __METHOD__ )->fetchResultSet();
		$fname = __METHOD__;

		if ( $rows->numRows() === 0 ) {
			return [];
		}

		$callback = function ( $row ) use ( $connection, $table, $fname ): array {
			$map = self::mapRow( $table, $row );
			$map = [ 'count' => $row->count ] + $map;

			if ( $table === PropertyTableInfoFetcher::findTableIdForDataItemTypeId( DataItem::TYPE_WIKIPAGE ) ) {
				$row = $connection->newSelectQueryBuilder()
					->select( [ 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw', 'smw_subobject' ] )
					->from( SQLStore::ID_TABLE )
					->where( [ 'smw_id' => $row->s_id ] )
					->caller( $fname )
					->fetchRow();

				$map += [
					'entity' => [
						'smw_id' => $row->smw_id,
						'smw_title' => $row->smw_title,
						'smw_namespace' => $row->smw_namespace,
						'smw_iw' => $row->smw_iw,
						'smw_subobject' => $row->smw_subobject,
					]
				];
			}

			return $map;
		};

		return $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $rows ),
			$callback
		);
	}

	private function id_table( string $table, SelectQueryBuilder $qb ): void {
		$fields = self::fields( $table );
		$connection = $this->store->getConnection( 'mw.db' );

		$qb->select( array_merge( [ 'count' => 'COUNT(*)' ], $fields ) )
			->andWhere( $connection->expr( 'smw_iw', '!=', SMW_SQL3_SMWIW_OUTDATED ) )
			->andWhere( $connection->expr( 'smw_iw', '!=', SMW_SQL3_SMWDELETEIW ) )
			->groupBy( $fields )
			->having( 'count(*) > 1' );
	}

	private function common_table( string $table, SelectQueryBuilder $qb ): void {
		$fields = self::fields( $table );

		$qb->select( array_merge( [ 'count' => 'COUNT(*)' ], $fields ) )
			->groupBy( $fields )
			->having( 'count(*) > 1' );
	}

	private static function fields( string $tableName ) {
		$fieldsDef = self::fieldsDef();

		if ( !isset( $fieldsDef[$tableName] ) ) {
			return [];
		}

		return $fieldsDef[$tableName];
	}

	/**
	 * @return mixed[]
	 */
	private static function mapRow( $tableName, $row ): array {
		$fieldsDef = self::fieldsDef();

		if ( !isset( $fieldsDef[$tableName] ) ) {
			return [];
		}

		$fields = $fieldsDef[$tableName];

		$map = [];
		foreach ( $fields as $field ) {
			$map[$field] = $row->{$field};
		}

		return $map;
	}

	private static function fieldsDef(): array {
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
