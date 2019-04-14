<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Stats;

/**
 * @covers \SMW\Utils\Stats
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class StatsTest extends \PHPUnit_Framework_TestCase {

	private $cache;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Stats::class,
			new Stats( $this->cache, 42 )
		);
	}

	public function testIncr() {

		$container = [
			'Foo.bar' => 10
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $container ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'Foo.bar' => 11 ] ) );

		$instance = new Stats(
			$this->cache,
			42
		);

		$instance->incr( 'Foo.bar' );
		$instance->saveStats();
	}

	public function testSet() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'Foo.bar' => 10 ] ) );

		$instance = new Stats(
			$this->cache,
			42
		);

		$instance->set( 'Foo.bar', 10 );
		$instance->saveStats();
	}

	public function testCalcMedian() {

		$container = [
			'Foo.bar' => 10
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $container ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'Foo.bar' => 7.5 ] ) );

		$instance = new Stats(
			$this->cache,
			42
		);

		$instance->calcMedian( 'Foo.bar', 5 );
		$instance->saveStats();
	}

	public function testStats_Simple() {

		$container = [
			'Foo' => 1,
			'Bar' => 1
		];

		$expected = [
			'Foo' => 1,
			'Bar' => 1
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $container ) );

		$instance = new Stats(
			$this->cache,
			42
		);

		$this->assertEquals(
			$expected,
			$instance->getStats()
		);
	}

	public function testStats_SimpleHierarchy() {

		$container = [
			'Foo.foobar' => 1,
			'Bar' => 1
		];

		$expected = [
			'Foo' => [ 'foobar' => 1 ],
			'Bar' => 1
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $container ) );

		$instance = new Stats(
			$this->cache,
			42
		);

		$this->assertEquals(
			$expected,
			$instance->getStats()
		);
	}

	public function testStats_ExtendedHierarchy() {

		$container = [
			'Foo.foobar' => 5,
			'Bar' => 1,
			'Foo.foobar.baz' => 1
		];

		$expected = [
			'Foo' => [ 'foobar' => [ 5, 'baz' => 1 ] ],
			'Bar' => 1
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $container ) );

		$instance = new Stats(
			$this->cache,
			42
		);

		$this->assertEquals(
			$expected,
			$instance->getStats()
		);
	}

}
