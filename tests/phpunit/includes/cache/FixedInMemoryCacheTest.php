<?php

namespace SMW\Tests\Cache;

use SMW\Cache\FixedInMemoryCache;

/**
 * @covers \SMW\Cache\FixedInMemoryCache
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixedInMemoryCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Cache\FixedInMemoryCache',
			new FixedInMemoryCache()
		);
	}

	public function testItemRemoval() {

		$instance = new FixedInMemoryCache( 5 );

		$instance->save( 'foo', array( 'foo' ) );
		$instance->save( 42, null );

		$this->assertTrue( $instance->contains( 'foo' ) );
		$this->assertTrue( $instance->contains( 42 ) );

		$stats = $instance->getStats();
		$this->assertEquals(
			2,
			$stats['count']
		);

		$instance->delete( 'foo' );

		$this->assertFalse( $instance->contains( 'foo' ) );
		$this->assertFalse( $instance->delete( 'foo' ) );

		$stats = $instance->getStats();
		$this->assertEquals(
			1,
			$stats['count']
		);
	}

	public function testLeastRecentlyUsedShiftForLimitedCacheSize() {

		$instance = new FixedInMemoryCache( 5 );
		$instance->save( 'berlin', array( 'berlin' ) );

		$this->assertEquals(
			array( 'berlin' ),
			$instance->fetch( 'berlin' )
		);

		foreach ( array( 'paris', 'london', '東京', '北京', 'new york' ) as $city ) {
			$instance->save( $city, array( $city ) );
		}

		// 'paris' was added and removes 'berlin' from the cache
		$this->assertFalse( $instance->fetch( 'berlin' ) );

		$stats = $instance->getStats();
		$this->assertEquals(
			5,
			$stats['count']
		);

		// 'paris' moves to the top (last postion as most recently used) and
		// 'london' becomes the next LRU candidate
		$this->assertEquals(
			array( 'paris' ),
			$instance->fetch( 'paris' )
		);

		$instance->save( 'rio', 'rio' );
		$this->assertFalse( $instance->fetch( 'london' ) );

		// 東京 would be the next LRU slot but setting it again will move it to MRU
		// and push 北京 into the next LRU position
		$instance->save( '東京', '東京' );

		$instance->save( 'sidney', 'sidney' );
		$this->assertFalse( $instance->fetch( '北京' ) );

		$stats = $instance->getStats();
		$this->assertEquals(
			5,
			$stats['count']
		);
	}

}
