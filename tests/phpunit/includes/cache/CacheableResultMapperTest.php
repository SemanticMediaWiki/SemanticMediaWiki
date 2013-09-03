<?php

namespace SMW\Test;

use SMW\CacheableResultMapper;
use SMW\SimpleDictionary;

/**
 * Tests for the CacheableResultMapper class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\CacheableResultMapper
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class CacheableResultMapperTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CacheableResultMapper';
	}

	/**
	 * Helper method that returns a CacheableResultMapper object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return CacheableResultMapper
	 */
	private function newInstance( $cacheId = 'Foo', $cacheEnabled = true, $cacheExpiry = 10 ) {

		$setup = array(
			'id'      => $cacheId,
			'prefix'  => 'test',
			'type'    => 'hash',
			'enabled' => $cacheEnabled,
			'expiry'  => $cacheExpiry
		);

		return new CacheableResultMapper( new SimpleDictionary( $setup ) );
	}

	/**
	 * @test CacheableResultMapper::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test CacheableResultMapper::recache
	 * @test CacheableResultMapper::fetchFromCache
	 *
	 * @since 1.9
	 */
	public function testRoundTrip() {

		$id       = $this->getRandomString();
		$expected = array( $this->getRandomString(), $this->getRandomString() );
		$instance = $this->newInstance( $id, true, rand( 100, 200 ) );

		// Initial fetch(without any data present) must fail
		$result = $instance->fetchFromCache();
		$this->assertFalse( $result );
		$this->assertInternalType( 'null', $instance->getCacheDate() );

		// Cache object
		$instance->recache( $expected );

		// Re-fetch data from cache
		$result = $instance->fetchFromCache();

		$this->assertInternalType( 'array', $result );
		$this->assertInternalType( 'string', $instance->getCacheDate() );
		$this->assertEquals( $expected, $result );

	}

}
