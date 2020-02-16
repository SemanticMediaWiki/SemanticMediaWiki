<?php

namespace SMW\Tests\Iterators;

use SMW\Iterators\SeekableIteratorTrait;
use SMW\Iterators\DotSeekableIteratorTrait;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Iterators\SeekableIteratorTrait
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SeekableIteratorTraitTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testIterate_Indexed() {

		$container = [
			1, 42, 1001
		];

		$instance = $this->newSeekableIterator(
			$container
		);

		$this->assertCount(
			3,
			$instance
		);

		$instance->seek( 2 );

		$this->assertEquals(
			1001,
			$instance->current()
		);

		foreach ( $instance as $key => $value ) {
			$this->assertEquals(
				$container[$key],
				$value
			);
		}
	}

	public function testIterate_Associative() {

		$container = [
			'foo' => 1, 'bar' => 42, 'foobar' => 1001
		];

		$instance = $this->newSeekableIterator(
			$container
		);

		$this->assertCount(
			3,
			$instance
		);

		$instance->seek( 'foobar' );

		$this->assertEquals(
			1001,
			$instance->current()
		);

		foreach ( $instance as $key => $value ) {
			$this->assertEquals(
				$container[$key],
				$value
			);
		}
	}

	public function testIterate_Mixed() {

		$container = [
			1, 'bar' => 42, 'foobar' => 1001
		];

		$instance = $this->newSeekableIterator(
			$container
		);

		$this->assertCount(
			3,
			$instance
		);

		$instance->seek( 'foobar' );

		$this->assertEquals(
			1001,
			$instance->current()
		);

		foreach ( $instance as $key => $value ) {
			$this->assertEquals(
				$container[$key],
				$value
			);
		}
	}

	public function testSeek_Indexed() {

		$container = [
			1, 42, 1001
		];

		$instance = $this->newSeekableIterator(
			$container
		);

		$instance->seek( 1 );

		$this->assertEquals(
			42,
			$instance->current()
		);
	}

	public function testSeek_Associative() {

		$container = [
			'foo' => 1, 'bar' => 42, 'foobar' => 1001
		];

		$instance = $this->newSeekableIterator(
			$container
		);

		$instance->seek( 'bar' );

		$this->assertEquals(
			42,
			$instance->current()
		);
	}

	public function testSeek_Mixed() {

		$container = [
			1, 'bar' => 42, 'foobar' => 1001
		];

		$instance = $this->newSeekableIterator(
			$container
		);

		$instance->seek( 'bar' );

		$this->assertEquals(
			42,
			$instance->current()
		);
	}

	public function testInvalidSeekPositionThrowsException() {

		$instance = $this->newSeekableIterator();

		$this->expectException( 'OutOfBoundsException' );
		$instance->seek( 'foo' );
	}

	private function newSeekableIterator( array $container = [] ) {
		return new class( $container ) implements \Iterator, \Countable, \SeekableIterator {

			use SeekableIteratorTrait;

			public function __construct( array $container = [] ) {
				$this->container = $container;
			}
		};
	}

}
