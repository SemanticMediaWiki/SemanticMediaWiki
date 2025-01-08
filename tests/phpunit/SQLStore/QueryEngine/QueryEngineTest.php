<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\Tests\PHPUnitCompat;
use SMWQuery as Query;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $conditionBuilder;
	private $querySegmentListProcessor;
	private $engineOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListProcessor = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor' )
			->disableOriginalConstructor()
			->getMock();

		$this->engineOptions = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\EngineOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryEngine::class,
			new QueryEngine( $this->store, $this->conditionBuilder, $this->querySegmentListProcessor, $this->engineOptions )
		);
	}

	public function testGetQueryResultForDebugQueryMode() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder->expects( $this->any() )
			->method( 'select' )
			->willReturn( $queryBuilder );

		$queryBuilder->expects( $this->any() )
			->method( 'from' )
			->willReturn( $queryBuilder );

		$queryBuilder->expects( $this->any() )
			->method( 'where' )
			->willReturn( $queryBuilder );

		$queryBuilder->expects( $this->any() )
			->method( 'distinct' )
			->willReturn( $queryBuilder );

		$queryBuilder->expects( $this->any() )
			->method( 'caller' )
			->willReturn( $queryBuilder );

		// Mock getSQL() for debug mode
		$queryBuilder->expects( $this->any() )
			->method( 'getSQL' )
			->willReturn( 'SELECT * FROM test' );

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$connection->expects( $this->any() )
			->method( 'applySqlOptions' )
			->willReturn( $queryBuilder );

		// Mock isType() for EXPLAIN query formatting
		$connection->expects( $this->any() )
			->method( 'isType' )
			->willReturn( false );

		// Mock query() for EXPLAIN
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\IResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->any() )
			->method( 'free' )
			->willReturn( true );

		$connection->expects( $this->any() )
			->method( 'query' )
			->willReturn( $resultWrapper );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$this->conditionBuilder->expects( $this->once() )
			->method( 'buildCondition' )
			->willReturn( 1 );

		$querySegment = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegment' )
			->disableOriginalConstructor()
			->getMock();

		$querySegment->joinTable = 'smw_object_ids';
		$querySegment->alias = 'table1';
		$querySegment->joinfield = 'smw_id';
		$querySegment->where = '';

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getQuerySegmentList' )
			->willReturn( [ 1 => $querySegment ] );

		$this->querySegmentListProcessor->expects( $this->any() )
			->method( 'getExecutedQueries' )
			->willReturn( [] );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$this->store,
			$this->conditionBuilder,
			$this->querySegmentListProcessor,
			$this->engineOptions
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertIsString(
			$instance->getQueryResult( $query )
		);
	}

	public function testGetImmediateEmptyQueryResultForLimitLessThanOne() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->never() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$this->store,
			$this->conditionBuilder,
			$this->querySegmentListProcessor,
			$this->engineOptions
		);

		$query = new Query( $description );
		$query->setUnboundLimit( -1 );

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$instance->getQueryResult( $query )
		);
	}
}
