<?php

namespace SMW\Tests;

use SMW\InfoParserFunction;

/**
 * @covers \SMW\InfoParserFunction
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class InfoParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\InfoParserFunction',
			new InfoParserFunction()
		);
	}

	public function testStaticInit() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			InfoParserFunction::staticInit( $parser )
		);
	}

}
