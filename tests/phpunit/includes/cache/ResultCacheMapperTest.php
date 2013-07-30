<?php

namespace SMW\Test;

use SMW\ResultCacheMapper;
use SMW\ArrayAccessor;

/**
 * Tests for the ResultCacheMapper class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ResultCacheMapper
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ResultCacheMapperTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ResultCacheMapper';
	}

	/**
	 * Helper method that returns a ResultCacheMapper object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return ResultCacheMapper
	 */
	private function getInstance( $cacheId = 'Foo', $cacheEnabled = true, $cacheExpiry = 10 ) {

		$setup = array(
			'id'      => $cacheId,
			'prefix'  => 'test',
			'type'    => 'hash',
			'enabled' => $cacheEnabled,
			'expiry'  => $cacheExpiry
		);

		return new ResultCacheMapper( new ArrayAccessor( $setup ) );
	}

	/**
	 * @test ResultCacheMapper::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ResultCacheMapper::recache
	 * @test ResultCacheMapper::fetchFromCache
	 *
	 * @since 1.9
	 */
	public function testRoundTrip() {

		$id       = $this->getRandomString();
		$expected = array( $this->getRandomString(), $this->getRandomString() );
		$instance = $this->getInstance( $id, true, rand( 100, 200 ) );

		// Initial fetch(without any data present) must fail
		$result = $instance->fetchFromCache();
		$this->assertFalse( $result );
		$this->assertInternalType( 'null', $instance->getCacheDate() );

		// Invoke object
		$instance->recache( $expected );

		// Re-fetch data from cache
		$result = $instance->fetchFromCache();

		$this->assertInternalType( 'array', $result );
		$this->assertInternalType( 'string', $instance->getCacheDate() );
		$this->assertEquals( $expected, $result );
	}
}
