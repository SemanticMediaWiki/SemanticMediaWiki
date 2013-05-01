<?php

namespace SMW\Test;

/**
 * Tests for global constants being loaded
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
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for global constants being loaded
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class DefinesTest extends  SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|boolean
	 */
	public function getClass() {
		return false;
	}

	/**
	 * Provides sample of constants to be tested
	 *
	 * @return array
	 */
	public function getConstantsDataProvider() {
		return array(
			array( SMW_HEADERS_SHOW, 2 ),
			array( SMW_HEADERS_PLAIN, 1 ),
			array( SMW_HEADERS_HIDE, 0 ),
			array( SMW_OUTPUT_HTML, 1 ),
			array( SMW_OUTPUT_WIKI, 2 ),
			array( SMW_OUTPUT_FILE, 3 ),
			array( SMW_FACTBOX_HIDDEN, 1 ),
			array( SMW_FACTBOX_SPECIAL, 2 ),
			array( SMW_FACTBOX_NONEMPTY, 3 ),
			array( SMW_FACTBOX_SHOWN, 5 ),
		) ;
	}

	/**
	 * Test if constants are accessible
	 * @dataProvider getConstantsDataProvider
	 *
	 * @param $constant
	 * @param $expected
	 */
	public function testConstants( $constant, $expected ) {
		$this->assertEquals( $expected, $constant );
	}
}