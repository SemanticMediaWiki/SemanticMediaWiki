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
	 * @since 3.1
	 *
	 * @param string|null $templateDir
	 * @param boolean $resetTemplates
	 */
	public function __construct( $templateDir = null, $resetTemplates = false ) {
		$this->templateDir = $templateDir;

		if ( $this->templateDir === null ) {
			$this->templateDir = $GLOBALS['smwgTemplateDir'];
		}

		if ( $resetTemplates ) {
			self::$templates = [];
		}
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

		$_file = str_replace( [ '\\', '/', '//' ], DIRECTORY_SEPARATOR, $this->templateDir . '/' . $file );

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

		foreach ( $args as $key => $value ) {
			$this->container[$target] = str_replace( [ '{{' . $key . '}}', '{{#' . $key . '}}' ], $value, $this->container[$target] );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param string $target
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function code( $target ) {

		if ( !isset( $this->container[$target] ) ) {
			throw new RuntimeException( "Unknown `$target` reference!" );
		}

		return $this->container[$target];
	}

}
