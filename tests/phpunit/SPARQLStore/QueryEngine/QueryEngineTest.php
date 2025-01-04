<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMWQuery as Query;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultFactory = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryResultFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $connection, $conditionBuilder, $queryResultFactory )
		);
	}

	public function testEmptyGetQueryResultWhereQueryContainsErrors() {
		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$engineOptions = new EngineOptions();
		$engineOptions->set( 'smwgIgnoreQueryErrors', false );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store ),
			$engineOptions
		);

		$query = new Query( $description );
		$query->addErrors( [ 'Foo' ] );

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

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [ 'bogus-error' ] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$engineOptions = new EngineOptions();
		$engineOptions->set( 'smwgIgnoreQueryErrors', false );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
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
			[ 'bogus-error' ],
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

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
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
		$sortKeys = [ 'Foo', 'Bar' ];

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'getSortKeys' )
			->willReturn( $sortKeys );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->setSortKeys( $sortKeys );

		$this->expectException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testtestGetQueryResultForDebugQueryMode() {
		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector' )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$queryResultFactory = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\QueryResultFactory' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine( $connection, $conditionBuilder, $queryResultFactory );

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertNotInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);

		$this->assertIsString(

			$instance->getQueryResult( $query )
		);
	}

	public function testGetSuccessCountQueryResultForMockedCompostion() {
		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectCount' )
			->willReturn( $repositoryResult );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$conditionBuilder->expects( $this->once() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
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
			->onlyMethods( [ 'select' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$conditionBuilder->expects( $this->once() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
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

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->never() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
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
			->onlyMethods( [ 'ask' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'ask' )
			->willReturn( $repositoryResult );

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

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$conditionBuilder->expects( $this->once() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
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
		// PHPUnit 3.7 goes drumming when trying to add a method on an
		// interface hence the use of the concrete class
		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getSparqlForSelect' ] )
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getSparqlForSelect' )
			->willReturn( 'Foo' );

		$condition = $this->getMockForAbstractClass( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$conditionBuilder->expects( $this->once() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertIsString(

			$instance->getQueryResult( $query )
		);
	}

}
