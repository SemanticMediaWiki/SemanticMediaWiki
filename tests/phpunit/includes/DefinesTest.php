<?php

namespace SMW\Test;

/**
 * Tests for the global constants being loaded
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
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class DefinesTest extends \MediaWikiTestCase {

	/**
	 * dataProvider
	 */
	public function getConstants() {
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
	 *
	 * @covers constants
	 * @dataProvider getConstants
	 */
	public function testConstants( $constant, $result ) {
		$this->assertEquals( $result, $constant );
	}
}