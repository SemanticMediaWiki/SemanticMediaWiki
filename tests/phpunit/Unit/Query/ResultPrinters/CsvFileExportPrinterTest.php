<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\DataValue;
use SMW\Formatters\Infolink;
use SMW\Query\PrintRequest;
use SMW\Query\QueryResult;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\CsvFileExportPrinter;
use SMW\Query\ResultPrinters\ResultPrinter;
use SMW\Utils\Csv;

/**
 * @covers \SMW\Query\ResultPrinters\CsvFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CsvFileExportPrinterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CsvFileExportPrinter::class,
			new CsvFileExportPrinter( 'csv' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new CsvFileExportPrinter( 'csv' );

		$this->assertIsString(

			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

	public function testLink() {
		$link = $this->getMockBuilder( Infolink::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getQueryLink' )
			->willReturn( $link );

		$queryResult->expects( $this->any() )
			->method( 'getCount' )
			->willReturn( 1 );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new CsvFileExportPrinter( 'csv' );
		$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI );
	}

	public function testMarkupFieldSeparatorCannotInjectMarkupIntoExportedOutput() {
		$output = $this->export( '"><img src=x onerror=alert(1)>' );

		// The exported CSV reduces the field separator to a single character, so a
		// markup separator cannot emit an HTML tag into the text/csv output.
		$this->assertStringNotContainsString( '<img', $output );
		$this->assertStringContainsString( 'ValA', $output );
		$this->assertStringContainsString( 'ValB', $output );
	}

	public function testBenignFieldSeparatorRoundTrips() {
		$output = $this->export( ',' );

		$this->assertStringContainsString( 'ValA,ValB', $output );
	}

	private function export( string $sep ): string {
		$instance = new CsvFileExportPrinter( 'csv' );

		$params = new \ReflectionProperty( ResultPrinter::class, 'params' );
		$params->setAccessible( true );
		$params->setValue( $instance, [ 'sep' => $sep, 'valuesep' => ',', 'merge' => false ] );

		$method = new \ReflectionMethod( CsvFileExportPrinter::class, 'getCsv' );
		$method->setAccessible( true );

		return (string)$method->invoke( $instance, new Csv( false, false ), $this->newQueryResult() );
	}

	private function newQueryResult(): QueryResult {
		$queryResult = $this->createMock( QueryResult::class );
		$queryResult->method( 'getPrintRequests' )->willReturn(
			[ $this->newPrintRequest( 'H1' ), $this->newPrintRequest( 'H2' ) ]
		);
		$queryResult->method( 'getNext' )->willReturnOnConsecutiveCalls(
			[ $this->newField( 'ValA' ), $this->newField( 'ValB' ) ],
			false
		);

		return $queryResult;
	}

	private function newPrintRequest( string $label ): PrintRequest {
		$printRequest = $this->createMock( PrintRequest::class );
		$printRequest->method( 'getLabel' )->willReturn( $label );

		return $printRequest;
	}

	private function newField( string $value ): ResultArray {
		$dataValue = $this->createMock( DataValue::class );
		$dataValue->method( 'getShortWikiText' )->willReturn( $value );

		$field = $this->createMock( ResultArray::class );
		$field->method( 'getNextDataValue' )->willReturnOnConsecutiveCalls( $dataValue, false );

		return $field;
	}

}
