<?php

namespace SMW\MediaWiki\Connection;

use RuntimeException;
use Wikimedia\Rdbms\IDatabase;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CleanUpTables {

	/**
	 * @var Database|IDatabase
	 */
	private $connection;

	/**
	 * @since 3.1
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
	 * @since 3.1
	 *
	 * @param string $tablePrefix
	 */
	public function dropTables( $tablePrefix ) {
		$tables = $this->connection->listTables();

		// MW SQLite does some prefix meddling hence we require to remove any
		// prefix reference
		if ( $tablePrefix !== '' && $this->connection->getType() === 'sqlite' ) {
			$this->connection->tablePrefix( '' );
		}

		foreach ( $tables as $table ) {

			if ( strpos( $table, $tablePrefix ) === false || !$this->connection->tableExists( $table, __METHOD__ ) ) {
				continue;
			}

			if ( $this->connection->getType() === 'postgres' ) {
				$this->connection->query( "DROP TABLE IF EXISTS $table CASCADE", __METHOD__ );
			} else {
				$this->connection->query( "DROP TABLE $table", __METHOD__ );
			}
		}
	}

}
