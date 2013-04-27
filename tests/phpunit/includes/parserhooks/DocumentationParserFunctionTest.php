<?php

namespace SMW\Test;

use SMW\DocumentationParserFunction;

/**
 * Tests for the SMW\DocumentationParserFunction class
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
class DocumentationParserFunctionTest extends ParserTestCase {

	// Will be extended in a follow-up

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\DocumentationParserFunction';
	}

	/**
	 * Test instance
	 *
	 */
	public function testConstructor() {
		$instance = new DocumentationParserFunction();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test DocumentationParserFunction::staticInit
	 *
	 * @since 1.9
	 */
	public function testStaticInit() {
		$parser = $this->getParser( $this->getTitle(), new MockSuperUser() );
		$result = DocumentationParserFunction::staticInit( $parser );
		$this->assertTrue( $result );
	}
}
