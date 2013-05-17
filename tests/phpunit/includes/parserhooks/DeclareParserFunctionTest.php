<?php

namespace SMW\Test;

use SMW\DeclareParserFunction;

/**
 * Tests for the DeclareParserFunction class
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
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the DeclareParserFunction class
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class DeclareParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\DeclareParserFunction';
	}

	/**
	 * @test DeclareParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = new DeclareParserFunction();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}
}
