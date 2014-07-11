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
 * @since 1.9.2
 *
 * @author mwjames
 */
class MockedQueryEngineTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\SparqlConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$resultListConverter = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ResultListConverter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $connection, $sparqlConditionBuilder, $resultListConverter )
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

		$sparqlConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\SparqlConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $sparqlConditionBuilder ) );

		$sparqlConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMWDescription' );

		$instance = new QueryEngine( $connection, $sparqlConditionBuilder, new ResultListConverter( $store ) );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getCountQueryResult( new Query( $description ) )
		);
	}

}