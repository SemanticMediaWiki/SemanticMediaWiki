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

	public function testCountAndSeek_Associative() {

		$data = [
			'test_1' => [ 'Foo' => [ 'Foobar' ] ],
			'test_2' => [ 'Bar' => [] ]
		];

		$instance = new CompartmentIterator(
			$data
		);

		$this->assertCount(
			2,
			$instance
		);

		$instance->seek( 'test_1' );

		$this->assertTrue(
			$instance->current()->has( 'Foo' )
		);
	}

	public function testCountAndSeek_Associative_WhereValueIsNotAnArray() {

		$data = [
			'test_2' => 'Bar'
		];

		$instance = new CompartmentIterator(
			$data
		);

		$this->assertCount(
			1,
			$instance
		);

		$instance->seek( 'test_2' );

		$this->assertTrue(
			$instance->current()->has( 'test_2' )
		);
	}

	public function testSeekOnInvalidPositionThrowsException() {

		$instance = new CompartmentIterator();

		$this->setExpectedException( '\OutOfBoundsException' );
		$instance->seek( 0 );
	}

	public function testSeekOnInvalidPosition_Associative_ThrowsException() {

		$data = [
			'test_1' => [ 'Foo' => [ 'Foobar' ] ],
			'test_2' => [ 'Bar' => [] ]
		];

		$instance = new CompartmentIterator(
			$data
		);

		$this->setExpectedException( '\OutOfBoundsException' );
		$instance->seek( 'bar' );
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

	public function testIterate_Associatve() {

		$data = [
			'test_1' => [ 'Foo' => [ 'Foobar' ] ],
			'test_2' => [ 'Bar' => [] ]
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

	public function testFind() {

		$data = [
			'test_1' => [ 'Foo' => [ 'Foobar' ] ],
			'test_2' => [ 'Bar' => [] ]
		];

		$instance = new CompartmentIterator(
			$data
		);

		$compartmentIterator = $instance->find( 'Foo' );

		$this->assertInstanceOf(
			CompartmentIterator::class,
			$compartmentIterator
		);

		foreach ( $compartmentIterator as $compartment ) {
			$this->assertInstanceOf(
				'\SMW\Schema\Compartment',
				$compartment
			);
		}
	}

}
