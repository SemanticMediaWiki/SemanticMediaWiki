<?php

namespace SMW\Test;

use SMW\EmptyContext;
use SMW\StoreFactory;
use ApiResult;

/**
 * @covers \SMW\Api\Query
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class QueryTest extends ApiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Api\Query';
	}

	/**
	 * @since 1.9
	 *
	 * @return Api\Query
	 */
	private function newInstance( ApiResult $apiResult = null, $store = null ) {

		$context = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );

		$query = $this->getMockBuilder( $this->getClass() )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getResult' )
			->will( $this->returnValue( $apiResult ) );

		$query->expects( $this->any() )
			->method( 'withContext' )
			->will( $this->returnValue( $context ) );

		return $query;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testQueryAndQueryResultOnSQLStore() {

		$instance  = $this->newInstance( null, StoreFactory::getStore( 'SMWSQLStore3' ) );

		// Query object
		$reflector = $this->newReflector();
		$getQuery  = $reflector->getMethod( 'getQuery' );
		$getQuery->setAccessible( true );
		$query = $getQuery->invoke( $instance, '[[Modification date::+]]', array(), array() );

		$this->assertInstanceOf( 'SMWQuery', $query );

		// QueryResult object
		$getQueryResult = $reflector->getMethod( 'getQueryResult' );
		$getQueryResult->setAccessible( true );

		$result = $getQueryResult->invoke( $instance, $query );

		$this->assertInstanceOf( 'SMWQueryResult', $result );

	}

	/**
	 * @since 1.9
	 */
	public function testAddQueryResultOnMockStore() {

		$store = $this->newMockBuilder()->newObject( 'Store' );

		// Minimalistic test case to verify executability
		// For a full coverage, use Api\QueryResultFormatterTest
		$test = array(
			'results' => array(
				'Foo' => array(
					'printouts' => array( 'lula' => array( 'lila' ) )
				)
			),
			'printrequests' => array( 'Bar' ),
			'meta' => array( 'count' => 5, 'offset' => 5 )
		);

		$apiResult   = $this->getApiResult( array() );
		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'toArray'           => $test,
			'getErrors'         => array(),
			'hasFurtherResults' => true
		) );

		// Access protected method
		$reflector = $this->newReflector();
		$method = $reflector->getMethod( 'addQueryResult' );
		$method->setAccessible( true );

		$instance = $this->newInstance( $apiResult, $store );
		$method->invoke( $instance, $queryResult );

		// Test against the invoked ApiResult, as the addQueryResult method
		// does not return any actual results
		$this->assertInternalType( 'array', $apiResult->getData() );
		$this->assertEquals( array( 'query' => $test, 'query-continue-offset' => 10 ), $apiResult->getData() );

	}

}
