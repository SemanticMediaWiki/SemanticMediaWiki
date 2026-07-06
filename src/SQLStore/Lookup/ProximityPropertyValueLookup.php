<?php

namespace SMW\SQLStore\Lookup;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ProximityPropertyValueLookup {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param Property $property
	 * @param string $search
	 * @param RequestOptions $opts
	 *
	 * @return array
	 */
	public function lookup( Property $property, string $search, RequestOptions $opts ): array {
		return $this->fetchFromTable( $property, $search, $opts );
	}

	/**
	 * @since 3.0
	 *
	 * @param Property $property
	 * @param $search
	 * @param RequestOptions $opts
	 *
	 * @return array
	 */
	public function fetchFromTable( Property $property, string $search, RequestOptions $opts ): array {
		$list = [];

		$table = $this->store->findPropertyTableID(
			$property
		);

		$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );

		$connection = $this->store->getConnection( 'mw.db' );
		$qb = $connection->newSelectQueryBuilder();

		[ $field, $diType ] = $this->getField( $property );

		// look ahead +1
		$limit = $opts->getLimit() + 1;
		$offset = $opts->getOffset();
		$sort = $opts->sort;

		if ( $diType === DataItem::TYPE_WIKIPAGE ) {
			return $this->fetchFromIDTable( $qb, $pid, $table, $limit, $offset, $search, $sort );
		}

		$qb->from( $table )
			->select( $field );

		if ( trim( $search ) !== '' ) {
			if ( $diType === DataItem::TYPE_BLOB || $diType === DataItem::TYPE_URI ) {
				$this->build_like( $qb, $connection, $field, $search );
			} else {
				$qb->andWhere( "$field LIKE " . $connection->addQuotes( '%' . $search . '%' ) );
			}
		} else {
			$qb->andWhere( "$field IS NOT NULL" );
		}

		if ( $this->isFixedPropertyTable( $table ) === false ) {
			$qb->andWhere( [ 'p_id' => $pid ] );

			// To make the MySQL query planner happy to pick the right index!
			$qb->select( 'p_id' );
		}

		if ( $sort ) {
			$qb->orderBy( $field, $sort );
		}

		$qb->distinct()
			->limit( $limit )
			->offset( $offset );

		$res = $qb->caller( __METHOD__ )->fetchResultSet();

		foreach ( $res as $row ) {

			$value = $row->{$field};

			// The internal serialization doesn't mean much to a user so
			// transformed it!
			if ( $diType === DataItem::TYPE_TIME ) {
				$value = DataValueFactory::getInstance()->newDataValueByItem(
					Time::doUnserialize( $value ),
					$property
				)->getWikiValue();
			}

			$list[] = $value;
		}

		return $list;
	}

	/**
	 * @return mixed[][]|string[]
	 */
	private function fetchFromIDTable(
		SelectQueryBuilder $qb,
		int $pid,
		?string $table,
		int $limit,
		int $offset,
		string $search,
		string|false $sort
	): array {
		$connection = $this->store->getConnection( 'mw.db' );
		$res = [];

		if ( trim( $search ) !== '' ) {
			$this->build_like( $qb, $connection, 'smw_sortkey', $search );
		}

		if ( is_string( $sort ) && $sort !== '' ) {
			$qb->orderBy( 'smw_title', $sort );
		}

		$qb->distinct()
			->limit( $limit )
			->offset( $offset )
			->select( [ 'smw_id', 'smw_title', 'smw_sortkey' ] );

		// Benchmarks showed that different select schema yield better results
		// for the following use cases
		if ( $this->isFixedPropertyTable( $table ) === false && $search !== '' ) {

			/**
			 * SELECT DISTINCT smw_id,smw_title,smw_sortkey
			 * FROM `smw_object_ids`
			 * INNER JOIN (
			 * 	SELECT o_id FROM `smw_di_wikipage` WHERE p_id='310167' GROUP BY o_id
			 * 	) AS t1 ON t1.o_id=smw_id
			 * 	WHERE ( smw_sortkey LIKE '%foo%' OR smw_sortkey LIKE '%Foo%' OR smw_sortkey LIKE '%FOO%')
			 * 	LIMIT 11
			 */

			$qb->from( SQLStore::ID_TABLE );

			$sub = $qb->newSubquery()
				->select( 'o_id' )
				->from( $table ?? '' )
				->where( [ 'p_id' => $pid ] )
				->groupBy( 'o_id' );

			$qb->join( $sub, 't1', 't1.o_id=smw_id' );

		} elseif ( $this->isFixedPropertyTable( $table ) === false ) {

			$qb->from( $table ?? '' )
				->andWhere( [ 'p_id' => $pid ] );

			// To make the MySQL query planner happy to pick the right index!
			$qb->select( 'p_id' );

			$qb->join( SQLStore::ID_TABLE, null, 'smw_id=o_id' );

		} else {

			/**
			 * SELECT DISTINCT smw_id,smw_title,smw_sortkey
			 * FROM `smw_fpt_sobj`
			 * INNER JOIN `smw_object_ids` ON ((smw_id=o_id))
			 * WHERE ( smw_sortkey LIKE '%foo%' OR smw_sortkey LIKE '%Foo%' OR smw_sortkey LIKE '%FOO%' )
			 * LIMIT 11
			 */
			$qb->from( $table ?? '' )
				->join( SQLStore::ID_TABLE, null, 'smw_id=o_id' );
		}

		$res = $qb->caller( __METHOD__ )->fetchResultSet();

		$list = [];

		foreach ( $res as $row ) {
			$list[] = str_replace( '_', ' ', $row->smw_title );
		}

		return $list;
	}

	private function isFixedPropertyTable( $table ) {
		$propertyTables = $this->store->getPropertyTables();

		foreach ( $propertyTables as $propertyTable ) {
			if ( $propertyTable->getName() === $table ) {
				return $propertyTable->isFixedPropertyTable();
			}
		}

		return false;
	}

	private function getField( Property $property ): array {
		$typeId = $property->findPropertyValueType();
		$diType = DataTypeRegistry::getInstance()->getDataItemByType( $typeId );

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$diType
		);

		return [ $diHandler->getLabelField(), $diType ];
	}

	private function build_like( SelectQueryBuilder $qb, Database $connection, string $field, string $search ): void {
		$conds = [
			// @phan-suppress-next-line PhanUselessBinaryAddRight
			'%' . $search . '%',
			'%' . ucfirst( $search ) . '%',
			'%' . strtoupper( $search ) . '%'
		] + ( $search !== strtolower( $search ) ? [ '%' . strtolower( $search ) . '%' ] : [] );

		$ors = [];

		foreach ( $conds as $c ) {
			$ors[] = "$field LIKE " . $connection->addQuotes( $c );
		}

		$qb->andWhere( '(' . implode( ' OR ', $ors ) . ')' );
	}

}
