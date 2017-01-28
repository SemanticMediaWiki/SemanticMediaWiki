<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager;
use SMW\SQLStore\QueryEngine\QuerySegment;

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
	private $qrderConditionsComplementor;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->qrderConditionsComplementor = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\OrderConditionsComplementor' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager',
			new QuerySegmentListBuildManager( $this->connection, $this->querySegmentListBuilder, $this->qrderConditionsComplementor )
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

		$this->qrderConditionsComplementor->expects( $this->once() )
			->method( 'applyOrderConditions' );

		$instance = new QuerySegmentListBuildManager(
			$this->connection,
			$this->querySegmentListBuilder,
			$this->qrderConditionsComplementor
		);

		$instance->getQuerySegmentFrom( $query );
	}

}
