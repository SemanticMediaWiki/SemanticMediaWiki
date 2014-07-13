<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\ResultListConverter;

use SMW\DIProperty;

use SMWQuery as Query;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryEngine
 *
 * @ingroup Test
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

		$queryConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$resultListConverter = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ResultListConverter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $connection, $queryConditionBuilder, $resultListConverter )
		);
	}

	public function testEmptyGetQueryResultWhereQueryContainsErrors() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$queryConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $queryConditionBuilder, new ResultListConverter( $store ) );
		$instance->setIgnoreQueryErrors( false );

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

		$queryConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $queryConditionBuilder, new ResultListConverter( $store ) );

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

	public function testGetQueryResultWhereQueryModeIsDebug() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$queryConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$queryConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $queryConditionBuilder ) );

		$queryConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$resultListConverter = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ResultListConverter' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $queryConditionBuilder, $resultListConverter );

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

		$federateResultList = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultList' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectCount' )
			->will( $this->returnValue( $federateResultList ) );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$queryConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$queryConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $queryConditionBuilder ) );

		$queryConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $queryConditionBuilder, new ResultListConverter( $store ) );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getCountQueryResult( new Query( $description ) )
		);
	}

}
