<?php

namespace SMW\MediaWiki;


use SMW\DBConnectionProvider;

/**
 * Wikia Change -> whole file
 * QueryEngineDatabase use slave for read and write operation.
 * There is no write in QueryEngine but ->query() use writeConnection.
 * Query needs to create temporary tables and insert data to it.
 * @package SMW\MediaWiki
 */
class QueryEngineDatabaseConnectionProvider implements DBConnectionProvider {

	private $connection = null;

	/**
	 * Returns the database connection.
	 * Initialization of this connection is done if it was not already initialized.
	 *
	 * @since 0.1
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
	 * @since 0.1
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

		$connection->resetTransactionProfiler();

		return $connection;
	}
}
