<?php

namespace SMW\Test;

use SMW\StoreFactory;

use SMWSQLStore3;
use SMWQuery;

/**
 * @group SMW
 * @group SMWExtension
 * @group medium
 * @group SqlStore
 * @group IntegrationTest
 *
 * @license GNU GPL v2+
 * @since   1.9.2
 *
 * @author mwjames
 */
class QuerySourceIntegrationTest extends \PHPUnit_Framework_TestCase {

	protected $smwgQuerySources = array();

	protected function setUp() {

		$this->smwgQuerySources = $GLOBALS['smwgQuerySources'];

		$GLOBALS['smwgQuerySources'] = array(
			'foo' => 'SMW\Test\FakeQueryStore',
			'bar' => 'SMW\Test\NonExistentQueryStore'
		);

		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();

		StoreFactory::clear();
		$GLOBALS['smwgQuerySources'] = $this->smwgQuerySources;
	}

	public function testQueryProcessorWithDefaultSource() {

		$rawParams = array(
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=default'
		);

		$this->setupStore( 'default' );

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithValidSource() {

		$rawParams = array(
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=foo'
		);

		$this->setupStore( 'foo', 'SMW\Test\FakeQueryStore', 1 );

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithInvalidSource() {

		$this->setExpectedException( 'RuntimeException' );

		$rawParams = array(
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=bar'
		);

		$this->setupStore( 'bar', 'SMW\Test\NonExistentQueryStore' );

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryRoutingWithDefaultSource() {

		$printrequest = $this->getMockBuilder( 'SMWPrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( 'SMWDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getPrintrequests' )
			->will( $this->returnValue( array( $printrequest ) ) );

		$query = $this->getMockBuilder( 'SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$count = $this->setupStore( 'default' )
			->getQueryResult( $query )
			->getCount();

		$this->assertInternalType( 'integer', $count );
	}

	public function testQueryRoutingWithAnotherValidSource() {

		$query = $this->getMockBuilder( 'SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$count = $this->setupStore( 'foo', 'SMW\Test\FakeQueryStore', 1 )
			->getQueryResult( $query )
			->getCount();

		$this->assertInternalType( 'integer', $count );
	}

	public function testQueryRoutingWithInvalidSourceThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$query = $this->getMockBuilder( 'SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$count = $this->setupStore( 'bar', 'SMW\Test\NonExistentQueryStore' )
			->getQueryResult( $query )
			->getCount();

		$this->assertInternalType( 'integer', $count );
	}

	protected function setupStore( $source, $storeId = null, $expectedToRun = 0 ) {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->exactly( $expectedToRun ) )
			->method( 'getCount' )
			->will( $this->returnValue( 0 ) );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$store = StoreFactory::getStore( $storeId );

		if ( method_exists( $store, 'setQueryResult' ) ) {
			$store->setQueryResult( $queryResult );
		}

		return $store;
	}

	protected function makeQueryResultFromRawParameters( $rawParams ) {

		list( $query, $params ) = \SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			\SMWQueryProcessor::INLINE_QUERY,
			false
		);

		return \SMWQueryProcessor::getResultFromQuery(
			$query,
			$params,
			SMW_OUTPUT_WIKI,
			\SMWQueryProcessor::INLINE_QUERY
		);
	}

}

/**
 * FIXME One would wish to have a FakeStore but instead SMWSQLStore3 is used in
 * order to avoid to implement all abstract methods specified by SMW\Store
 */
class FakeQueryStore extends SMWSQLStore3 {

	protected $queryResult;

	public function setQueryResult( \SMWQueryResult $queryResult ) {
		$this->queryResult = $queryResult;
	}

	public function getQueryResult( SMWQuery $query ) {
		return $this->queryResult;
	}
}
