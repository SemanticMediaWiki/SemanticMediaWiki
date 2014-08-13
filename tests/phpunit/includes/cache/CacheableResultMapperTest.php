<?php

namespace SMW\Test;

use SMW\CacheableResultMapper;
use SMW\SimpleDictionary;

/**
 * @covers \SMW\CacheableResultMapper
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CacheableResultMapperTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CacheableResultMapper';
	}

	/**
	 * @since 1.9
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
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testRoundTrip() {

		$id       = $this->newRandomString();
		$expected = array( $this->newRandomString(), $this->newRandomString() );
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
