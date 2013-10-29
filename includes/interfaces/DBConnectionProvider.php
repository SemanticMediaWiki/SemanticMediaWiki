<?php

namespace SMW;

/**
 * Interface for database connection providers.
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface DBConnectionProvider {

	/**
	 * Returns the database connection.
	 * Initialization of this connection is done if it was not already initialized.
	 *
	 * @since 0.1
	 *
	 * @return DatabaseBase
	 */
	public function getConnection();

	/**
	 * Releases the connection if doing so makes any sense resource wise.
	 *
	 * @since 0.1
	 */
	public function releaseConnection();

}
