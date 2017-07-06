<?php

namespace SMW\MediaWiki;

use SMW\DBConnectionProvider;

/**
 * QueryEngineDatabase use slave for read and write operation.
 * There is no write in QueryEngine but ->query() use writeConnection.
 * Query needs to create temporary tables and insert data to it.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Sqreek
 */
class QueryEngineDatabaseConnectionProvider implements DBConnectionProvider {

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var boolean
	 */
	private $resetTransactionProfiler = false;

	/**
	 * @see DBConnectionProvider::getConnection
	 *
	 * Returns the database connection.
	 * Initialization of this connection is done if it was not already initialized.
	 *
	 * @since 2.5
	 *
	 * @return Database
	 */
	public function getConnection() {
		if ($this->connection === null) {
			$this->connection = $this->createConnection();
		}

		return $this->connection;
	}

	/**
	 * Releases the connection if doing so makes any sense resource wise.
	 *
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 2.5
	 */
	public function releaseConnection() {
		$this->connection = null;
	}

	private function createConnection() {

		$connectionProvider = new LazyDBConnectionProvider( DB_SLAVE );
		// use same connection to read/write see explanation in this class doc
		$connection = new Database( $connectionProvider, $connectionProvider );

		global $wgDBprefix;
		$connection->setDBPrefix( $wgDBprefix );

		$connection->resetTransactionProfiler( $this->resetTransactionProfiler );

		return $connection;
	}
}
