<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryEngine;
use SMWQuery as Query;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
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

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

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
