<?php

namespace SMW\Test;

use SMW\ParserFunctionFactory;

use WikiPage;
use Parser;

/**
 * Tests for the ParserFunctionFactory class
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
 * @covers \SMW\ParserFunctionFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ParserFunctionFactoryTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ParserFunctionFactory';
	}

	/**
	 * Helper method that returns a ParserFunctionFactory object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return ParserFunctionFactory
	 */
	private function getInstance() {
		return ParserFunctionFactory::newFromParser( $this->getParser( $this->getTitle(), $this->getUser() ) );
	}

	/**
	 * @test ParserFunctionFactory::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ParserFunctionFactory::getSubobjectParser
	 *
	 * @since 1.9
	 */
	public function testGetSubobjectParser() {
		$this->assertInstanceOf( '\SMW\SubobjectParserFunction', $this->getInstance()->getSubobjectParser() );
	}

	/**
	 * @test ParserFunctionFactory::getRecurringEventsParser
	 *
	 * @since 1.9
	 */
	public function testGetRecurringEventsParser() {
		$this->assertInstanceOf( '\SMW\RecurringEventsParserFunction', $this->getInstance()->getRecurringEventsParser() );
	}
}
