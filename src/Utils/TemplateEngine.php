<?php

namespace SMW\Utils;

use SMW\Exception\FileNotReadableException;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TemplateEngine {

	/**
	 * Remove some whitespace from the generate HTML.
	 */
	const HTML_TIDY = 2;

	/**
	 * @var []
	 */
	private static $templates = [];

	/**
	 * @var string
	 */
	private $templateDir;

	/**
	 * @var []
	 */
	private $container = [];

	/**
	 * @var []
	 */
	private $compiled = [];

	/**
	 * @since 3.1
	 *
	 * @param string|null $templateDir
	 */
	public function __construct( $templateDir = null ) {
		$this->templateDir = $templateDir;

		if ( $this->templateDir === null ) {
			$this->templateDir = $GLOBALS['smwgDir'] . '/data/template';
		}
	}

	/**
	 * @since 3.2
	 */
	public function clearTemplates() {
		self::$templates = [];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $target
	 * @param string $contents
	 */
	public function setContents( $target, $contents ) {
		$this->container[$target] = $contents;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $files
	 */
	public function bulkLoad( array $files ) {
		foreach ( $files as $file => $target ) {
			$this->load( $file, $target );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param string $file
	 * @param string $target
	 *
	 * @throws FileNotReadableException
	 */
	public function load( $file, $target ) {

		if ( isset( self::$templates[$file] ) ) {
			return $this->container[$target] = self::$templates[$file];
		}

		$_file = str_replace( [ '\\', '//', '/', '\\\\' ], DIRECTORY_SEPARATOR, $this->templateDir . '/' . $file );

		if ( !is_readable( $_file ) ) {
			throw new FileNotReadableException( $_file );
		}

		$this->container[$target] = self::$templates[$file] = file_get_contents( $_file );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $target
	 * @param array $args
	 */
	public function compile( $target, array $args = [] ) {

		if ( !isset( $this->container[$target] ) ) {
			return;
		}

		$complied = $this->container[$target];

		foreach ( $args as $key => $value ) {
			$complied = str_replace( [ '{{' . $key . '}}', '{{#' . $key . '}}' ], $value, $complied );
		}

		$this->compiled[$target] = $complied;
	}

	/**
	 * @deprecated 3.2, use TemplateEngine::publish
	 * @since 3.1
	 *
	 * @param string $target
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function code( $target ) {
		return $this->publish( $target );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $target
	 * @param int $flag
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function publish( $target, int $flag = -1 ) {

		if ( !isset( $this->compiled[$target] ) ) {
			throw new RuntimeException( "Unknown `$target` reference!" );
		}

		if ( ( self::HTML_TIDY & $flag ) == $flag ) {
			return preg_replace( '/(\>)\s*(\<)/m', '$1$2', $this->compiled[$target] );
		}

		return $this->compiled[$target];
	}

}
