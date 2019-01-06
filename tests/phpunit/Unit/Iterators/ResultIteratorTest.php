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
class ResultIteratorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ResultIterator::class,
			new ResultIterator( [] )
		);
	}

	public function testInvalidConstructorArgumentThrowsException() {

		$this->setExpectedException( 'RuntimeException' );
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

		$resultWrapper = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->will( $this->returnValue( 1 ) );

		$resultWrapper->expects( $this->atLeastOnce() )
			->method( 'current' )
			->will( $this->returnValue( 42 ) );

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
