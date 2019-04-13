<?php

namespace SMW\Tests\Elastic\Connection;

use SMW\Elastic\Connection\LockManager;
use SMW\Options;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Connection\LockManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LockManagerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $cache;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			LockManager::class,
			new LockManager( $this->cache )
		);
	}

	public function testHasMaintenanceLock() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->stringContains( 'smw:elastic:57cb773ae7a82c8c8aae12fa8f8d7abd' ) )
			->will( $this->returnValue( true ) );

		$instance = new LockManager(
			$this->cache
		);

		$instance->hasMaintenanceLock();
	}

	public function testSetMaintenanceLock() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:elastic:57cb773ae7a82c8c8aae12fa8f8d7abd' ) );

		$instance = new LockManager(
			$this->cache
		);

		$instance->setMaintenanceLock();
	}

	public function testSetLock() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->equalTo( 2 ) );

		$instance = new LockManager(
			$this->cache
		);

		$instance->setLock( 'foo', 2 );
	}

	public function testHasLock() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( '123' ) );

		$instance = new LockManager(
			$this->cache
		);

		$this->assertTrue(
			$instance->hasLock( 'foo', 2 )
		);
	}

	public function testGetLock() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( 2 ) );

		$instance = new LockManager(
			$this->cache
		);

		$this->assertEquals(
			2,
			$instance->getLock( 'foo' )
		);
	}

	public function testReleaseLock() {

		$this->cache->expects( $this->at( 0 ) )
			->method( 'delete' );

		$this->cache->expects( $this->at( 1 ) )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:elastic:57cb773ae7a82c8c8aae12fa8f8d7abd' ) );

		$instance = new LockManager(
			$this->cache
		);

		$instance->releaseLock( 'foo' );
	}

}
