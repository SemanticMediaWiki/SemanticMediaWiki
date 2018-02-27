<?php

namespace SMW\Iterators\Tests;

use SMW\Iterators\ChunkedIterator;

/**
 * @covers \SMW\Iterators\ChunkedIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChunkedIteratorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChunkedIterator::class,
			new ChunkedIterator( array() )
		);
	}

	public function testChunkedOnArray() {

		$result = array(
			1, 42, 1001, 9999
		);

		$instance = new ChunkedIterator( $result, 2 );

		foreach ( $instance as $chunk ) {
			$this->assertCount(
				2,
				$chunk
			);
		}

		$chunks = iterator_to_array( $instance, false );

		$this->assertEquals(
			[1, 42],
			$chunks[0]
		);

		$this->assertEquals(
			[1001, 9999],
			$chunks[1]
		);
	}

	public function testInvalidConstructorArgumentThrowsException() {

		$this->setExpectedException( 'RuntimeException' );
		$instance = new ChunkedIterator( 2 );
	}

	public function testInvalidChunkSizeArgumentThrowsException() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance = new ChunkedIterator( array(), -1 );
	}

}
