<?php

namespace SMW\Tests\Unit\Query\Result;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\DataValues\DataValue;
use SMW\Query\PrintRequest;
use SMW\Query\Result\ResultArray;
use SMW\Store;

/**
 * @covers \SMW\Query\Result\ResultArray
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class ResultArrayTest extends TestCase {

	private function newResultArrayReturning( Time $dataItem, ?WikiPage $contextPage ): ResultArray {
		$store = $this->createMock( Store::class );

		$printRequest = $this->createMock( PrintRequest::class );
		$printRequest->method( 'getMode' )->willReturn( PrintRequest::PRINT_THIS );
		$printRequest->method( 'isMode' )->willReturn( false );
		$printRequest->method( 'getOutputFormat' )->willReturn( 'LOCL#TO' );

		$resultPage = WikiPage::doUnserialize( 'Result#0#' );

		$resultArray = $this->getMockBuilder( ResultArray::class )
			->setConstructorArgs( [ $resultPage, $printRequest, $store ] )
			->onlyMethods( [ 'getNextDataItem' ] )
			->getMock();

		$resultArray->method( 'getNextDataItem' )->willReturn( $dataItem );
		$resultArray->setContextPage( $contextPage );

		return $resultArray;
	}

	public function testEmbeddedQuerySetsDeferLocalTimeOption() {
		$dataItem = Time::doUnserialize( '1/2016/05/08/00/00/00/00' );
		$contextPage = WikiPage::doUnserialize( 'Context#0#' );

		$dataValue = $this->newResultArrayReturning( $dataItem, $contextPage )->getNextDataValue();

		$this->assertTrue(
			$dataValue->getOption( DataValue::OPT_DEFER_LOCAL_TIME )
		);
	}

	public function testNonEmbeddedQueryDoesNotSetDeferLocalTimeOption() {
		$dataItem = Time::doUnserialize( '1/2016/05/08/00/00/00/00' );

		$dataValue = $this->newResultArrayReturning( $dataItem, null )->getNextDataValue();

		// Strict false (the getOption default), confirming the option was not
		// set at all rather than merely resolving to a falsy value.
		$this->assertSame(
			false,
			$dataValue->getOption( DataValue::OPT_DEFER_LOCAL_TIME )
		);
	}
}
