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
