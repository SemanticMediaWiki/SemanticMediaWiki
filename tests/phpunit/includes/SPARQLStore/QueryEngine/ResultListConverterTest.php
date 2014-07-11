<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\Tests\Util\Mock\IteratorMockBuilder;

use SMW\SPARQLStore\QueryEngine\ResultListConverter;

use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ResultListConverter
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ResultListConverterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ResultListConverter',
			new ResultListConverter( $store )
		);
	}

	/**
	 * @dataProvider sparqlResultListErrorCodeProvider
	 */
	public function testGetQueryResultObjectForCountQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$federateResultList = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultList' )
			->disableOriginalConstructor()
			->getMock();

		$federateResultList->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_COUNT;

		$instance = new ResultListConverter( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->convertToQueryResult( $federateResultList, $query )
		);

		$this->assertQueryResultErrorCodeForCountValue(
			$errorCode,
			$instance->convertToQueryResult( $federateResultList, $query )
		);
	}

	/**
	 * @dataProvider sparqlResultListErrorCodeProvider
	 */
	public function testGetQueryResultObjectForEmptyInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$federateResultList = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultList' )
			->disableOriginalConstructor()
			->getMock();

		$federateResultList->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new ResultListConverter( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->convertToQueryResult( $federateResultList, $query )
		);

		$this->assertQueryResultErrorCode(
			$errorCode,
			$instance->convertToQueryResult( $federateResultList, $query )
		);
	}

	/**
	 * @dataProvider sparqlResultListErrorCodeProvider
	 */
	public function testGetQueryResultObjectForInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$expElement = $this->getMockBuilder( '\SMWExpElement' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$iteratorMockBuilder = new IteratorMockBuilder();

		$federateResultList = $iteratorMockBuilder->setClass( '\SMW\SPARQLStore\QueryEngine\FederateResultList' )
			->with( array( array( $expElement ) ) )
			->getMockForIterator();

		$federateResultList->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new ResultListConverter( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->convertToQueryResult( $federateResultList, $query )
		);

		$this->assertQueryResultErrorCode(
			$errorCode,
			$instance->convertToQueryResult( $federateResultList, $query )
		);
	}

	private function assertQueryResultErrorCodeForCountValue( $errorCode, QueryResult $queryResult ) {

		if ( $errorCode > 0 ) {
			$this->assertNotEmpty( $queryResult->getErrors() );
			return $this->assertNull( $queryResult->getCountValue() );
		}

		$this->assertEmpty( $queryResult->getErrors() );
		$this->assertInternalType( 'integer', $queryResult->getCountValue() );
	}

	private function assertQueryResultErrorCode( $errorCode, QueryResult $queryResult ) {

		if ( $errorCode > 0 ) {
			return $this->assertNotEmpty( $queryResult->getErrors() );
		}

		$this->assertEmpty( $queryResult->getErrors() );
	}

	public function sparqlResultListErrorCodeProvider() {

		$provider = array(
			array( 0 ),
			array( 1 ),
			array( 2 )
		);

		return $provider;
	}

}
