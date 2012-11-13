<?php

namespace SMW;

/**
 * Parser parameter utility class
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
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class ParserParameter {

	/**
	 * Returns a default separator
	 *
	 * @since 1.9
	 *
	 * @var string
	 */
	protected $defaultSeparator = ',';

	/**
	 * Returns an instance of the current class
	 *
	 * @since 1.9
	 *
	 * @return ParserParameter
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Returns remapped parameters
	 *
	 * @since 1.9
	 *
	 * @param array $param
	 *
	 * @return array
	 */
	public function getParameters( array $params ) {
		$results = array();
		$previousProperty = null;

		while ( key( $params ) !== null ) {
			$separator = '';
			$values = array();

			// Get the current element
			$currentElement = explode( '=', trim( current ( $params ) ), 2 );

			// Looking to the next element for comparison
			if( next( $params ) ) {
				$nextElement = explode( '=', trim( current( $params ) ), 2 );

				if ( $nextElement !== array() ) {
					// This allows assignments of type |Has property=Test1,Test2|+sep=,
					// as a means to support mutliple value declaration
					if ( substr( $nextElement[0], - 5 ) === '+sep' ) {
						$separator = isset( $nextElement[1] ) ? $nextElement[1] !== '' ? $nextElement[1] : $this->defaultSeparator : $this->defaultSeparator;
						next( $params );
					}
				}
			}

			// Here we allow to support assignments of type |Has property=Test1|Test2|Test3
			// for mutliple values with the same preceeding property
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

			// Remap properties and values to ouput a simple array
			foreach ( $values as $value ) {
				if ( $value !== '' ){
					$results[ $currentElement[0] ][] = $value;
				}
			}
		}
	return $results;
	}
}