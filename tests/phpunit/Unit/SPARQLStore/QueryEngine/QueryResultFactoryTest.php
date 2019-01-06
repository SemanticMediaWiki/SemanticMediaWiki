<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryResultFactory
 * @group semantic-mediawiki
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

	public function testGetQueryResultObjectForNullSet() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->newQueryResult( null, $query )
		);
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testGetQueryResultObjectForCountQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$RepositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$RepositoryResult->expects( $this->atLeastOnce() )
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
			$instance->newQueryResult( $RepositoryResult, $query )
		);

		$this->assertQueryResultErrorCodeForCountValue(
			$errorCode,
			$instance->newQueryResult( $RepositoryResult, $query )
		);
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testGetQueryResultObjectForEmptyInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$RepositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$RepositoryResult->expects( $this->atLeastOnce() )
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
			$instance->newQueryResult( $RepositoryResult, $query )
		);

		$this->assertQueryResultErrorCode(
			$errorCode,
			$instance->newQueryResult( $RepositoryResult, $query )
		);
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testGetQueryResultObjectForInstanceQuery( $errorCode ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$expElement = $this->getMockBuilder( '\SMWExpElement' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$iteratorMockBuilder = new IteratorMockBuilder();

		$repositoryResult = $iteratorMockBuilder->setClass( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->with( [ [ $expElement ] ] )
			->getMockForIterator();

		$repositoryResult->expects( $this->atLeastOnce() )
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
			$instance->newQueryResult( $repositoryResult, $query )
		);

		$this->assertQueryResultErrorCode(
			$errorCode,
			$instance->newQueryResult( $repositoryResult, $query )
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

	public function errorCodeProvider() {

		$provider = [
			[ 0 ],
			[ 1 ],
			[ 2 ]
		];

		return $provider;
	}

}
