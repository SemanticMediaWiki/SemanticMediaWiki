<?php

namespace SMW\Tests\Cache;

use SMW\Cache\InMemoryCache;

/**
 * @uses \SMW\Cache\InMemoryCache
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class InMemoryCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Cache\InMemoryCache',
			new InMemoryCache
		);
	}

	public function testSetSingleEntry() {

		$instance = new InMemoryCache;

		$this->assertTrue( $instance->isSafe() );

		$this->assertFalse( $instance->has( 'foo' ) );
		$this->assertFalse( $instance->get( 'foo' ) );

		$instance->set( 'foo', 'bar' );
		$this->assertEquals( 'bar', $instance->get( 'foo' ) );
	}

	public function testSetNullValue() {

		$instance = new InMemoryCache;

		$instance->set( 'foo', null );

		$this->assertTrue( $instance->has( 'foo' ) );
		$this->assertNull( $instance->get( 'foo' ) );
	}

	public function testSetValueWithNonExpiredCacheTime() {

		$instance = new InMemoryCache;

		$instance->set( 'foo', 'bar', 100000 );

		$this->assertTrue( $instance->has( 'foo' ) );
		$this->assertEquals( 'bar', $instance->get( 'foo' ) );
	}

	public function testSetValueWithExpiredCacheTime() {

		$instance = new InMemoryCache;

		$instance->set( 'foo', 'bar', -10 );

		$this->assertTrue( $instance->has( 'foo' ) );
		$this->assertFalse( $instance->get( 'foo' ) );

		$this->assertFalse(
			$instance->has( 'foo' ),
			'Asserts that once a key has been detected as expired, it is also removed'
		);
	}

	public function testBulkThatIsSetToHaveNoExpiry() {

		$instance = new InMemoryCache( array( 'foo' => 'bar', 'bar' => null ) );

		$this->assertEquals( 'bar', $instance->get( 'foo' ) );
		$this->assertEquals(  null, $instance->get( 'bar' ) );
	}

	public function testDeleteEntry() {

		$instance = new InMemoryCache( array( 'foo' => 'bar') );

		$this->assertFalse( $instance->delete( 'bar' ) );
		$this->assertTrue( $instance->delete( 'foo' ) );
	}

}
