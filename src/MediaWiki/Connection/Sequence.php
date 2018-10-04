<?php

namespace SMW\MediaWiki\Connection;

use SMW\SQLStore\SQLStore;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Sequence {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $tablePrefix;

	/**
	 * @since 3.0
	 */
	public function __construct( $connection ) {

		if ( !$connection instanceof Database && !$connection instanceof \DatabaseBase ) {
			throw new RuntimeException( "Invalid connection instance!" );
		}

		$this->connection = $connection;
	}

	/**
	 * @since 3.0
	 */
	public function tablePrefix( $tablePrefix = '' ) {
		$this->tablePrefix = $tablePrefix;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $tableName
	 * @param string $field
	 *
	 * @return string
	 */
	public static function makeSequence( $table, $field ) {
		return "{$table}_{$field}_seq";
	}

	/**
	 * @since 3.0
	 *
	 * @param string $tableName
	 * @param string $field
	 *
	 * @return integer
	 */
	public function restart( $table, $field ) {

		if ( $this->connection->getType() !== 'postgres' ) {
			return;
		}

		if ( $this->tablePrefix !== null ) {
			$this->connection->tablePrefix( $this->tablePrefix );
		}

		$seq_num = $this->connection->selectField( $table, "max({$field})", [], __METHOD__ );
		$seq_num += 1;

		$sequence = self::makeSequence( $table, $field );

		$this->connection->onTransactionIdle( function() use( $sequence, $seq_num ) {
			$this->connection->query( "ALTER SEQUENCE {$sequence} RESTART WITH {$seq_num}", __METHOD__ );
		} );

		return $seq_num;
	}

}
