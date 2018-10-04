<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\CategoryResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\CategoryResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CategoryResultPrinterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CategoryResultPrinter::class,
			new CategoryResultPrinter( 'category' )
		);
	}

	public function testGetResult_Empty() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$instance = new CategoryResultPrinter( 'category' );

		$this->assertInternalType(
			'string',
			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

}
