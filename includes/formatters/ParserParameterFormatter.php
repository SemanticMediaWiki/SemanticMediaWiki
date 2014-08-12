<?php

namespace SMW;

/**
 * Class handling parser parameter formatting
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Handling raw parameters from the parser hook
 *
 * @ingroup Formatter
 */
class ParserParameterFormatter extends ArrayFormatter {

	/** @var string */
	protected $defaultSeparator = ',';

	/** @var array */
	protected $rawParameters;

	/** @var array */
	protected $parameters;

	/** @var string */
	protected $first = null;

	/**
	 * @since 1.9
	 *
	 * @param array $parameters
	 */
	public function __construct( array $parameters ) {
		$this->rawParameters = $parameters;
		$this->parameters = $this->format( $this->rawParameters );
	}

	/**
	 * Returns first available parameter
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getFirst() {
		return $this->first;
	}

	/**
	 * Returns raw parameters
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getRaw() {
		return $this->rawParameters;
	}

	/**
	 * Returns remapped parameters
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function toArray() {
		return $this->parameters;
	}

	/**
	 * Inject parameters from an outside source
	 *
	 * @since 1.9
	 *
	 * @param array
	 */
	public function setParameters( array $parameters ) {
		$this->parameters = $parameters;
	}

	/**
	 * Add parameter key and value
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function addParameter( $key, $value ) {
		if( $key !== '' && $value !== '' ) {
			$this->parameters[$key][] = $value;
		}
	}

	/**
	 * Do mapping of raw parameters array into an 2n-array for simplified
	 * via [key] => [value1, value2]
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	protected function format( array $params ) {
		$results = array();
		$previousProperty = null;

		while ( key( $params ) !== null ) {
			$separator = '';
			$values = array();

			// Only strings are allowed for processing
			if( !is_string( current ( $params ) ) ) {
				next( $params );
			}

			// Get the current element and divide it into parts
			$currentElement = explode( '=', trim( current ( $params ) ), 2 );

			// Looking to the next element for comparison
			if( next( $params ) ) {
				$nextElement = explode( '=', trim( current( $params ) ), 2 );

				if ( $nextElement !== array() ) {
					// This allows assignments of type |Has property=Test1,Test2|+sep=,
					// as a means to support multiple value declaration
					if ( substr( $nextElement[0], - 5 ) === '+sep' ) {
						$separator = isset( $nextElement[1] ) ? $nextElement[1] !== '' ? $nextElement[1] : $this->defaultSeparator : $this->defaultSeparator;
						next( $params );
					}
				}
			}

			// First named parameter
			if ( count( $currentElement ) == 1 && $previousProperty === null ) {
				$this->first = str_replace( ' ', '_', $currentElement[0] );
			}

			// Here we allow to support assignments of type |Has property=Test1|Test2|Test3
			// for multiple values with the same preceding property
			if ( count( $currentElement ) == 1 && $previousProperty !== null ) {
				$currentElement[1] = $currentElement[0];
				$currentElement[0] = $previousProperty;
			} else {
				$previousProperty = $currentElement[0];
			}

			// Reassign values
			if ( $separator !== '' && isset( $currentElement[1] ) ) {
				$values = explode( $separator, $currentElement[1] );
			} elseif ( isset( $currentElement[1] ) ) {
				$values[] = $currentElement[1];
			}

			// Remap properties and values to output a simple array
			foreach ( $values as $value ) {
				if ( $value !== '' ){
					$results[ $currentElement[0] ][] = $value;
				}
			}
		}

		return $results;
	}
}