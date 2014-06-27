<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
interface HttpRequest {

	/**
	 * @since  1.9.3
	 *
	 * @param $name
	 * @param $value
	 */
	public function setOption( $name, $value );

	/**
	 * @since  1.9.3
	 *
	 * @param $name
	 */
	public function getInfo( $name );

	/**
	 * @since  1.9.3
	 */
	public function getLastError();

	/**
	 * @since  1.9.3
	 */
	public function getLastErrorCode();

	/**
	 * @since  1.9.3
	 */
	public function execute();

}
