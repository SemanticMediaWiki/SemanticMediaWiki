<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\TableResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\TableResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TableResultPrinterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableResultPrinter::class,
			new TableResultPrinter( 'table' )
		);
	}

	public function testGetResult_Empty() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$instance = new TableResultPrinter( 'table' );

		$this->assertInternalType(
			'string',
			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

}
