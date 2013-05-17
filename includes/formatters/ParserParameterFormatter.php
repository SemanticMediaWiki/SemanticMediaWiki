<?php

namespace SMW;

/**
 * Interface for handling parameter formatting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup Formatters
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
interface IParameterFormatter {

	// Will also be used for QueryParameterFormatter

	/**
	 * The constructor requires an array of raw parameters
	 */

	/**
	 * Returns transformed parameters
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray();

	/**
	 * Returns raw parameters
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getRaw();

}

/**
 * Handling raw parameters from the parser hook
 *
 * @ingroup Formatters
 */
class ParserParameterFormatter implements IParameterFormatter {

	/**
	 * Returns a default separator
	 * @var string
	 */
	protected $defaultSeparator = ',';

	/**
	 * Returns first named identifier
	 * @var array
	 */
	protected $rawParameters;

	/**
	 * Returns first named identifier
	 * @var array
	 */
	protected $parameters;

	/**
	 * Returns first named identifier
	 * @var string
	 */
	protected $first = null;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param array $parameters
	 */
	public function __construct( array $parameters ) {
		$this->rawParameters = $parameters;
		$this->parameters = $this->map( $this->rawParameters );
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
	 * Map parameters from an external source independent from the invoked
	 * constructor
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function mapParameters( array $params ) {
		return $this->map( $params );
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
	protected function map( array $params ) {
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