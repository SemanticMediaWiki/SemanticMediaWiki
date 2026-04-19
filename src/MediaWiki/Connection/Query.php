<?php

namespace SMW\MediaWiki\Connection;

use InvalidArgumentException;
use RuntimeException;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * @private
 *
 * Convenience class with methods to generate a SQL query statement where value
 * quotes and name transformations are done automatically.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Query {

	const TYPE_SELECT = 'SELECT';

	protected string $type = '';

	protected string $table = '';

	protected array $fields = [];

	protected array $conditions = [];

	protected array $options = [];

	private array $joins = [];

	public string $alias = '';

	public int $index = 0;

	public bool $autoCommit = false;

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Database $connection ) {
	}

	/**
	 * @since 3.0
	 *
	 * @throws InvalidArgumentException
	 */
	public function type( string $type ): void {
		$type = strtoupper( $type );

		if ( !in_array( $type, [ self::TYPE_SELECT ] ) ) {
			throw new InvalidArgumentException( "$type was not recognized as valid type!" );
		}

		$this->type = $type;
	}

	/**
	 * @since 3.0
	 */
	public function fields( array $fields ): void {
		$this->fields = $fields;
	}

	/**
	 * @since 3.0
	 */
	public function field( array|string ...$field ): void {
		$this->fields[] = $field;
	}

	/**
	 * @since 3.0
	 */
	public function hasField( $field = '' ): bool {
		if ( (string)$field === '' ) {
			return $this->fields !== [];
		}

		return strpos( json_encode( $this->fields ), $field ) !== false;
	}

	/**
	 * @since 3.0
	 */
	public function hasCondition(): bool {
		return $this->conditions !== [];
	}

	/**
	 * Register the main table in form of ( 'foo' ) or as ( 'foo', 't1' ).
	 *
	 * @since 3.0
	 */
	public function table( array|string ...$table ): void {
		if ( strpos( $table[0] ?? '', 'SELECT' ) !== false ) {
			$tableName = '(' . $table[0] . ')';
		} else {
			$tableName = $this->connection->tableName( $table[0] );
		}

		$this->table = $tableName . (
			isset( $table[1] ) && is_string( $table[1] )
				? " AS " . $table[1]
				: ''
		);
	}

	/**
	 * @since 3.0
	 */
	public function join( array|string ...$join ): void {
		if ( strpos( $join[0], 'JOIN' ) === false ) {
			throw new InvalidArgumentException( "A join type is missing!" );
		}

		// ->join( 'INNNER JOIN', [ Table_Foo => ... ] )
		if ( is_array( $join[1] ) ) {
			$joins = [];

			foreach ( $join[1] as $table => $value ) {

				if ( is_string( $table ) ) {
					$value = $value[0] . $value[1] === 'ON' ? "$value" : "AS $value";
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
	 */
	public function like( string $k, string $v ): string {
		return "$k LIKE " . $this->connection->addQuotes( $v );
	}

	/**
	 * @since 3.1
	 */
	public function in( string $k, array $v ): string {
		return "$k IN (" . $this->connection->makeList( $v ) . ')';
	}

	/**
	 * @since 3.0
	 */
	public function eq( string $k, string $v ): string {
		return "$k=" . $this->connection->addQuotes( $v );
	}

	/**
	 * @since 3.0
	 */
	public function neq( string $k, string $v ): string {
		return "$k!=" . $this->connection->addQuotes( $v );
	}

	/**
	 * Supposed to be called `and` but this works only on PHP 7.1+.
	 *
	 * @since 3.0
	 */
	public function asAnd( string $condition ): array {
		return [ 'AND' => $condition ];
	}

	/**
	 * Supposed to be called `or` but this works only on PHP 7.1+.
	 *
	 * @since 3.0
	 */
	public function asOr( string $condition ): array {
		return [ 'OR' => $condition ];
	}

	/**
	 * @since 3.0
	 */
	public function condition( string|array $condition ): void {
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
	 */
	public function options( array $options ): void {
		$this->options = $options;
	}

	/**
	 * @since 3.1
	 */
	public function option( string $key, ?string $value ): void {
		if ( $value === null ) {
			unset( $this->options[$key] );
		} else {
			$this->options[$key] = $value;
		}
	}

	/**
	 * @since 3.0
	 */
	public function __toString(): string {
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
	 */
	public function getSQL(): string {
		return $this->sql();
	}

	/**
	 * @since 3.0
	 */
	public function build(): string {
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
	 */
	public function execute( string $fname = __METHOD__ ): bool|IResultWrapper {
		return $this->connection->readQuery( $this, $fname );
	}

	private function sql(): string {
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
