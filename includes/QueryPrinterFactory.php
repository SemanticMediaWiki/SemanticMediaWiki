<?php

namespace SMW;

use InvalidArgumentException;
use SMW\Query\Exception\ResultFormatNotFoundException;

/**
 * Factory for "result formats", ie classes implementing QueryResultPrinter.
 *
 * @license GNU GPL v2+
 * @since 2.5 (since 1.9, renamed in 2.5)
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class QueryPrinterFactory {

	/**
	 * Returns the global instance of the factory.
	 *
	 * @since 2.5
	 *
	 * @return QueryPrinterFactory
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = self::newFromGlobalState();
		}

		return $instance;
	}

	private static function newFromGlobalState() {
		$instance = new self();

		foreach ( $GLOBALS['smwgResultFormats'] as $formatName => $printerClass ) {
			$instance->registerFormat( $formatName, $printerClass );
		}

		foreach ( $GLOBALS['smwgResultAliases'] as $formatName => $aliases ) {
			$instance->registerAliases( $formatName, $aliases );
		}

		return $instance;
	}

	/**
	 * Format registry. Format names pointing to their associated QueryResultPrinter implementing classes.
	 *
	 * @var string[]
	 */
	private $formats = [];

	/**
	 * Form alias registry. Aliases pointing to their canonical format name.
	 *
	 * @var string[]
	 */
	private $aliases = [];

	/**
	 * Registers a format.
	 * If there is a format already with the provided name,
	 * it will be overridden with the newly provided data.
	 *
	 * @since 2.5
	 *
	 * @param string $formatName
	 * @param string $class
	 *
	 * @throws InvalidArgumentException
	 */
	public function registerFormat( $formatName, $class ) {

		if ( !is_string( $formatName ) ) {
			throw new InvalidArgumentException( 'Format names can only be of type string' );
		}

		if ( !is_string( $class ) ) {
			throw new InvalidArgumentException( 'Format class names can only be of type string' );
		}

		$this->formats[$formatName] = $class;
	}

	/**
	 * Registers the provided format aliases.
	 * If an aliases is already registered, it will
	 * be overridden with the newly provided data.
	 *
	 * @since 2.5
	 *
	 * @param string $formatName
	 * @param array $aliases
	 *
	 * @throws InvalidArgumentException
	 */
	public function registerAliases( $formatName, array $aliases ) {

		if ( !is_string( $formatName ) ) {
			throw new InvalidArgumentException( 'Format names can only be of type string' );
		}

		foreach ( $aliases as $alias ) {
			if ( !is_string( $alias ) ) {
				throw new InvalidArgumentException( 'Format aliases can only be of type string' );
			}

			$this->aliases[$alias] = $formatName;
		}
	}

	/**
	 * Returns the canonical format names.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function getFormats() {
		return array_keys( $this->formats );
	}

	/**
	 * Returns if there is a format or format alias with the provided name.
	 *
	 * @since 2.5
	 *
	 * @param string $formatName Format name or alias
	 *
	 * @return boolean
	 */
	public function hasFormat( $formatName ) {
		$formatName = $this->getCanonicalName( $formatName );
		return array_key_exists( $formatName, $this->formats );
	}

	/**
	 * Returns a new instance of the handling result printer for the provided format.
	 *
	 * @since 2.5
	 *
	 * @param string $formatName
	 *
	 * @return QueryResultPrinter
	 * @throws ResultFormatNotFoundException
	 */
	public function getPrinter( $formatName ) {
		$class = $this->getPrinterClass( $formatName );
		return new $class( $formatName );
	}

	/**
	 * Returns the QueryResultPrinter implementing class that is the printer for the provided format.
	 *
	 * @param string $formatName Format name or alias
	 *
	 * @return string
	 * @throws ResultFormatNotFoundException
	 */
	private function getPrinterClass( $formatName ) {
		$formatName = $this->getCanonicalName( $formatName );

		if ( !array_key_exists( $formatName, $this->formats ) ) {
			throw new ResultFormatNotFoundException( 'Unknown format name "' . $formatName . '" has no associated printer class' );
		}

		return $this->formats[$formatName];
	}

	/**
	 * Resolves format aliases into their associated canonical format name.
	 *
	 * @since 2.5
	 *
	 * @param string $formatName Format name or alias
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getCanonicalName( $formatName ) {

		if ( !is_string( $formatName ) ) {
			throw new InvalidArgumentException( 'Format names can only be of type string' );
		}

		if ( array_key_exists( $formatName, $this->aliases ) ) {
			$formatName = $this->aliases[$formatName];
		}

		return $formatName;
	}

}
