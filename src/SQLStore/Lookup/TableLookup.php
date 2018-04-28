<?php

namespace SMW\SQLStore\Lookup;

use SMW\IteratorFactory;
use SMW\Iterators\ResultIterator;
use SMW\Iterators\MappingIterator;
use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMW\MediaWiki\Database;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TableLookup {

	const ID_TABLE = SQLStore::ID_TABLE;

	/**
	 * @var Store
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {
		return $this->connection->addQuotes( $value );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @param ResultIterator $res
	 * @param callable $callback
	 *
	 * @return MappingIterator
	 */
	public function map( ResultIterator $res, callable $callback ) {
		return new MappingIterator( $res, $callback );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $fitableNameelds
	 * @param array $fields
	 * @param array $conditions
	 * @param array $options
	 *
	 * @return ResultIterator
	 */
	public function match( $tableName, array $fields, array $conditions, array $options = [], array $joins = [] ) {

		if ( is_array( $tableName ) ) {
			$callback = function( $table ) {
				return $this->connection->tableName( $table );
			};

			$tableName = array_map( $callback , $tableName );
		} else {
			$tableName = $this->connection->tableName( $tableName );
		}

		if ( $joins !== [] ) {
			$n_joins = [];

			foreach ( $joins as $table => $cond ) {
				$n_joins[$this->connection->tableName( $table )] = $cond;
			}

			$joins = $n_joins;
		}

		$fname = $this->origin !=='' ? $this->origin : __METHOD__;

		$res = $this->connection->select(
			$tableName,
			$fields,
			$conditions,
			$fname,
			$options,
			$joins
		);

		return new ResultIterator( $res );
	}

}
