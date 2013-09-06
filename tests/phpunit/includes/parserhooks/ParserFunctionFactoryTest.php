<?php

namespace SMW\Test;

use SMW\ParserFunctionFactory;

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
	private function newInstance() {
		return ParserFunctionFactory::newFromParser( $this->newParser( $this->newTitle(), $this->getUser() ) );
	}

	/**
	 * @test ParserFunctionFactory::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test ParserFunctionFactory::getRecurringEventsParser
	 * @dataProvider parserFunctionDataProvider
	 *
	 * @since 1.9
	 */
	public function testParserFunction( $instance, $method ) {
		$this->assertInstanceOf( $instance, call_user_func_array( array( $this->newInstance(), $method ), array() ) );
	}

	/**
	 * @return array
	 */
	public function parserFunctionDataProvider() {

		$provider = array();

		$provider[] = array( '\SMW\RecurringEventsParserFunction', 'getRecurringEventsParser' );
		$provider[] = array( '\SMW\SubobjectParserFunction',       'getSubobjectParser' );

		return $provider;
	}

}
