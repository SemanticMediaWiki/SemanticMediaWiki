<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWDITime as DIime;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PValueLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|integer
	 */
	public function getVersion() {
		return __METHOD__ . self::VERSION;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {

		$limit = 20;
		$offset = 0;

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$res = [];
		$continueOffset = 0;
		$property = null;

		if ( isset( $parameters['property'] ) ) {
			$property = $parameters['property'];

			// Get the last which represents the final output
			// Foo.Bar.Foobar.Baz
			if ( strpos( $property, '.' ) !== false ) {
				$chain = explode( '.', $property );
				$property = array_pop( $chain );
			}
		}

		if ( $property === '' || $property === null ) {
			return [];
		}

		if ( isset( $parameters['search'] ) ) {
			list( $res, $continueOffset ) = $this->fetchFromTable( $property, $limit, $offset, $parameters );
		}

		// Changing this output format requires to set a new version
		$res = [
			'query' => $res,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'pvalue',
				'limit' => $limit,
				'count' => count( $res )
			]
		];

		return $res;
	}

	private function fetchFromTable( $property, $limit, $offset, $parameters ) {

		$options = [];
		$search = $parameters['search'];
		$sort = false;

		$property = DIProperty::newFromUserLabel( $property );

		$table = $this->store->findPropertyTableID(
			$property
		);

		$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );
		$list = [];
		$continueOffset = 0;

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );
		$query->table( $table );

		list( $field, $diType ) = $this->getField( $property );

		$options = [
			'LIMIT' => $limit + 1,  // look ahead +1
			'OFFSET' => $offset
		];

		// Generally we don't want to sort results to avoid having the DB to use
		// temporary tables/filesort when the value pool is very large
		if ( isset( $parameters['sort'] ) ) {
			$sort = in_array( strtolower( $parameters['sort'] ), [ 'asc', 'desc' ] ) ? $parameters['sort'] : 'asc';
		}

		if ( $diType === DataItem::TYPE_WIKIPAGE ) {
			return $this->fetchFromIDTable( $query, $pid, $table, $field, $options, $search, $sort, $limit, $offset );
		}

		$query->field( $field );

		if ( trim( $search ) !== '' ) {
			if ( $diType === DataItem::TYPE_BLOB || $diType === DataItem::TYPE_URI ) {
				$this->build_like( $query, $field, $search );
			} else {
				$query->condition( $query->like( $field, '%' . $search . '%' ) );
			}
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
			__METHOD__
		);

		if ( $res->numRows() > $limit ) {
			$continueOffset = $offset + $res->numRows();
		}

		foreach ( $res as $row ) {

			$value = $row->{$field};

			// The internal serialization doesn't mean much to a user so
			// transformed it!
			if ( $diType === DataItem::TYPE_TIME ) {
				$value = DataValueFactory::getInstance()->newDataValueByItem(
					DIime::doUnserialize( $value ), $property )->getWikiValue();
			}

			$list[] = $value;
		}

		return [ $list, $continueOffset ];
	}

	private function fetchFromIDTable( $query, $pid, $table, $field, $options, $search, $sort, $limit, $offset ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$continueOffset = 0;
		$res = [];

		if ( trim( $search ) !== '' ) {
			$this->build_like( $query, 'smw_sortkey', $search );
		}

		if ( $sort ) {
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
			__METHOD__
		);

		if ( $res !== [] && $res->numRows() > $limit ) {
			$continueOffset = $offset + $res->numRows();
		}

		$list = [];

		foreach ( $res as $row ) {
			$list[] = str_replace( '_', ' ', $row->smw_title );
		}

		return [ $list, $continueOffset ];
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

	private function getField( $property ) {

		$typeId = $property->findPropertyTypeID();
		$diType = DataTypeRegistry::getInstance()->getDataItemId( $typeId );

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$diType
		);

	 	return [ $diHandler->getLabelField(), $diType ];
	}

	private function build_like( $query, $field, $search ) {

		$conds = [
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
