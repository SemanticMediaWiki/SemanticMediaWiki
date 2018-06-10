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

		$list = [];
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
			list( $list, $continueOffset ) = $this->search( $property, $limit, $offset, $parameters );
		}

		// Changing this output format requires to set a new version
		$res = [
			'query' => $list,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'pvalue',
				'limit' => $limit,
				'count' => count( $list )
			]
		];

		return $res;
	}

	private function search( $property, $limit, $offset, $parameters ) {

		$conditions = [];
		$options = [];
		$fields = [];
		$search = $parameters['search'];
		$sort = false;

		$property = DIProperty::newFromUserLabel( $property );

		$table = $this->store->findPropertyTableID(
			$property
		);

		$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );
		$list = [];
		$continueOffset = 0;

		if ( $this->isFixedPropertyTable( $table ) === false ) {
			$conditions = [ 'p_id' => $pid ];

			// To make the MySQL query planner happy to pick the right index!
			$fields[] = 'p_id';
		}

		list( $field, $diType ) = $this->getField( $property );

		$fields[] = $field;

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
			return $this->lookupWikiPage( $property, $table, $field, $conditions, $options, $search, $sort, $limit, $offset );
		}

		$connection = $this->store->getConnection( 'mw.db' );

		if ( trim( $search ) !== '' ) {
			if ( $diType === DataItem::TYPE_BLOB ) {
				$conditions[] = " " . $this->like( $field, $search );
			} else {
				$conditions[] = "$field LIKE " . $connection->addQuotes( '%' . $search . '%' );
			}
		}

		if ( $sort ) {
			$options['ORDER BY'] = "$field $sort";
		}

		$options[] = 'DISTINCT';

		$res = $connection->select(
			$table,
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		$continueOffset = count( $res ) > $limit ? $offset + $limit : 0;

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

	private function lookupWikiPage( $property, $table, $field, $conditions, $options, $search, $sort, $limit, $offset ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$list = [];
		$continueOffset = 0;

		if ( trim( $search ) !== '' ) {
			$conditions[] =  " " . $this->like( 'smw_sortkey', $search );
		}

		$options[] = 'DISTINCT';

		if ( $sort ) {
			$options['ORDER BY'] = "smw_title $sort";
		}

		$res = $connection->select(
			[ $connection->tableName( SQLStore::ID_TABLE ), $connection->tableName( $table ) ],
			[ 'smw_id', 'smw_title', 'smw_sortkey' ],
			$conditions,
			__METHOD__,
			$options,
			[ $connection->tableName( SQLStore::ID_TABLE ) => [ 'INNER JOIN', [ 'smw_id=o_id' ] ] ]
		);

		$continueOffset = count( $res ) > $limit ? $offset + $limit : 0;

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

	private function like( $field, $search ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$conds = [
			'%' . $search . '%',
			'%' . ucfirst( $search ) . '%',
			'%' . strtoupper( $search ) . '%',
			'%' . strtolower( $search ) . '%'
		];

		$cond = [];

		foreach ( $conds as $c ) {
			$cond[] = $connection->addQuotes( $c );
		}

		return  "$field LIKE " . implode( " OR $field LIKE ", $cond );
	}

}
