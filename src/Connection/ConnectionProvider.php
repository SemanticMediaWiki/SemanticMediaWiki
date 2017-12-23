<?php

namespace SMW\Connection;

/**
 * Interface for database connection providers.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface ConnectionProvider {

	/**
	 * Returns the database connection.
	 * Initialization of this connection is done if it was not already initialized.
	 *
	 * @since 1.9
	 */
	public function getConnection();

	/**
	 * Releases the connection if doing so makes any sense resource wise.
	 *
	 * @since 1.9
	 */
	public function releaseConnection();

}
