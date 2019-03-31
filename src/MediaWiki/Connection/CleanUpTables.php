<?php

namespace SMW\MediaWiki\Connection;

use RuntimeException;

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
	 * @var Database
	 */
	private $connection;

	/**
	 * @since 3.1
	 */
	public function __construct( $connection ) {

		if ( !$connection instanceof Database && !$connection instanceof \DatabaseBase ) {
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

		foreach ( $tables as $table ) {
			if ( strpos( $table, $tablePrefix ) !== false && $this->connection->tableExists( $table ) ) {
				if ( $this->connection->getType() === 'postgres' ) {
					$this->connection->query( "DROP TABLE IF EXISTS $table CASCADE", __METHOD__ );
				} else {
					$this->connection->query( "DROP TABLE $table", __METHOD__ );
				}
			}
		}
	}

}
