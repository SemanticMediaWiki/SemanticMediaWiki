<?php

namespace SMW\Tests\Iterators;

use SMW\Iterators\ResultIterator;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Iterators\ResultIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ResultIteratorTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultIterator::class,
			new ResultIterator( [] )
		);
	}

	public function testInvalidConstructorArgumentThrowsException() {
		$this->expectException( 'RuntimeException' );
		$instance = new ResultIterator( 2 );
	}

	public function testdoIterateOnArray() {
		$result = [
			1, 42
		];

		$instance = new ResultIterator( $result );

		$this->assertCount(
			2,
			$instance
		);

		foreach ( $instance as $key => $value ) {
			$this->assertEquals(
				$result[$key],
				$value
			);
		}

		$this->assertEquals(
			2,
			$instance->key()
		);
	}

	public function testdoSeekOnArray() {
		$result = [
			1, 42, 1001
		];

		$instance = new ResultIterator( $result );
		$instance->seek( 1 );

		$this->assertEquals(
			42,
			$instance->current()
		);
	}

	public function testdoIterateOnResultWrapper() {
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->exactly( 3 ) )
			->method( 'numRows' )
			->willReturn( 1 );

		$resultWrapper->expects( $this->atLeastOnce() )
			->method( 'current' )
			->willReturn( 42 );

		$instance = new ResultIterator( $resultWrapper );

		$this->assertCount(
			1,
			$instance
		);

		foreach ( $instance as $key => $value ) {
			$this->assertEquals(
				42,
				$value
			);
		}
	}

}
