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
class DuplicateLookupTaskTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $cache;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
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
			->will( $this->returnValue( false ) );

		$this->cache->expects( $this->once() )
			->method( 'save' );

		$entityTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$entityTable->expects( $this->atLeastOnce() )
			->method( 'findDuplicates' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityTable ) );

		$instance = new DuplicateLookupTask(
			$store,
			$this->cache
		);

		$instance->process( [] );
	}

}
