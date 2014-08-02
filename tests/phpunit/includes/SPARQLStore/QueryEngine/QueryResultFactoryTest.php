<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\Tests\Util\Mock\IteratorMockBuilder;

use SMW\SPARQLStore\QueryEngine\QueryResultFactory;

use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryResultFactory
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
class QueryResultFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryResultFactory',
			new QueryResultFactory( $store )
		);
	}

	/**
	 * @dataProvider sparqlResultListErrorCodeProvider
	 */
	public function testGetQueryResultObjectForCountQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$federateResultSet = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$federateResultSet->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_COUNT;

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->newQueryResult( $federateResultSet, $query )
		);

		$this->assertQueryResultErrorCodeForCountValue(
			$errorCode,
			$instance->newQueryResult( $federateResultSet, $query )
		);
	}

	/**
	 * @dataProvider sparqlResultListErrorCodeProvider
	 */
	public function testGetQueryResultObjectForEmptyInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$federateResultSet = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$federateResultSet->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->newQueryResult( $federateResultSet, $query )
		);

		$this->assertQueryResultErrorCode(
			$errorCode,
			$instance->newQueryResult( $federateResultSet, $query )
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

		$federateResultSet = $iteratorMockBuilder->setClass( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->with( array( array( $expElement ) ) )
			->getMockForIterator();

		$federateResultSet->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->will( $this->returnValue( $errorCode ) );

		$description = $this->getMockBuilder( '\SMWDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->newQueryResult( $federateResultSet, $query )
		);

		$this->assertQueryResultErrorCode(
			$errorCode,
			$instance->newQueryResult( $federateResultSet, $query )
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
