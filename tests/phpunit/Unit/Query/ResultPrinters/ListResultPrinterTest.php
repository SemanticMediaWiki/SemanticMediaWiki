<?php
namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\ListResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\ListResultPrinter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 3.0
 */
class ListResultPrinterTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider allFormatsProvider
	 * @param string $format
	 */
	public function testCanConstruct( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$this->assertInstanceOf( '\SMW\Query\ResultPrinters\ListResultPrinter', $listResultPrinter );
	}

	/**
	 * @dataProvider listFormatProvider
	 * @param string $format
	 */
	public function whenFormatIsNotPlainThenColumnsParameterExists( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$definitions = $listResultPrinter->getParamDefinitions( [] );

		$this->assertArrayHasKey( 'columns', $definitions );
	}

	/**
	 * @dataProvider plainFormatProvider
	 * @param string $format
	 */
	public function whenFormatIsPlainThenColumnsParameterDoesNotExist( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$definitions = $listResultPrinter->getParamDefinitions( [] );

		$this->assertArrayNotHasKey( 'columns', $definitions );
	}

	public function listFormatProvider() {
		yield [ 'ul' ];
		yield [ 'ol' ];
	}

	public function plainFormatProvider() {
		yield [ 'template' ];
	}

	public function allFormatsProvider() {
		yield [ 'ul' ];
		yield [ 'ol' ];
		yield [ 'template' ];
	}
}
