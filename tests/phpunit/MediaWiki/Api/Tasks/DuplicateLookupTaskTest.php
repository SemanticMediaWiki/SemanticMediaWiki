<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\DuplicateLookupTask;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\DuplicateLookupTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DuplicateLookupTaskTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $cache;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
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
		$instance = new DuplicateLookupTask( $this->store, $this->cache );

		$this->assertInstanceOf(
			DuplicateLookupTask::class,
			$instance
		);
	}

	public function testProcess() {
		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->cache->expects( $this->once() )
			->method( 'save' );

		$entityTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityTable->expects( $this->atLeastOnce() )
			->method( 'findDuplicates' )
			->willReturn( [] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $entityTable );

		$instance = new DuplicateLookupTask(
			$store,
			$this->cache
		);

		$instance->process( [] );
	}

}
