<?php

namespace SMW\MediaWiki;

use SMW\DBConnectionProvider;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DatabaseConnectionProvider implements DBConnectionProvider {

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var Database
	 */
	private $resetTransactionProfiler = false;

	/**
	 * @see DBConnectionProvider::getConnection
	 *
	 * @since 2.1
	 *
	 * @return Database
	 */
	public function getConnection() {

		if ( $this->connection === null ) {
			$this->connection = $this->createConnection();
		}

		return $this->connection;
	}

	/**
	 * @see #1499, #1603
	 *
	 * @since 2.4
	 */
	public function resetTransactionProfiler() {
		$this->resetTransactionProfiler = true;
	}

	/**
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 2.1
	 */
	public function releaseConnection() {
		$this->connection = null;
	}

	private function createConnection() {

		$connection = new Database(
			new LazyDBConnectionProvider( DB_SLAVE ),
			new LazyDBConnectionProvider( DB_MASTER )
		);

		$connection->setDBPrefix( $GLOBALS['wgDBprefix'] );

		// #1695
		if ( $this->resetTransactionProfiler ) {
			$connection->resetTransactionProfiler();
		}

		return $connection;
	}

}
