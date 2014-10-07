<?php

namespace SMW\Tests\Cache;

use SMW\Cache\InMemoryCache;

/**
 * @covers \SMW\Cache\InMemoryCache
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class InMemoryCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Cache\InMemoryCache',
			new InMemoryCache()
		);
	}

	public function testItemRemoval() {

		$instance = new InMemoryCache( 5 );

		$instance->set( 'foo', array( 'foo' ) );
		$instance->set( 42, null );

		$this->assertTrue( $instance->has( 'foo' ) );
		$this->assertTrue( $instance->has( 42 ) );

		$this->assertEquals(
			2,
			$instance->getCount()
		);

		$instance->delete( 'foo' );

		$this->assertFalse( $instance->has( 'foo' ) );

		$this->assertEquals(
			1,
			$instance->getCount()
		);
	}

	public function testReset() {

		$instance = new InMemoryCache( 5 );

		$instance->set( 'foo', array( 'foo' ) );
		$instance->set( 42, null );

		$this->assertEquals(
			2,
			$instance->getCount()
		);

		$instance->reset();

		$this->assertEquals(
			0,
			$instance->getCount()
		);
	}

	public function testLeastRecentlyUsedShift() {

		$instance = new InMemoryCache( 5 );
		$instance->set( 'berlin', array( 'berlin' ) );

		$this->assertEquals(
			array( 'berlin' ),
			$instance->get( 'berlin' )
		);

		foreach ( array( 'paris', 'london', '東京', '北京', 'new york' ) as $city ) {
			$instance->set( $city, array( $city ) );
		}

		// 'paris' was added and removes 'berlin' from the cache
		$this->assertFalse( $instance->get( 'berlin' ) );

		$this->assertEquals(
			5,
			$instance->getCount()
		);

		// 'paris' moves to the top (last postion as most recently used) and
		// 'london' becomes the next LRU candidate
		$this->assertEquals(
			array( 'paris' ),
			$instance->get( 'paris' )
		);

		$instance->set( 'rio', 'rio' );
		$this->assertFalse( $instance->get( 'london' ) );

		// 東京 would be next LRU slot but setting it again will move it to MRU
		// and push 北京 into the next LRU position
		$instance->set( '東京', '東京' );

		$instance->set( 'sidney', 'sidney' );
		$this->assertFalse( $instance->get( '北京' ) );

		$this->assertEquals(
			5,
			$instance->getCount()
		);
	}

}
