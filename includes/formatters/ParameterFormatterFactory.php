<?php

namespace SMW;

/**
 * Factory class handling parameter formatting instances
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
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Factory class handling parameter formatting instances
 *
 * @ingroup Formatter
 */
class ParameterFormatterFactory {

	/**
	 * Returns an ArrayFormatter instance
	 *
	 * @since 1.9
	 *
	 * @param array $rawParams
	 *
	 * @return ArrayFormatter
	 */
	public static function newFromArray( array $rawParams ) {

		if ( isset( $rawParams[0] ) && is_object( $rawParams[0] ) ) {
			array_shift( $rawParams );
		}

		//$formatter = JsonParameterFormatter::newFromArray( $rawParams );

		//if ( $formatter->isJson() ) {
		//	$instance = $formatter;
		//} else {
		//	$instance = new ParserParameterFormatter( $rawParams );
		//}

		$instance = new ParserParameterFormatter( $rawParams );

		return $instance;
	}
}
