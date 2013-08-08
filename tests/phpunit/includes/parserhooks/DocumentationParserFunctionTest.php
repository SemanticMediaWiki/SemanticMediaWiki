<?php

namespace SMW\Test;

use SMW\DocumentationParserFunction;

/**
 * Tests for the DocumentationParserFunction class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\DocumentationParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class DocumentationParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\DocumentationParserFunction';
	}

	/**
	 * @test DocumentationParserFunction::__construct
	 *
	 * @since 1.9
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
		$parser = $this->getParser( $this->newTitle(), $this->getUser() );
		$result = DocumentationParserFunction::staticInit( $parser );
		$this->assertTrue( $result );
	}
}
