<?php

namespace SMW;

use SMW\Exception\ConfigPreloadFileNotReadableException;

/**
 * @private
 *
 * Convenience class to allow users to inject some default settings from
 * individual files directly from `enableSemantics`.
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloader {

	/**
	 * @var []
	 */
	private static $config = [];

	/**
	 * Loading files from the internal `config` directory that provides some
	 * predeployed default settings.
	 *
	 * ```
	 * enableSemantics( 'example.org' )->loadDefaultConfigFrom( 'media.php', 'xxx.php' );
	 * ```
	 *
	 * @since 3.2
	 *
	 * @param array $files
	 *
	 * @return self
	 */
	public function loadDefaultConfigFrom( ...$files ) : ConfigPreloader {

		$dir = $GLOBALS['smwgDir'] . '/data/config/';

		foreach ( $files as $file ) {
			$this->load( "$dir/$file" );
		}

		return $this;
	}

	/**
	 * Loading some custom config from "any" location and is provided for
	 * convenience to be used directly as in:
	 *
	 * ```
	 * enableSemantics( 'example.org' )->loadConfigFrom( __DIR__ . '/locationX/foo.php' );
	 * ```
	 *
	 * @since 3.2
	 *
	 * @param array $files
	 *
	 * @return self
	 */
	public function loadConfigFrom( ...$files ) : ConfigPreloader {

		foreach ( $files as $file ) {
			$this->load( $file );
		}

		return $this;
	}

	private function load( $file ) {

		$file = str_replace( [ '\\', '//', '/' ], DIRECTORY_SEPARATOR, $file );

		if ( !is_readable( $file ) ) {
			throw new ConfigPreloadFileNotReadableException( $file );
		}

		if ( ( $config = require_once $file ) !== true ) {
			self::$config[$file] = $config;
		}

		foreach ( self::$config[$file] as $key => $value ) {
			$GLOBALS[$key] = $value;
		}
	}

}
