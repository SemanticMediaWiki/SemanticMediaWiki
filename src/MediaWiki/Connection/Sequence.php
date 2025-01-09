<?php

namespace SMW\MediaWiki\Connection;

use RuntimeException;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Sequence {

	/**
	 * @var Database|IDatabase
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
		if (
			!$connection instanceof Database &&
			!$connection instanceof IDatabase ) {
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
	 * @param string $table
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
	 * @param string $table
	 * @param string $field
	 *
	 * @return int
	 */
	public function restart( $table, $field ) {
		$fname = __METHOD__;

		if ( $this->connection->getType() !== 'postgres' ) {
			return;
		}

		if ( $this->tablePrefix !== null ) {
			$this->connection->tablePrefix( $this->tablePrefix );
		}

		$seq_num = $this->connection->selectField( $table, "max({$field})", [], __METHOD__ );
		$seq_num += 1;

		$sequence = self::makeSequence( $table, $field );

		$this->connection->onTransactionCommitOrIdle( function () use( $sequence, $seq_num, $fname ) {
			$this->connection->query( "ALTER SEQUENCE {$sequence} RESTART WITH {$seq_num}", $fname, ISQLPlatform::QUERY_CHANGE_SCHEMA );
		} );

		return $seq_num;
	}

}
