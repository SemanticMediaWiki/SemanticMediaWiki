<?php

namespace SMW\Test;

use SMW\DeclareParserFunction;

/**
 * Tests for the DeclareParserFunction class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\DeclareParserFunction
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
