<?php

namespace SMW\Tests\Iterators;

use SMW\Iterators\SeekableIteratorTrait;
use SMW\Iterators\DotSeekableIteratorTrait;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Iterators\DotSeekableIteratorTrait
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DotSeekableIteratorTraitTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testSeek_MultiAssociative_Dot() {

		$container = [
			'foo' => 1, 'bar' => [ 'foo' => [ 'foobar' => 42 ] ], 'foobar' => 1001
		];

		$instance = $this->newDotSeekableIterator(
			$container
		);

		$instance->seek( 'bar.foo.foobar' );

		$this->assertEquals(
			42,
			$instance->current()
		);
	}

	public function testSeek_MultiInstanceAssociative_Dot() {

		$container = [
			'foo' => $this->newDotSeekableIterator( [ 1 ] ),
			'bar' => $this->newDotSeekableIterator( [ 'foo' => [ 'foobar' => 42 ], 'foobar' => 1001 ] )
		];

		$instance = $this->newDotSeekableIterator(
			$container
		);

		$instance->seek( 'bar' );
		$bar = $instance->current();

		$bar->seek( 'foo.foobar' );

		$this->assertEquals(
			42,
			$bar->current()
		);
	}

	public function testInvalidSeekPositionThrowsException() {

		$instance = $this->newDotSeekableIterator();

		$this->expectException( 'OutOfBoundsException' );
		$instance->seek( 'foo' );
	}

	private function newDotSeekableIterator( array $container = [] ) {
		return new class( $container ) implements \Iterator, \Countable, \SeekableIterator {

			use DotSeekableIteratorTrait;

			public function __construct( array $container = [] ) {
				$this->container = $container;
			}
		};
	}

}
