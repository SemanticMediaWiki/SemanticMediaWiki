<?php

namespace SMW\Test;

use SMW\ParserFunctionFactory;

/**
 * @covers \SMW\ParserFunctionFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserFunctionFactoryTest extends ParserTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ParserFunctionFactory';
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserFunctionFactory
	 */
	private function newInstance() {
		return ParserFunctionFactory::newFromParser( $this->newParser( $this->newTitle(), $this->getUser() ) );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
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
