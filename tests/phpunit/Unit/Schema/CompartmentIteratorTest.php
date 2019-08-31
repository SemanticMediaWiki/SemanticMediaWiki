<?php

namespace SMW\Tests\Schema;

use SMW\Schema\CompartmentIterator;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Schema\CompartmentIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CompartmentIteratorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CompartmentIterator::class,
			new CompartmentIterator( [] )
		);
	}

	public function testCountAndSeek() {

		$data = [
			[ 'Foo' => [ 'Foobar' ] ],
			[ 'Bar' => [] ],
		];

		$instance = new CompartmentIterator(
			$data
		);

		$this->assertCount(
			2,
			$instance
		);

		$instance->seek( 0 );

		$this->assertTrue(
			$instance->current()->has( 'Foo' )
		);
	}

	public function testSeekOnInvalidPositionThrowsException() {

		$instance = new CompartmentIterator();

		$this->setExpectedException( '\OutOfBoundsException' );
		$instance->seek( 0 );
	}

	public function testIterate() {

		$data = [
			[ 'Foo' => [ 'Foobar' ] ],
			[ 'Bar' => [] ],
		];

		$instance = new CompartmentIterator(
			$data
		);

		foreach ( $instance as $compartment ) {
			$this->assertInstanceOf(
				'\SMW\Schema\Compartment',
				$compartment
			);
		}
	}

}
