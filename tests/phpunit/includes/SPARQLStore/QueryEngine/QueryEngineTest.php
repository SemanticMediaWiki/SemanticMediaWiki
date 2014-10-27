<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;

use SMW\DIProperty;

use SMWQuery as Query;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryEngine
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultFactory = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryResultFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $connection, $compoundConditionBuilder, $queryResultFactory )
		);
	}

	public function testEmptyGetQueryResultWhereQueryContainsErrors() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$engineOptions = new EngineOptions();
		$engineOptions->ignoreQueryErrors = false;

		$instance = new QueryEngine( $connection, $compoundConditionBuilder, new QueryResultFactory( $store ), $engineOptions );

		$query = new Query( $description );
		$query->addErrors( array( 'Foo' ) );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);

		$this->assertEmpty(
			$instance->getQueryResult( $query )->getResults()
		);
	}

	public function testEmptyGetQueryResultWhereQueryModeIsNone() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $compoundConditionBuilder, new QueryResultFactory( $store ) );

		$query = new Query( $description );
		$query->querymode = Query::MODE_NONE;

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);

		$this->assertEmpty(
			$instance->getQueryResult( $query )->getResults()
		);
	}

	public function testInvalidSorkeyThrowsException() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $compoundConditionBuilder, new QueryResultFactory( $store ) );

		$query = new Query( $description );
		$query->sortkeys = array( 'Foo', 'Bar' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testGetQueryResultWhereQueryModeIsDebug() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$queryResultFactory = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryResultFactory' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $compoundConditionBuilder, $queryResultFactory );

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertNotInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);

		$this->assertInternalType(
			'string',
			$instance->getQueryResult( $query )
		);
	}

	public function testGetSuccessCountQueryResultForMockedCompostion() {

		$federateResultSet = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectCount' )
			->will( $this->returnValue( $federateResultSet ) );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $compoundConditionBuilder, new QueryResultFactory( $store ) );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getCountQueryResult( new Query( $description ) )
		);
	}

}
