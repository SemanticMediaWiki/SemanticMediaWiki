<?php

namespace SMW\Test;

use SMW\ParserFunctionFactory;

use WikiPage;
use Parser;

/**
 * Tests for the ParserFunctionFactory class
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
