<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\Exporter\Element;
use SMW\Query\Language\Description;
use SMW\Query\QueryResult;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\SPARQLStore;
use SMW\Store;
use SMWQuery as Query;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class QueryEngineTest extends TestCase {

	public function testCanConstruct() {
		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResultFactory = $this->getMockBuilder( QueryResultFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			QueryEngine::class,
			new QueryEngine( $connection, $conditionBuilder, $queryResultFactory )
		);
	}

	public function testEmptyGetQueryResultWhereQueryContainsErrors() {
		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( Description::class );

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
			QueryResult::class,
			$instance->getQueryResult( $query )
		);

		$this->assertEmpty(
			$instance->getQueryResult( $query )->getResults()
		);
	}

	public function testConditionBuilderReturnsErrors() {
		$condition = $this->getMockForAbstractClass( Condition::class );

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [ 'bogus-error' ] );

		$conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'getConditionFrom' )
			->willReturn( $condition );

		$description = $this->getMockForAbstractClass( Description::class );

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
			QueryResult::class,
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
		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_NONE;

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);

		$this->assertEmpty(
			$instance->getQueryResult( $query )->getResults()
		);
	}

	public function testInvalidSorkeyThrowsException() {
		$sortKeys = [ 'Foo', 'Bar' ];

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$condition = $this->getMockForAbstractClass( Condition::class );

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$description = $this->getMockForAbstractClass( Description::class );

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
		$connection = $this->getMockBuilder( GenericRepositoryConnector::class )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockForAbstractClass( Condition::class );

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$queryResultFactory = $this->getMockBuilder( QueryResultFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine( $connection, $conditionBuilder, $queryResultFactory );

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertNotInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);

		$this->assertIsString(

			$instance->getQueryResult( $query )
		);
	}

	public function testGetSuccessCountQueryResultForMockedCompostion() {
		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( GenericRepositoryConnector::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectCount' )
			->willReturn( $repositoryResult );

		$condition = $this->getMockForAbstractClass( Condition::class );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_COUNT;

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

	public function testInstanceQueryResultForMockedCompostion() {
		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->setMethods( [ 'select' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$condition = $this->getMockForAbstractClass( Condition::class );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

	public function testGetImmediateEmptyQueryResultForLimitLessThanOne() {
		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( SPARQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->never() )
			->method( 'setSortKeys' )
			->willReturn( $conditionBuilder );

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->setUnboundLimit( -1 );

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

	public function testInstanceQueryResultForMockedSingletonCompostion() {
		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->setMethods( [ 'ask' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'ask' )
			->willReturn( $repositoryResult );

		$element = $this->getMockBuilder( Element::class )
			->disableOriginalConstructor()
			->getMock();

		$condition = $this->getMockBuilder( SingletonCondition::class )
			->disableOriginalConstructor()
			->getMock();

		$condition->matchElement = $element;

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$connection,
			$conditionBuilder,
			new QueryResultFactory( $store )
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_INSTANCES;

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

	public function testDebugQueryResultForMockedCompostion() {
		// PHPUnit 3.7 goes drumming when trying to add a method on an
		// interface hence the use of the concrete class
		$connection = $this->getMockBuilder( GenericRepositoryConnector::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getSparqlForSelect' ] )
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getSparqlForSelect' )
			->willReturn( 'Foo' );

		$condition = $this->getMockForAbstractClass( Condition::class );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$description = $this->getMockForAbstractClass( Description::class );

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
