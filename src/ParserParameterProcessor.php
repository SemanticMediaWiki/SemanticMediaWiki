<?php

namespace SMW;

use SMW\Utils\ErrorCodeFormatter;

/**
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ParserParameterProcessor {

	/**
	 * @var string
	 */
	private $defaultSeparator = ',';

	/**
	 * @var array
	 */
	private $rawParameters;

	/**
	 * @var array
	 */
	private $parameters;

	/**
	 * @var null
	 */
	private $first = null;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @since 1.9
	 *
	 * @param array $rawParameters
	 */
	public function __construct( array $rawParameters = [] ) {
		$this->rawParameters = $rawParameters;
		$this->parameters = $this->doMap( $rawParameters );
	}

	/**
	 * Returns collected errors
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds an error
	 *
	 * @since 1.9
	 *
	 * @param mixed $error
	 */
	public function addError( $error ) {
		$this->errors = array_merge( (array)$error === $error ? $error : [ $error ], $this->errors );
	}

	/**
	 * @deprecated since 2.3, use ParserParameterProcessor::getFirstParameter
	 */
	public function getFirst() {
		return $this->getFirstParameter();
	}

	/**
	 * @since 2.3
	 *
	 * @return string
	 */
	public function getFirstParameter() {
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
	 * @return array
	 */
	public function toArray() {
		return $this->parameters;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasParameter( $key ) {
		return isset( $this->parameters[$key] ) || array_key_exists( $key, $this->parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 */
	public function removeParameterByKey( $key ) {
		unset( $this->parameters[$key] );
	}

	/**
	 * @deprecated since 2.5, use ParserParameterProcessor::getParameterValuesByKey
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getParameterValuesFor( $key ) {
		return $this->getParameterValuesByKey( $key );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	public function getParameterValuesByKey( $key ) {

		if ( $this->hasParameter( $key ) ) {
			return $this->parameters[$key];
		}

		return [];
	}

	/**
	 * @since 1.9
	 *
	 * @param array $parameters
	 */
	public function setParameters( array $parameters ) {
		$this->parameters = $parameters;
	}

	/**
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
	 * @since 2.3
	 *
	 * @param string $key
	 * @param array $values
	 */
	public function setParameter( $key, array $values ) {
		if ( $key !== '' && $values !== [] ) {
			$this->parameters[$key] = $values;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 * @param boolean $associative
	 */
	public static function sort( array &$parameters, $associative = true ) {

		// Associative vs. simple index array sort
		if ( $associative ) {
			ksort( $parameters );
		} else {
			sort( $parameters );
		}

		foreach ( $parameters as $key => &$value ) {
			if ( is_array( $value ) ) {
				self::sort( $value, is_int( $key ) );
			}
		}
	}

	/**
	 * Map raw parameters array into an 2n-array for simplified
	 * via [key] => [value1, value2]
	 */
	private function doMap( array $params ) {
		$results = [];
		$previousProperty = null;

		while ( key( $params ) !== null ) {

			$pipe = false;
			$values = [];

			// Only strings are allowed for processing
			if( !is_string( current ( $params ) ) ) {
				next( $params );
			}

			// Get the current element and divide it into parts
			$currentElement = explode( '=', trim( current ( $params ) ), 2 );

			// Looking to the next element for comparison
			$separator = $this->lookAheadOnNextElement( $params, $pipe );

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
				if ( $value !== '' ) {
					$results[$currentElement[0]][] = trim( $value );
				}
			}

			// +pipe indicates that elements are expected to be concatenated
			// with a | that was removed during a #parserFunction invocation
			if ( $pipe ) {
				$results[$currentElement[0]] = [ implode( '|', $results[$currentElement[0]] ) ];
			}
		}

		return $this->parseFromJson( $results );
	}

	private function lookAheadOnNextElement( &$params, &$pipe ) {

		$separator = '';

		if( !next( $params ) ) {
			return $separator;
		}

		$nextElement = explode( '=', trim( current( $params ) ), 2 );

		if ( $nextElement !== [] ) {
			// This allows assignments of type |Has property=Test1,Test2|+sep=,
			// as a means to support multiple value declaration
			if ( substr( $nextElement[0], - 5 ) === '+sep' ) {
				$separator = isset( $nextElement[1] ) ? $nextElement[1] !== '' ? $nextElement[1] : $this->defaultSeparator : $this->defaultSeparator;
				next( $params );
			}
		}

		if ( current( $params ) === '+pipe' ) {
			$pipe = true;
			next( $params );
		}

		return $separator;
	}

	private function parseFromJson( $results ) {

		if ( !isset( $results['@json'] ) || !isset( $results['@json'][0] ) ) {
			return $results;
		}

		// Restrict the depth to avoid resolving recursive assignment
		// that can not be handled beyond the 2:n
		$depth = 3;
		$params = json_decode( $results['@json'][0], true, $depth );

		if ( $params === null || json_last_error() !== JSON_ERROR_NONE ) {
			$this->addError( Message::encode(
				[
					'smw-parser-invalid-json-format',
					ErrorCodeFormatter::getStringFromJsonErrorCode( json_last_error() )
				]
			) );
			return $results;
		}

		array_walk( $params, function( &$value, $key ) {

			if ( $value === '' ) {
				$value = [];
			}

			if ( !is_array( $value ) ) {
				$value = [ $value ];
			}
		} );

		unset( $results['@json'] );
		return array_merge( $results, $params );
	}

}
