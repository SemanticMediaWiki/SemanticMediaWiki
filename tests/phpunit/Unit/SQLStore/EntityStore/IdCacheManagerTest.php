<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\IdCacheManager;
use Onoi\Cache\FixedInMemoryLruCache;

/**
 * @covers \SMW\SQLStore\EntityStore\IdCacheManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class IdCacheManagerTest extends \PHPUnit_Framework_TestCase {

	private $caches;

 	protected function setUp() {

		$this->caches = [
			'entity.id' => new FixedInMemoryLruCache(),
			'entity.sort' => new FixedInMemoryLruCache()
		];
 	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IdCacheManager::class,
			new IdCacheManager( $this->caches )
		);
	}

	public function testComputeSha1() {

		$this->assertInternalType(
			'string',
			IdCacheManager::computeSha1( [] )
		);
	}

	public function testGet() {

		$instance = new IdCacheManager( $this->caches );

		$this->assertInstanceOf(
			FixedInMemoryLruCache::class,
			$instance->get( 'entity.sort' )
		);
	}

	public function testGetThrowsException() {

		$instance = new IdCacheManager( $this->caches );

		$this->setExpectedException( '\RuntimeException' );
		$instance->get( 'foo' );
	}

	public function testSetCache() {

		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', 42, 'bar' );

		$this->assertEquals(
			42,
			$instance->getId( [ 'foo', 0, '', '' ] )
		);

		$this->assertEquals(
			false,
			$instance->getId( [ 'foo', '0', '', '' ] )
		);

		$this->assertEquals(
			'bar',
			$instance->getSort( [ 'foo', 0, '', '' ] )
		);
	}

	public function testDeleteCache() {

		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$this->assertEquals(
			42,
			$instance->getId( [ 'foo', 0, '', '' ] )
		);

		$instance->deleteCache( 'foo', 0, '', '' );

		$this->assertEquals(
			false,
			$instance->getId( [ 'foo', '0', '', '' ] )
		);

		$this->assertEquals(
			false,
			$instance->getSort( [ 'foo', 0, '', '' ] )
		);
	}

	public function testHasCache() {

		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$this->assertEquals(
			false,
			$instance->hasCache( [ 'foo', 0, '', '' ] )
		);

		$this->assertEquals(
			true,
			$instance->hasCache( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);

	}

}
