<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager;

/**
 * @covers \SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QuerySegmentListBuildManagerTest extends \PHPUnit_Framework_TestCase {

	private $connection;
	private $querySegmentListBuilder;
	private $orderCondition;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->orderCondition = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\OrderCondition' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			QuerySegmentListBuildManager::class,
			new QuerySegmentListBuildManager( $this->connection, $this->querySegmentListBuilder, $this->orderCondition )
		);
	}

	public function testGetQuerySegmentFrom() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$this->querySegmentListBuilder->expects( $this->once() )
			->method( 'getQuerySegmentFrom' );

		$this->orderCondition->expects( $this->once() )
			->method( 'apply' );

		$instance = new QuerySegmentListBuildManager(
			$this->connection,
			$this->querySegmentListBuilder,
			$this->orderCondition
		);

		$instance->getQuerySegmentFrom( $query );
	}

}
