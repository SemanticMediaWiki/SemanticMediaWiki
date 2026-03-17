<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Tasks\TableStatisticsTask;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\TableStatisticsTask
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTaskTest extends TestCase {

	private $store;
	private $cache;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->cache = $this->getMockBuilder( Cache::class )
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
		$tableStatisticsLookup = $this->getMockBuilder( TableStatisticsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'service' )
			->with( 'TableStatisticsLookup' )
			->willReturn( $tableStatisticsLookup );

		$this->cache = $this->getMockBuilder( Cache::class )
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
