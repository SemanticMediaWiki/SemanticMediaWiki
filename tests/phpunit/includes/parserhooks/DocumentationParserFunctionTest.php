<?php

namespace SMW\Tests;

use SMW\DocumentationParserFunction;

/**
 * @covers \SMW\DocumentationParserFunction
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class DocumentationParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DocumentationParserFunction',
			new DocumentationParserFunction()
		);
	}

	public function testStaticInit() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			DocumentationParserFunction::staticInit( $parser )
		);
	}

}
