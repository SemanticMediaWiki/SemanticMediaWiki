<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Url {

	/**
	 * @var []
	 */
	private $info = [];

	/**
	 * @var []
	 */
	private $flag = [];

	/**
	 * @since 3.2
	 *
	 * @param string $url
	 */
	public function __construct( string $url ) {
		$this->info = parse_url( $url );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function path( string $path = '' ) : string {

		if ( $path === '' ) {
			return $this->get( PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH );
		}

		return $this->get( PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS ) . "$path";
	}

	/**
	 * @since 3.2
	 *
	 * @param ...$args
	 *
	 * @return string
	 */
	public function get( ...$args ) : string {
		// PHP_URL_* aren't bit fields !!!!!
		$this->flag = $args;

		$text = '';

		// https://www.php.net/manual/en/function.parse-url.php#106731
		$scheme = isset( $this->info['scheme'] ) ? $this->info['scheme'] . '://' : '';
		$host = isset( $this->info['host'] ) ? $this->info['host'] : '';
		$port = isset( $this->info['port'] ) ? ':' . $this->info['port'] : '';
		$user = isset( $this->info['user'] ) ? $this->info['user'] : '';
		$pass = isset( $this->info['pass'] ) ? ':' . $this->info['pass'] : '';
		$pass = ( $user || $pass ) ? "$pass@" : '';
		$path = isset( $this->info['path'] ) ? $this->info['path'] : '';
		$query = isset( $this->info['query'] ) ? '?' . $this->info['query'] : '';
		$fragment = isset( $this->info['fragment'] ) ? '#' . $this->info['fragment'] : '';

		if ( $this->is( PHP_URL_SCHEME ) ) {
			$text .= $scheme;
		}

		if ( $this->is( PHP_URL_USER ) ) {
			$text .= $user;
		}

		if ( $this->is( PHP_URL_PASS ) ) {
			$text .= $pass;
		}

		if ( $this->is( PHP_URL_HOST ) ) {
			$text .= $host;
		}

		if ( $this->is( PHP_URL_PORT ) ) {
			$text .= $port;
		}

		if ( $this->is( PHP_URL_PATH ) ) {
			$text .= $path;
		}

		if ( $this->is( PHP_URL_QUERY ) ) {
			$text .= $query;
		}

		if ( $this->is( PHP_URL_FRAGMENT ) ) {
			$text .= $fragment;
		}

		return $text;
	}

	private function is( $flag ) : bool {
		return in_array( $flag, $this->flag );
	}

}
