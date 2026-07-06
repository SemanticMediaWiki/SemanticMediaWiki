<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\Query\Language\Description;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\QueryEngine\SubqueryQueryBuilder;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class QueryEngineTest extends TestCase {

	private $store;
	private $conditionBuilder;
	private $querySegmentListProcessor;
	private $engineOptions;
	private $subqueryQueryBuilder;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListProcessor = $this->getMockBuilder( QuerySegmentListProcessor::class )
			->disableOriginalConstructor()
			->getMock();

		$this->engineOptions = $this->getMockBuilder( EngineOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$this->subqueryQueryBuilder = $this->getMockBuilder( SubqueryQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryEngine::class,
			new QueryEngine( $this->store, $this->conditionBuilder, $this->querySegmentListProcessor, $this->engineOptions, $this->subqueryQueryBuilder )
		);
	}

	public function testGetQueryResultForDebugQueryMode() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$this->querySegmentListProcessor->expects( $this->any() )
			->method( 'getExecutedQueries' )
			->willReturn( [] );

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$this->store,
			$this->conditionBuilder,
			$this->querySegmentListProcessor,
			$this->engineOptions,
			$this->subqueryQueryBuilder
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertIsString(

			$instance->getQueryResult( $query )
		);
	}

	public function testGetImmediateEmptyQueryResultForLimitLessThanOne() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->never() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new QueryEngine(
			$this->store,
			$this->conditionBuilder,
			$this->querySegmentListProcessor,
			$this->engineOptions,
			$this->subqueryQueryBuilder
		);

		$query = new Query( $description );
		$query->setUnboundLimit( -1 );

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->getQueryResult( $query )
		);
	}

}
