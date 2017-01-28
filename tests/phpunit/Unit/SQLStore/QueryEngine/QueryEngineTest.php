<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryEngine;
use SMWQuery as Query;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $querySegmentListBuildManager;
	private $querySegmentListProcessor;
	private $engineOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListBuildManager = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager' )
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
			'\SMW\SQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $this->store, $this->querySegmentListBuildManager, $this->querySegmentListProcessor, $this->engineOptions )
		);
	}

	public function testGetQueryResultForDebugQueryMode() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->querySegmentListBuildManager->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$this->querySegmentListProcessor->expects( $this->any() )
			->method( 'getListOfResolvedQueries' )
			->will( $this->returnValue( array() ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$this->store,
			$this->querySegmentListBuildManager,
			$this->querySegmentListProcessor,
			$this->engineOptions
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertInternalType(
			'string',
			$instance->getQueryResult( $query )
		);
	}

	public function testGetImmediateEmptyQueryResultForLimitLessThanOne() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->never() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->querySegmentListBuildManager->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$this->store,
			$this->querySegmentListBuildManager,
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
