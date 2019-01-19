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

	public function testSetLock() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->will( $this->returnValue( '' ) );

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

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->will( $this->returnValue( 2 ) );

		$instance = new LockManager(
			$this->cache
		);

		$instance->releaseLock( 'foo' );
	}

}
