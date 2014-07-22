<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
interface HttpRequest {

	/**
	 * @since  2.0
	 *
	 * @param $name
	 * @param $value
	 */
	public function setOption( $name, $value );

	/**
	 * @since  2.0
	 *
	 * @param $name
	 */
	public function getInfo( $name );

	/**
	 * @since  2.0
	 */
	public function getLastError();

	/**
	 * @since  2.0
	 */
	public function getLastErrorCode();

	/**
	 * @since  2.0
	 */
	public function execute();

}
