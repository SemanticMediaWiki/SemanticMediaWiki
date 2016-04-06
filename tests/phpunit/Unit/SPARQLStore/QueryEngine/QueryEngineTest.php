<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMWQuery as Query;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$engineOptions = new EngineOptions();
		$engineOptions->set( 'smwgIgnoreQueryErrors', false );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store ),
			$engineOptions
		);

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

	public function testConditionBuilderReturnsErrors() {

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array( 'bogus-error' ) ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$engineOptions = new EngineOptions();
		$engineOptions->set( 'smwgIgnoreQueryErrors', false );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store ),
			$engineOptions
		);

		$query = new Query( $description );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);

		$this->assertEmpty(
			$instance->getQueryResult( $query )->getResults()
		);

		$this->assertEquals(
			array( 'bogus-error' ),
			$query->getErrors()
		);
	}

	public function testEmptyGetQueryResultWhereQueryModeIsNone() {

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store )
		);

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

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine( $connection, $compoundConditionBuilder, new QueryResultFactory( $store ) );

		$query = new Query( $description );
		$query->sortkeys = array( 'Foo', 'Bar' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testtestGetQueryResultForDebugQueryMode() {

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector' )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$queryResultFactory = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryResultFactory' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

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

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectCount' )
			->will( $this->returnValue( $repositoryResult ) );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_COUNT;

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);
	}

	public function testInstanceQueryResultForMockedCompostion() {

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->setMethods( array( 'select' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $repositoryResult ) );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);
	}

	public function testGetImmediateEmptyQueryResultForLimitLessThanOne() {

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->never() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->setUnboundLimit( -1 );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);
	}

	public function testInstanceQueryResultForMockedSingletonCompostion() {

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->setMethods( array( 'ask' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'ask' )
			->will( $this->returnValue( $repositoryResult ) );

		$element = $this->getMockBuilder( '\SMW\Exporter\Element' )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition' )
			->disableOriginalConstructor()
			->getMock();

		$condition->matchElement = $element;

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);
	}

	public function testDebugQueryResultForMockedCompostion() {

		// PHPUnit 3.7 goes drumming when trying to a method on an
		// interface hence the use of the concrete class
		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector' )
			->disableOriginalConstructor()
			->setMethods( array( 'getSparqlForSelect' ) )
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getSparqlForSelect' )
			->will( $this->returnValue( 'Foo' ) );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$compoundConditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->will( $this->returnValue( $compoundConditionBuilder ) );

		$compoundConditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->will( $this->returnValue( $condition ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$compoundConditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertInternalType(
			'string',
			$instance->getQueryResult( $query )
		);
	}

}
