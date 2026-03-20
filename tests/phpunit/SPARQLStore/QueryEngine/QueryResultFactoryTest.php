<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\Exporter\Element\ExpElement;
use SMW\Query\Language\Description;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\Store;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryResultFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class QueryResultFactoryTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			QueryResultFactory::class,
			new QueryResultFactory( $store )
		);
	}

	public function testGetQueryResultObjectForNullSet() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->newQueryResult( null, $query )
		);
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testGetQueryResultObjectForCountQuery( $errorCode ) {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$RepositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$RepositoryResult->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->willReturn( $errorCode );

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_COUNT;

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			QueryResult::class,
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
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$RepositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$RepositoryResult->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->willReturn( $errorCode );

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			QueryResult::class,
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
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$expElement = $this->getMockBuilder( ExpElement::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$iteratorMockBuilder = new IteratorMockBuilder();

		$repositoryResult = $iteratorMockBuilder->setClass( RepositoryResult::class )
			->with( [ [ $expElement ] ] )
			->getMockForIterator();

		$repositoryResult->expects( $this->atLeastOnce() )
			->method( 'getErrorCode' )
			->willReturn( $errorCode );

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$instance = new QueryResultFactory( $store );

		$this->assertInstanceOf(
			QueryResult::class,
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
		$this->assertIsInt( $queryResult->getCountValue() );
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
