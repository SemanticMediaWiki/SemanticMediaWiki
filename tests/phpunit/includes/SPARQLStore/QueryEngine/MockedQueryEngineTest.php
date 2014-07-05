<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\SparqlResultConverter;

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

		$sparqlResultConverter = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\SparqlResultConverter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $connection, $sparqlConditionBuilder, $sparqlResultConverter )
		);
	}

	public function testGetSuccessCountQueryResultForMockedCompostion() {

		$sparqlResultWrapper = $this->getMockBuilder( '\SMWSparqlResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectCount' )
			->will( $this->returnValue( $sparqlResultWrapper ) );

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

		$instance = new QueryEngine( $connection, $sparqlConditionBuilder, new SparqlResultConverter( $store ) );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getCountQueryResult( new Query( $description ) )
		);
	}

}