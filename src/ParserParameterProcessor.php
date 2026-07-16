<?php

namespace SMW;

use SMW\Localizer\Message;
use SMW\Utils\ErrorCodeFormatter;

/**
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class ParserParameterProcessor {

	private string $defaultSeparator = ',';

	private array $rawParameters;

	/**
	 * @var array
	 */
	private $parameters;

	private ?string $first = null;

	private array $errors = [];

	private bool $captureDisplayOptions;

	private array $displayOptions = [];

	/**
	 * @since 1.9
	 */
	public function __construct( array $rawParameters = [], bool $captureDisplayOptions = false ) {
		$this->captureDisplayOptions = $captureDisplayOptions;
		$this->rawParameters = $rawParameters;
		$this->parameters = $this->doMap( $rawParameters );
	}

	/**
	 * Returns collected errors
	 *
	 * @since 1.9
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Adds an error
	 *
	 * @since 1.9
	 *
	 * @param mixed $error
	 */
	public function addError( $error ): void {
		$this->errors = array_merge( (array)$error === $error ? $error : [ $error ], $this->errors );
	}

	/**
	 * @since 2.3
	 */
	public function getFirstParameter(): ?string {
		return $this->first;
	}

	/**
	 * Display modes captured from `+display` options, keyed by the property
	 * name they attach to, verbatim and unvalidated. Only populated when the
	 * processor was constructed with display option capture enabled.
	 *
	 * @since 7.2.0
	 *
	 * @return array<string, string>
	 */
	public function getDisplayOptions(): array {
		return $this->displayOptions;
	}

	/**
	 * Returns raw parameters
	 *
	 * @since 1.9
	 */
	public function getRaw(): array {
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
	 */
	public function hasParameter( $key ): bool {
		return isset( $this->parameters[$key] ) || array_key_exists( $key, $this->parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 */
	public function removeParameterByKey( $key ): void {
		unset( $this->parameters[$key] );
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
	 */
	public function setParameters( array $parameters ): void {
		$this->parameters = $parameters;
	}

	/**
	 * @since 1.9
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function addParameter( $key, $value ): void {
		if ( $key !== '' && $value !== '' ) {
			$this->parameters[$key][] = $value;
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 * @param array $values
	 */
	public function setParameter( $key, array $values ): void {
		if ( $key !== '' && $values !== [] ) {
			$this->parameters[$key] = $values;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$parameters
	 * @param bool $associative
	 */
	public static function sort( array &$parameters, $associative = true ): void {
		// Associative vs. simple index array sort
		if ( $associative ) {
			ksort( $parameters );
		} else {
			sort( $parameters );
		}

		foreach ( $parameters as $key => &$value ) {
			if ( is_array( $value ) ) {
				/** @phan-suppress-next-line PhanRedundantConditionInLoop */
				self::sort( $value, is_int( $key ) );
			}
		}
	}

	/**
	 * Map raw parameters array into an 2n-array for simplified
	 * via [key] => [value1, value2]
	 */
	private function doMap( array $params ): array {
		$results = [];
		$previousProperty = null;

		while ( key( $params ) !== null ) {

			$pipe = false;
			$display = null;
			$values = [];

			// Only strings are allowed for processing
			if ( !is_string( current( $params ) ) ) {
				next( $params );
			}

			// Get the current element and divide it into parts
			$currentElement = explode( '=', trim( current( $params ) ), 2 );

			// Looking to the next element for comparison
			$separator = $this->lookAheadOnNextElement( $params, $pipe, $display );

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

			if ( $display !== null ) {
				$this->displayOptions[$currentElement[0]] = $display;
			}
		}

		return $this->parseFromJson( $results );
	}

	private function lookAheadOnNextElement( &$params, bool &$pipe, ?string &$display ): string {
		$separator = '';

		if ( !next( $params ) ) {
			return $separator;
		}

		$sepConsumed = false;

		while ( key( $params ) !== null ) {
			$current = current( $params );
			$option = is_string( $current ) ? explode( '=', trim( $current ), 2 ) : [ '' ];

			if ( $this->captureDisplayOptions && $option[0] === '+display' ) {
				$display = trim( $option[1] ?? '' );
				next( $params );
				continue;
			}

			// This allows assignments of type |Has property=Test1,Test2|+sep=,
			// as a means to support multiple value declaration; +sep is never
			// consumed after +pipe, and at most once per assignment
			if ( !$sepConsumed && !$pipe && substr( $option[0], -5 ) === '+sep' ) {
				$separator = isset( $option[1] ) ? ( $option[1] !== '' ? $option[1] : $this->defaultSeparator ) : $this->defaultSeparator;
				$sepConsumed = true;
				next( $params );
				continue;
			}

			// +pipe is matched against the raw, untrimmed element
			if ( !$pipe && $current === '+pipe' ) {
				$pipe = true;
				next( $params );
				continue;
			}

			break;
		}

		return $separator;
	}

	private function parseFromJson( array $results ): array {
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

		array_walk( $params, static function ( &$value, $key ): void {
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
