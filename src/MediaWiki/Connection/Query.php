<?php

namespace SMW\MediaWiki\Connection;

use InvalidArgumentException;
use RuntimeException;

/**
 * @private
 *
 * Convenience class with methods to generate a SQL query statement where value
 * quotes and name transformations are done automatically.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Query {

	const TYPE_SELECT = 'SELECT';

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var string
	 */
	protected $type = '';

	/**
	 * @var []
	 */
	protected $table = '';

	/**
	 * @var []
	 */
	protected $fields = [];

	/**
	 * @var []
	 */
	protected $conditions = [];

	/**
	 * @var []
	 */
	protected $options = [];

	/**
	 * @var []
	 */
	private $joins = [];

	/**
	 * @var string
	 */
	public $alias = '';

	/**
	 * @var integer
	 */
	public $index = 0;

	/**
	 * @var boolean
	 */
	public $autoCommit = false;

	/**
	 * @since 3.0
	 *
	 * @param Database $connection
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @throws InvalidArgumentException
	 */
	public function type( $type ) {

		$type = strtoupper( $type );

		if ( !in_array( $type, [ self::TYPE_SELECT ] ) ) {
			throw new InvalidArgumentException( "$type was not recognized as valid type!" );
		}

		$this->type = $type;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $fields
	 */
	public function fields( array $fields ) {
		$this->fields = $fields;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 */
	public function field( ...$field ) {
		$this->fields[] = $field;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasField( $field = '' ) {

		if ( (string)$field === '' ) {
			return $this->fields !== [];
		}

		return strpos( json_encode( $this->fields ), $field ) !== false;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasCondition() {
		return $this->conditions !== [];
	}

	/**
	 * Register the main table in form of ( 'foo' ) or as ( 'foo', 't1' ).
	 *
	 * @since 3.0
	 *
	 * @param string $table
	 */
	public function table( ...$table ) {
		$this->table = $this->connection->tableName( $table[0] ) . ( isset( $table[1] ) ? " AS " . $table[1] : '' );
	}

	/**
	 * @since 3.0
	 *
	 * @param string ...$join
	 */
	public function join( ...$join ) {

		if ( strpos( $join[0], 'JOIN' ) === false ) {
			throw new InvalidArgumentException( "A join type is missing!" );
		}

		// ->join( 'INNNER JOIN', [ Table_Foo => ... ] )
		if ( is_array( $join[1] ) ) {
			$joins = [];

			foreach ( $join[1] as $table => $value ) {

				if ( is_string( $table ) ) {
					$value = $value{0} . $value{1} === 'ON' ? "$value" : "AS $value";
					$value = $this->connection->tableName( $table ) . " $value";
				}

				$joins[] = $value;
			}

			$join[1] = implode( ' ', $joins );
		}

		$this->joins[] = $join;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return string
	 */
	public function like( $k, $v ) {
		return "$k LIKE " . $this->connection->addQuotes( $v );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $k
	 * @param array $v
	 *
	 * @return string
	 */
	public function in( $k, array $v ) {
		return "$k IN (" . $this->connection->makeList( $v ) . ')';
	}

	/**
	 * @since 3.0
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return string
	 */
	public function eq( $k, $v ) {
		return "$k=" . $this->connection->addQuotes( $v );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return string
	 */
	public function neq( $k, $v ) {
		return "$k!=" . $this->connection->addQuotes( $v );
	}

	/**
	 * Supposed to be called `and` but this works only on PHP 7.1+.
	 *
	 * @since 3.0
	 *
	 * @param string $condition
	 *
	 * @return array
	 */
	public function asAnd( $condition ) {
		return [ 'AND' => $condition ];
	}

	/**
	 * Supposed to be called `or` but this works only on PHP 7.1+.
	 *
	 * @since 3.0
	 *
	 * @param string $condition
	 *
	 * @return array
	 */
	public function asOr( $condition ) {
		return [ 'OR' => $condition ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string|array $condition
	 */
	public function condition( $condition ) {

		if ( $condition === '' ) {
			return;
		}

		if ( is_string( $condition ) ) {
			$condition = [ $condition ];
		}

		$this->conditions[] = $condition;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $options
	 */
	public function options( array $options ) {
		$this->options = $options;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function option( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function __toString() {

		$params = [
			'tables' => $this->table,
			'fields' => $this->fields,
			'conditions' => $this->conditions,
			'joins' => $this->joins,
			'options' => $this->options,
			'alias' => $this->alias,
			'index' => $this->index,
			'autocommit' => $this->autoCommit
		];

		return json_encode( $params );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getSQL() {
		return $this->sql();
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function build() {

		$statement = $this->sql();

		$this->type = '';
		$this->table = '';
		$this->conditions = [];
		$this->options = [];
		$this->joins = [];
		$this->fields = [];
		$this->alias = '';
		$this->index = 0;

		return $statement;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $fname
	 *
	 * @return iterable
	 */
	public function execute( $fname ) {
		return $this->connection->query( $this, $fname );
	}

	private function sql() {

		$i = 0;
		$sql = "";
		$fields = [];

		if ( $this->type === '' ) {
			throw new RuntimeException( "Missing a type" );
		} else {
			$sql = "$this->type ";
		}

		if ( isset( $this->options['DISTINCT'] ) ) {
			if ( is_bool( $this->options['DISTINCT'] ) ) {
				$sql .= 'DISTINCT ';
			} else {
				$sql .= 'DISTINCT ' . $this->options['DISTINCT'] . ' ';
			}
		}

		foreach ( $this->fields as $field ) {
			$fields[] = is_array( $field ) ? implode( ' AS ', $field ) : $field;
		}

		if ( $fields === [] ) {
			throw new RuntimeException( "Missing a field" );
		}

		$sql .= implode( ', ', $fields );
		$sql .= ' FROM ';
		$sql .= $this->table;

		foreach ( $this->joins as $join ) {
			$sql .= ' ' . implode( ' ', $join );
		}

		$conditions = [];

		foreach ( $this->conditions as $condition ) {

			foreach ( $condition as $exp => $cond ) {
				if ( $i > 0 && is_int( $exp ) ) {
					$exp = 'AND';
				}

				if ( is_array( $cond ) ) {
					$cond = implode( " $exp ", $cond );
				}

				if ( $cond !== '' ) {

					if ( $i > 0 && $exp === 'OR' ) {
						$conditions = [ '(' . implode( ' ', $conditions ) . " OR ($cond))" ];
					} else {
						$conditions[] = $i == 0 ? "($cond)" : "$exp ($cond)";
					}
				}
			}

			$i++;
		}

		if ( $conditions !== [] ) {
			$sql .= ' WHERE ' . implode( ' ', $conditions );
		}

		if ( isset( $this->options['GROUP BY'] ) ) {
			$sql .= " GROUP BY " . $this->options['GROUP BY'];

			if ( isset( $this->options['HAVING'] ) ) {
				$sql .= " HAVING " . $this->options['HAVING'];
			}
		}

		if ( isset( $this->options['ORDER BY'] ) ) {
			$sql .= " ORDER BY " . $this->options['ORDER BY'];
		}

		if ( isset( $this->options['LIMIT'] ) ) {
			$sql .= " LIMIT " . $this->options['LIMIT'];
		}

		if ( isset( $this->options['OFFSET'] ) ) {
			$sql .= " OFFSET " . $this->options['OFFSET'];
		}

		return $sql;
	}

}
