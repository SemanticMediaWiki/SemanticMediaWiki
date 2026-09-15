<?php

namespace SMW;

use SMW\Exception\ConfigPreloadFileAlreadyLoadedException;
use SMW\Exception\ConfigPreloadFileNotReadableException;

/**
 * @private
 *
 * Applies settings profiles, such as the ones listed in `$smwgConfigProfiles`,
 * by assigning the settings a profile file returns to `$GLOBALS`.
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloader {

	private static array $config = [];

	/**
	 * Loading files from the internal `config` directory that provides some
	 * predeployed default settings. A name without the `.php` extension is
	 * completed with it.
	 *
	 * ```
	 * ( new ConfigPreloader() )->loadDefaultConfigFrom( 'media', 'db-primary-keys.php' );
	 * ```
	 *
	 * @since 3.2
	 *
	 * @return self
	 */
	public function loadDefaultConfigFrom( string ...$files ): ConfigPreloader {
		$dir = $GLOBALS['smwgDir'] . '/data/config/';

		foreach ( $files as $file ) {
			$file = str_ends_with( $file, '.php' ) ? $file : "$file.php";
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
	 * @param string ...$files
	 *
	 * @return self
	 */
	public function loadConfigFrom( ...$files ): ConfigPreloader {
		foreach ( $files as $file ) {
			$this->load( $file );
		}

		return $this;
	}

	private function load( string $file ): void {
		$file = str_replace( [ '\\', '//', '/' ], DIRECTORY_SEPARATOR, $file );

		if ( !is_readable( $file ) ) {
			throw new ConfigPreloadFileNotReadableException( $file );
		}

		self::$config[$file] ??= $this->requireProfile( $file );

		foreach ( self::$config[$file] as $key => $value ) {
			$GLOBALS[$key] = $value;
		}
	}

	private function requireProfile( string $file ): array {
		$config = require_once $file;

		// `require_once` returns `true` for a file that was included without this
		// class, such as by a `require` in LocalSettings.php
		if ( $config === true ) {
			throw new ConfigPreloadFileAlreadyLoadedException( $file );
		}

		return $config;
	}

}
