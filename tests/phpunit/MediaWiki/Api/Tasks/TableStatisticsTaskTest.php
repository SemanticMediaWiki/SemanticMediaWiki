<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\TableStatisticsTask;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\TableStatisticsTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTaskTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $cache;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new TableStatisticsTask( $this->store, $this->cache );

		$this->assertInstanceOf(
			TableStatisticsTask::class,
			$instance
		);
	}

	public function testProcess() {
		$tableStatisticsLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\TableStatisticsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'service' )
			->with( 'TableStatisticsLookup' )
			->willReturn( $tableStatisticsLookup );

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->cache->expects( $this->once() )
			->method( 'save' );

		$instance = new TableStatisticsTask(
			$this->store,
			$this->cache
		);

		$instance->process( [] );
	}

}
