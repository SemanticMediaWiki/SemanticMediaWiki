<?php

namespace SMW\Tests\Query\Cache;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Query\Cache\CacheStats;

/**
 * @covers \SMW\Query\Cache\CacheStats
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CacheStatsTest extends \PHPUnit_Framework_TestCase {

	private $cache;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CacheStats::class,
			new CacheStats( $this->cache, 42 )
		);
	}

	public function testGetStats() {

		$container = [
			'misses' => 1,
			'hits'   => [ 'Foo' => 2, [ 'Bar' => 2 ] ],
			'meta'   => 'foo'
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $container ) );

		$instance = new CacheStats(
			$this->cache,
			42
		);

		$stats = $instance->getStats();

		$this->assertEquals(
			[
				'hit'  => 0.8,
				'miss' => 0.2
			],
			$stats['ratio']
		);
	}

	public function testGetStatsEmpty() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new CacheStats(
			$this->cache,
			42
		);

		$this->assertEmpty(
			$instance->getStats()
		);
	}

}
