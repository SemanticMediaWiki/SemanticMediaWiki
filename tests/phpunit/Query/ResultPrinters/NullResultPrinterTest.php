<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\NullResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\NullResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NullResultPrinterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NullResultPrinter::class,
			new NullResultPrinter( '' )
		);
	}

	public function testGetResult_Empty() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$instance = new NullResultPrinter( '' );

		$this->assertEmpty(
			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

}
