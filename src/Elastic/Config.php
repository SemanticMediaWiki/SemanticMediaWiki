<?php

namespace SMW\Elastic;

use SMW\Options;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Config extends Options {

	/**
	 * Whether `EalsticStore` was selected as default store or not
	 */
	const DEFAULT_STORE = 'elastic/defaultstore';

	/**
	 * Describes registered endpoints
	 */
	const ELASTIC_ENDPOINTS = 'elastic/endpoints';

	/**
	 * Contains deprecated or renamed settings.
	 *
	 * @var array
	 */
	private $deprecatedKeys = [
		'query' => [
			// 3.2
			'fallback.no.connection' => 'fallback.no_connection'
		]
	];

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function isDefaultStore() : bool {

		$defaultStore = $this->get(
			Config::DEFAULT_STORE
		);

		return $defaultStore === ElasticStore::class || $defaultStore === 'SMWElasticStore';
	}

	/**
	 * @note Can only be used during testing
	 *
	 * @since 3.2
	 *
	 * @param array $deprecatedKeys
	 */
	public function setDeprectedKeys( array $deprecatedKeys ) {

		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$this->deprecatedKeys = $deprecatedKeys;
	}

	/**
	 * @since 3.2
	 */
	public function reassignDeprectedKeys() {
		foreach ( $this->deprecatedKeys as $k => $keys ) {
			foreach ( $keys as $deprected => $new ) {

				if ( isset( $this->options[$k][$deprected] ) ) {
					$this->options[$k][$new] = $this->options[$k][$deprected];
				}

				unset( $this->options[$k][$deprected] );
			}
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $data
	 */
	public function loadFromJSON( $data ) {

		if ( $data === false ) {
			return;
		}

		$data = json_decode( $data, true );
		$merge = true;

		if ( ( $error = json_last_error() ) !== JSON_ERROR_NONE ) {
			throw new RuntimeException( 'JSON returned with a "' . json_last_error_msg() . '"' );
		}

		foreach ( $data as $key => $value ) {

			if ( $merge && isset( $this->options[$key] ) && is_array( $value ) && is_array( $this->options[$key] ) ) {
				$value = array_merge( $this->options[$key], $value );
			}

			$this->options[$key] = $value;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 *
	 * @return string|false
	 * @throws RuntimeException
	 */
	public function readFile( $file ) {

		if ( $file === false ) {
			return false;
		}

		$file = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, realpath( $file ) );

		if ( is_readable( $file ) ) {
			return file_get_contents( $file );
		}

		throw new RuntimeException( "$file is inaccessible!" );
	}

}
