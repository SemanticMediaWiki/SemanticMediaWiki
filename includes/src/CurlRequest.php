<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class CurlRequest implements HttpRequest {

	private $handle = null;

	/**
	 * @since  2.0
	 *
	 * @param $handle
	 */
	public function __construct( $handle ) {
		$this->handle = $handle;
	}

	/**
	 * @since  2.0
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return HttpRequest
	 */
	public function setOption( $name, $value ) {
		curl_setopt( $this->handle, $name, $value );
		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function getInfo( $name ) {
		return curl_getinfo( $this->handle, $name );
	}

	/**
	 * @since  2.0
	 *
	 * @return string
	 */
	public function getLastError() {
		return curl_error( $this->handle );
	}

	/**
	 * @since  2.0
	 *
	 * @return integer
	 */
	public function getLastErrorCode() {
		return curl_errno( $this->handle );
	}

	/**
	 * @since  2.0
	 *
	 * @return mixed
	 */
	public function execute() {
		return curl_exec( $this->handle );
	}

}

