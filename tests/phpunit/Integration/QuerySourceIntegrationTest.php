<?php

namespace SMW\Tests\Integration;

use SMW\StoreFactory;
use SMWQueryProcessor;

/**
 * @group SMW
 * @group SMWExtension
 * @group medium
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since   1.9.2
 *
 * @author mwjames
 */
class QuerySourceIntegrationTest extends \PHPUnit_Framework_TestCase {

	protected $smwgQuerySources = array();

	protected function setUp() {
		parent::setUp();

		$this->smwgQuerySources = $GLOBALS['smwgQuerySources'];

		$GLOBALS['smwgQuerySources'] = array(
			'foo' => 'SMW\Tests\Utils\Mock\FakeQueryStore',
			'bar' => 'SMW\Test\NonExistentQueryStore'
		);
	}

	protected function tearDown() {
		StoreFactory::clear();
		$GLOBALS['smwgQuerySources'] = $this->smwgQuerySources;

		parent::tearDown();
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

		$this->setupStore( 'foo', 1 );

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

		$this->setupStore( 'bar' );

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryRoutingWithDefaultSource() {

		$printrequest = $this->getMockBuilder( 'SMW\Query\PrintRequest' )
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

		$query->expects( $this->atLeastOnce() )
			->method( 'getLimit' )
			->will( $this->returnValue( 0 ) );

		$count = $this->setupStore( 'default' )
			->getQueryResult( $query )
			->getCount();

		$this->assertInternalType( 'integer', $count );
	}

	public function testQueryRoutingWithAnotherValidSource() {

		$query = $this->getMockBuilder( 'SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$count = $this->setupStore( 'foo', 1 )
			->getQueryResult( $query )
			->getCount();

		$this->assertInternalType( 'integer', $count );
	}

	public function testQueryRoutingWithInvalidSourceThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$query = $this->getMockBuilder( 'SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$count = $this->setupStore( 'bar' )
			->getQueryResult( $query )
			->getCount();

		$this->assertInternalType( 'integer', $count );
	}

	protected function setupStore( $source, $expectedToRun = 0 ) {

		$storeId = isset( $GLOBALS['smwgQuerySources'][$source] ) ? $GLOBALS['smwgQuerySources'][$source] : null;

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

		list( $query, $params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);

		return SMWQueryProcessor::getResultFromQuery(
			$query,
			$params,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY
		);
	}

}
