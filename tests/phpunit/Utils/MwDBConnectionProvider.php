<?php

namespace SMW\Tests\Utils;

use SMW\DBConnectionProvider;
use DatabaseBase;

/**
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class MwDBConnectionProvider implements DBConnectionProvider {

	/* @var DatabaseBase */
	protected $dbConnection = null;

	protected $connectionId;

	/**
	 * @since 2.0
	 *
	 * @param int $connectionId
	 */
	public function __construct( $connectionId = DB_MASTER ) {
		$this->connectionId = $connectionId;
	}

	/**
	 * @since  2.0
	 *
	 * @return DatabaseBase
	 */
	public function getConnection() {

		if ( $this->dbConnection === null ) {
			$this->dbConnection = wfGetDB( $this->connectionId );
		}

		return $this->dbConnection;
	}

	public function releaseConnection() {
	}

}
