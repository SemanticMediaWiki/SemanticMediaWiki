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

	private Database|IDatabase $connection;

	private string $tablePrefix;

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
	public function tablePrefix( string $tablePrefix = '' ): void {
		$this->tablePrefix = $tablePrefix;
	}

	/**
	 * @since 3.0
	 */
	public static function makeSequence( string $table, string $field ): string {
		return "{$table}_{$field}_seq";
	}

	/**
	 * @since 3.0
	 */
	public function restart( string $table, string $field ): int|false {
		$fname = __METHOD__;

		if ( $this->connection->getType() !== 'postgres' ) {
			return false;
		}

		if ( $this->tablePrefix !== null ) {
			$this->connection->tablePrefix( $this->tablePrefix );
		}

		$seq_num = $this->connection->selectField( $table, "max({$field})", [], __METHOD__ );
		$seq_num += 1;

		$sequence = self::makeSequence( $table, $field );

		$this->connection->onTransactionCommitOrIdle( function () use( $sequence, $seq_num, $fname ): void {
			$this->connection->query( "ALTER SEQUENCE {$sequence} RESTART WITH {$seq_num}", $fname, ISQLPlatform::QUERY_CHANGE_SCHEMA );
		} );

		return $seq_num;
	}

}
