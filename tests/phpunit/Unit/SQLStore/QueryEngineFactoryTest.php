<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\QueryEngineFactory;

/**
 * @covers \SMW\SQLStore\QueryEngineFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryEngineFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngineFactory',
			new QueryEngineFactory( $store )
		);
	}

	public function testCanConstructQuerySegmentListBuilder() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryEngineFactory( $store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder',
			$instance->newQuerySegmentListBuilder()
		);
	}

	public function testCanConstructQuerySegmentListProcessor() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new QueryEngineFactory( $store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor',
			$instance->newQuerySegmentListProcessor()
		);
	}

	public function testCanConstructQueryEngine() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new QueryEngineFactory( $store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryEngine',
			$instance->newQueryEngine()
		);
	}

}
