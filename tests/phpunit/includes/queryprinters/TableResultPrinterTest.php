<?php

namespace SMW\Test;

use ReflectionClass;
use SMW\DIWikiPage;
use SMW\TableResultPrinter;
use SMW\Tests\Utils\Mock\CoreMockObjectRepository;
use SMW\Tests\Utils\Mock\MockObjectBuilder;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * Tests for the TableResultPrinter class
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\TableResultPrinter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class TableResultPrinterTest extends QueryPrinterTestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\TableResultPrinter';
	}

	/**
	 * Helper method that returns a TableResultPrinter object
	 *
	 * @return TableResultPrinter
	 */
	private function newInstance( $parameters = [] ) {

		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'table';

		return $this->setParameters( new TableResultPrinter( $format ), $parameters );
	}

	/**
	 * @test TableResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test TableResultPrinter::getResultText
	 * @dataProvider standardTableDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetResultText(  $setup, $expected  ) {

		$instance  = $this->newInstance( $setup['parameters'] );
		$reflector = new ReflectionClass( '\SMW\TableResultPrinter' );

		$property = $reflector->getProperty( 'fullParams' );
		$property->setAccessible( true );
		$property->setValue( $instance, [] );

		$method = $reflector->getMethod( 'linkFurtherResults' );
		$method->setAccessible( true );
		$method->invoke( $instance, $setup['queryResult'] );

		$method = $reflector->getMethod( 'getResultText' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, $setup['queryResult'], $setup['outputMode'] );

		$this->assertInternalType(
			'string',
			$result,
			'assert that the result always returns a string'
		);

		$this->stringValidator->assertThatStringContains(
			$expected['matcher'],
			$result
		);
	}

	/**
	 * @return array
	 */
	public function standardTableDataProvider() {

		$mockBuilder = new MockObjectBuilder();
		$mockBuilder->registerRepository( new CoreMockObjectRepository() );

		$provider = [];

		$labels = [
			'pr-1' => 'PrintRequest-PageValue',
			'pr-2' => 'PrintRequest-NumberValue',
			'ra-1' => 'ResultArray-PageValue',
			'ra-2' =>  9001
		];

		$printRequests = [];

		$printRequests['pr-1'] = $mockBuilder->newObject( 'PrintRequest', [
			'getText' => $labels['pr-1']
		] );

		$printRequests['pr-2'] = $mockBuilder->newObject( 'PrintRequest', [
			'getText' => $labels['pr-2']
		] );

		$datItems = [];

		$datItems['ra-1'] = DIWikiPage::newFromTitle( Title::newFromText( $labels['ra-1'], NS_MAIN ) );
		$datItems['ra-2'] = $mockBuilder->newObject( 'DataItem', [ 'getSortKey' => $labels['ra-2'] ] );

		$dataValues = [];

		$dataValues['ra-1'] = $mockBuilder->newObject( 'DataValue', [
			'DataValueType'    => 'SMWWikiPageValue',
			'getTypeID'        => '_wpg',
			'getShortText'     => $labels['ra-1'],
			'getDataItem'      => $datItems['ra-1']
		] );

		$dataValues['ra-2'] = $mockBuilder->newObject( 'DataValue', [
			'DataValueType'    => 'SMWNumberValue',
			'getTypeID'        => '_num',
			'getShortText'     => $labels['ra-2'],
			'getDataItem'      => $datItems['ra-2']
		] );

		$resultArray = [];

		$resultArray['ra-1'] = $mockBuilder->newObject( 'ResultArray', [
			'getText'          => $labels['ra-1'],
			'getPrintRequest'  => $printRequests['pr-1'],
			'getNextDataValue' => $dataValues['ra-1'],
		] );

		$resultArray['ra-2'] = $mockBuilder->newObject( 'ResultArray', [
			'getText'          => $labels['ra-2'],
			'getPrintRequest'  => $printRequests['pr-2'],
			'getNextDataValue' => $dataValues['ra-2'],
		] );

		$queryResult = $mockBuilder->newObject( 'QueryResult', [
			'getPrintRequests'  => [ $printRequests['pr-1'], $printRequests['pr-2'] ],
			'getNext'           => [ $resultArray['ra-1'], $resultArray['ra-2'] ],
			'getQueryLink'      => new \SMWInfolink( true, 'Lala', 'Lula' ),
			'hasFurtherResults' => true
		] );

		// #0 standard table
		$parameters = [
			'headers'   => SMW_HEADERS_PLAIN,
			'class'     => 'tableClass',
			'format'    => 'table',
			'offset'    => 0,
			'transpose' => false
		];

		$matcher = [
			'<table class="tableClass">',
			'<th class="PrintRequest-PageValue">PrintRequest-PageValue</th>',
			'<th class="PrintRequest-NumberValue">PrintRequest-NumberValue</th>',
			'<tr data-row-number="1" class="row-odd">',
			'class="PrintRequest-PageValue smwtype_wpg">ResultArray-PageValue</td>',
			'<td data-sort-value="9001"',
			'class="PrintRequest-NumberValue smwtype_num">9001</td></tr>',
			'<tr class="smwfooter row-even">',
			'<td class="sortbottom">',
			'<span class="smw-table-furtherresults">'
		];

		$provider[] = [
			[
				'parameters'  => $parameters,
				'queryResult' => $queryResult,
				'outputMode'  => SMW_OUTPUT_FILE
			],
			[
				'matcher'     => $matcher
			]
		];

		// #1 broadtable table
		$parameters = [
			'headers'   => SMW_HEADERS_PLAIN,
			'class'     => 'tableClass',
			'format'    => 'broadtable',
			'offset'    => 0,
			'transpose' => false
		];

		$matcher = [
			'<table class="tableClass" width="100%">',
			'<th class="PrintRequest-PageValue">PrintRequest-PageValue</th>',
			'<th class="PrintRequest-NumberValue">PrintRequest-NumberValue</th>',
			'<tr class="smwfooter row-odd">',
			'<td class="sortbottom">',
			'<span class="smw-broadtable-furtherresults">'
		];

		$provider[] = [
			[
				'parameters'  => $parameters,
				'queryResult' => $queryResult,
				'outputMode'  => SMW_OUTPUT_FILE
			],
			[
				'matcher'     => $matcher
			]
		];

		// #2 "headers=hide"
		$parameters = [
			'headers'   => SMW_HEADERS_HIDE,
			'class'     => 'tableClass',
			'format'    => 'table',
			'offset'    => 0,
			'transpose' => false
		];

		$matcher = [
			'<table class="tableClass">',
			'<tr class="smwfooter row-odd">',
			'<td class="sortbottom">',
			'<span class="smw-table-furtherresults">'
		];

		$provider[] = [
			[
				'parameters'  => $parameters,
				'queryResult' => $queryResult,
				'outputMode'  => SMW_OUTPUT_FILE
			],
			[
				'matcher'     => $matcher
			]
		];

		// #3 "transpose=true"
		$parameters = [
			'headers'   => SMW_HEADERS_PLAIN,
			'class'     => 'tableClass',
			'format'    => 'table',
			'offset'    => 0,
			'transpose' => true
		];

		//TODO add proper matching data, which I can't seem to get to work.
		//MWJames would you mind doing the honors?
		$matcher = [];

		$provider[] = [
			[
				'parameters'  => $parameters,
				'queryResult' => $queryResult,
				'outputMode'  => SMW_OUTPUT_FILE
			],
			[
				'matcher'     => $matcher
			]
		];

		return $provider;

	}
}
