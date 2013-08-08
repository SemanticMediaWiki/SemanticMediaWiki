<?php

namespace SMW\Test;

use SMW\InfoParserFunction;

/**
 * Tests for the InfoParserFunction class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\InfoParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class InfoParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\InfoParserFunction';
	}

	/**
	 * @test InfoParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = new InfoParserFunction();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test InfoParserFunction::staticInit
	 *
	 * @since 1.9
	 */
	public function testStaticInit() {
		$parser = $this->getParser( $this->newTitle(), $this->getUser() );
		$result = InfoParserFunction::staticInit( $parser );
		$this->assertTrue( $result );
	}
}
