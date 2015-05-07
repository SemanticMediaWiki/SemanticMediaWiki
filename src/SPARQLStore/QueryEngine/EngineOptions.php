<?php

namespace SMW\SPARQLStore\QueryEngine;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptions {

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @since 2.2
	 */
	public function __construct() {
		$this->set( 'smwgIgnoreQueryErrors', $GLOBALS['smwgIgnoreQueryErrors'] );
		$this->set( 'smwgQSortingSupport', $GLOBALS['smwgQSortingSupport'] );
		$this->set( 'smwgQRandSortingSupport', $GLOBALS['smwgQRandSortingSupport'] );
		$this->set( 'smwgQSubpropertyDepth', $GLOBALS['smwgQSubpropertyDepth'] );
		$this->set( 'smwgQSubcategoryDepth', $GLOBALS['smwgQSubcategoryDepth'] );
		$this->set( 'smwgSparqlQFeatures', $GLOBALS['smwgSparqlQFeatures'] );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return isset( $this->options[$key] ) || array_key_exists( $key, $this->options );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( $this->has( $key ) ) {
			return $this->options[$key];
		}

		throw new InvalidArgumentException( "{$key} is an unregistered option" );
	}

}
