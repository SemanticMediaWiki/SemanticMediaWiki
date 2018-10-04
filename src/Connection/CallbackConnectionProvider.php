<?php

namespace SMW\Connection;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CallbackConnectionProvider implements ConnectionProvider {

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * @var mixed
	 */
	private $connection;

	/**
	 * @since 3.0
	 *
	 * @param callable $callback
	 */
	public function __construct( callable $callback ) {
		$this->callback = $callback;
	}

	/**
	 * @since 3.0
	 *
	 * @return mixed
	 */
	public function getConnection() {

		if ( $this->connection === null ) {
			$this->connection = call_user_func( $this->callback );
		}

		return $this->connection;
	}

	/**
	 * @since 3.0
	 */
	public function releaseConnection() {
		$this->connection = null;
	}

}
