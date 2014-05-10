<?php

namespace SMW\Tests\Util;

use SMW\DBConnectionProvider;
use DatabaseBase;

/**
 * @ingroup Test
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MwDBConnectionProvider implements DBConnectionProvider {

	/* @var DatabaseBase */
	protected $dbConnection = null;

	/**
	 * @since 1.9.3
	 *
	 * @param DatabaseBase|null $dbConnection
	 */
	public function __construct( DatabaseBase $dbConnection = null ) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @since  1.9.3
	 *
	 * @param DatabaseBase $dbConnection
	 */
	public function setConnection( DatabaseBase $dbConnection ) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @since  1.9.3
	 *
	 * @return DatabaseBase
	 */
	public function getConnection() {

		if ( $this->dbConnection === null ) {
			$this->dbConnection = wfGetDB( DB_MASTER );
		}

		return $this->dbConnection;
	}

	public function releaseConnection() {}

}
