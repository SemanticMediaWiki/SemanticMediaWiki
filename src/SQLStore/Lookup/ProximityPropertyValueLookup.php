<?php

namespace SMW\SQLStore\Lookup;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\MediaWiki\Connection\Query;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

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
		$options = [];
		$list = [];

		$table = $this->store->findPropertyTableID(
			$property
		);

		$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );
		$continueOffset = 0;

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );
		$query->table( $table );

		[ $field, $diType ] = $this->getField( $property );

		// look ahead +1
		$limit = $opts->getLimit() + 1;
		$offset = $opts->getOffset();
		$sort = $opts->sort;

		$options = [
			'LIMIT' => $limit,
			'OFFSET' => $offset
		];

		if ( $diType === DataItem::TYPE_WIKIPAGE ) {
			return $this->fetchFromIDTable( $query, $pid, $table, $options, $search, $sort );
		}

		$query->field( $field );

		if ( trim( $search ) !== '' ) {
			if ( $diType === DataItem::TYPE_BLOB || $diType === DataItem::TYPE_URI ) {
				$this->build_like( $query, $field, $search );
			} else {
				$query->condition( $query->like( $field, '%' . $search . '%' ) );
			}
		} else {
			$query->condition( $query->neq( $field, 'NULL' ) );
		}

		if ( $this->isFixedPropertyTable( $table ) === false ) {
			$query->condition( $query->asAnd( $query->eq( 'p_id', $pid ) ) );

			// To make the MySQL query planner happy to pick the right index!
			$query->field( 'p_id' );
		}

		if ( $sort ) {
			$options['ORDER BY'] = "$field $sort";
		}

		$options['DISTINCT'] = true;

		$query->options( $options );

		$res = $connection->query(
			$query,
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_NONE
		);

		foreach ( $res as $row ) {

			$value = $row->{$field};

			// The internal serialization doesn't mean much to a user so
			// transformed it!
			if ( $diType === DataItem::TYPE_TIME ) {
				$value = DataValueFactory::getInstance()->newDataValueByItem(
					Time::doUnserialize( $value ), $property )->getWikiValue();
			}

			$list[] = $value;
		}

		return $list;
	}

	/**
	 * @param Query $query
	 * @param int $pid
	 * @param ?string $table
	 * @param array $options
	 * @param string $search
	 * @param string $sort
	 *
	 * @return mixed[][]|string[]
	 */
	private function fetchFromIDTable( Query $query, int $pid, ?string $table, array $options, string $search, string|false $sort ): array {
		$connection = $this->store->getConnection( 'mw.db' );
		$continueOffset = 0;
		$res = [];

		if ( trim( $search ) !== '' ) {
			$this->build_like( $query, 'smw_sortkey', $search );
		}

		if ( is_string( $sort ) && $sort !== '' ) {
			$options['ORDER BY'] = "smw_title $sort";
		}

		$options['DISTINCT'] = true;

		$query->options( $options );
		$query->fields( [ 'smw_id', 'smw_title', 'smw_sortkey' ] );

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

			$query->table( SQLStore::ID_TABLE );

			$query->join(
				'INNER JOIN',
				'( SELECT o_id FROM ' . $connection->tableName( $table ) .
					' WHERE p_id=' . $connection->addQuotes( $pid ) .
					' GROUP BY o_id )' .
				' AS t1 ON t1.o_id=smw_id'
			);

		} elseif ( $this->isFixedPropertyTable( $table ) === false ) {

			$query->condition( $query->asAnd( $query->eq( 'p_id', $pid ) ) );

			// To make the MySQL query planner happy to pick the right index!
			$query->field( 'p_id' );

			$query->join(
				'INNER JOIN',
				[ SQLStore::ID_TABLE => 'ON (smw_id=o_id)' ]
			);

		} else {

			/**
			 * SELECT DISTINCT smw_id,smw_title,smw_sortkey
			 * FROM `smw_fpt_sobj`
			 * INNER JOIN `smw_object_ids` ON ((smw_id=o_id))
			 * WHERE ( smw_sortkey LIKE '%foo%' OR smw_sortkey LIKE '%Foo%' OR smw_sortkey LIKE '%FOO%' )
			 * LIMIT 11
			 */
			$query->join(
				'INNER JOIN',
				[ SQLStore::ID_TABLE => 'ON (smw_id=o_id)' ]
			);
		}

		$res = $connection->query(
			$query,
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_NONE
		);

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
		$typeId = $property->findPropertyTypeID();
		$diType = DataTypeRegistry::getInstance()->getDataItemId( $typeId );

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$diType
		);

		return [ $diHandler->getLabelField(), $diType ];
	}

	private function build_like( $query, $field, $search ): void {
		$conds = [
			// @phan-suppress-next-line PhanUselessBinaryAddRight
			'%' . $search . '%',
			'%' . ucfirst( $search ) . '%',
			'%' . strtoupper( $search ) . '%'
		] + ( $search !== strtolower( $search ) ? [ '%' . strtolower( $search ) . '%' ] : [] );

		$cond = [];

		foreach ( $conds as $c ) {
			$query->condition( $query->asOr( $query->like( $field, $c ) ) );
		}
	}

}
