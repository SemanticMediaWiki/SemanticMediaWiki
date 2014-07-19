<?php

namespace SMW;

use MWException;

/**
 * Factory for "result formats", ie classes implementing QueryResultPrinter.
 *
 * @since 1.9
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class FormatFactory {

	/**
	 * Returns an instance of the factory.
	 *
	 * @since 1.9
	 *
	 * @return FormatFactory
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new self();

			global $smwgResultFormats, $smwgResultAliases;

			foreach ( $smwgResultFormats as $formatName => $printerClass ) {
				$instance->registerFormat( $formatName, $printerClass );
			}

			foreach ( $smwgResultAliases as $formatName => $aliases ) {
				$instance->registerAliases( $formatName, $aliases );
			}
		}

		return $instance;
	}

	/**
	 * Constructor. Protected to enforce use of singleton.
	 */
	//protected function __construct() {} // TODO: enable when tests can deal w/ it

	/**
	 * Format registry. Format names pointing to their associated QueryResultPrinter implementing classes.
	 *
	 * @since 1.9
	 *
	 * @var array
	 */
	protected $formats = array();

	/**
	 * Form alias registry. Aliases pointing to their canonical format name.
	 *
	 * @since 1.9
	 *
	 * @var array
	 */
	protected $aliases = array();

	/**
	 * Registers a format.
	 * If there is a format already with the provided name,
	 * it will be overridden with the newly provided data.
	 *
	 * @since 1.9
	 *
	 * @param string $formatName
	 * @param string $class
	 *
	 * @throws MWException
	 */
	public function registerFormat( $formatName, $class ) {
		if ( !is_string( $formatName ) ) {
			throw new MWException( 'Format names can only be of type string' );
		}

		if ( !is_string( $class ) ) {
			throw new MWException( 'Format class names can only be of type string' );
		}

		$this->formats[$formatName] = $class;
	}

	/**
	 * Registers the provided format aliases.
	 * If an aliases is already registered, it will
	 * be overridden with the newly provided data.
	 *
	 * @since 1.9
	 *
	 * @param string $formatName
	 * @param array $aliases
	 *
	 * @throws MWException
	 */
	public function registerAliases( $formatName, array $aliases ) {
		if ( !is_string( $formatName ) ) {
			throw new MWException( 'Format names can only be of type string' );
		}

		foreach ( $aliases as $alias ) {
			if ( !is_string( $alias ) ) {
				throw new MWException( 'Format aliases can only be of type string' );
			}

			$this->aliases[$alias] = $formatName;
		}
	}

	/**
	 * Returns the canonical format names.
	 *
	 * @since 1.9
	 *
	 * @return array of string
	 */
	public function getFormats() {
		return array_keys( $this->formats );
	}

	/**
	 * Returns if there is a format or format alias with the provided name.
	 *
	 * @since 1.9
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
	 * @since 1.9
	 *
	 * @param string $formatName
	 *
	 * @return QueryResultPrinter
	 */
	public function getPrinter( $formatName ) {
		$class = $this->getPrinterClass( $formatName );
		return new $class( $formatName );
	}

	/**
	 * Returns the QueryResultPrinter implementing class that is the printer for the provided format.
	 *
	 * @since 1.9
	 *
	 * @param string $formatName Format name or alias
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function getPrinterClass( $formatName ) {
		$formatName = $this->getCanonicalName( $formatName );

		if ( !array_key_exists( $formatName, $this->formats ) ) {
			throw new MWException( 'Unknown format name "' . $formatName . '" has no associated printer class' );
		}

		return $this->formats[$formatName];
	}

	/**
	 * Resolves format aliases into their associated canonical format name.
	 *
	 * @since 1.9
	 *
	 * @param string $formatName Format name or alias
	 *
	 * @return string
	 * @throws MWException
	 */
	public function getCanonicalName( $formatName ) {
		if ( !is_string( $formatName ) ) {
			throw new MWException( 'Format names can only be of type string' );
		}

		if ( array_key_exists( $formatName, $this->aliases ) ) {
			$formatName = $this->aliases[$formatName];
		}

		return $formatName;
	}

}
