<?php

namespace SMW\MediaWiki;

use SMW\DBConnectionProvider;

use DatabaseBase;
use RuntimeException;

/**
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class DirectDBConnectionProvider implements DBConnectionProvider {

	/** @var DatabaseBase|null */
	protected $connection = null;

	/**
	 * @since 1.9.1
	 *
	 * @param DatabaseBase $connection
	 *
	 * @return DBConnectionProvider
	 */
	public function setConnection( DatabaseBase $connection ) {
		$this->connection = $connection;
		return $this;
	}

	/**
	 * @see DBConnectionProvider::getConnection
	 *
	 * @since 1.9.1
	 *
	 * @return DatabaseBase
	 * @throws RuntimeException
	 */
	public function getConnection() {

		if ( $this->connection instanceof DatabaseBase ) {
			return $this->connection;
		}

		throw new RuntimeException( 'Expected a DatabaseBase instance' );
	}

	/**
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 1.9.1
	 */
	public function releaseConnection() {}

}
