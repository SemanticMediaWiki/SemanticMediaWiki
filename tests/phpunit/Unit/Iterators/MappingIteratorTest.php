<?php

namespace SMW\Tests\Iterators;

use ArrayIterator;
use SMW\Iterators\MappingIterator;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Iterators\MappingIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MappingIteratorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MappingIterator::class,
			new MappingIterator( [], function() {
			} )
		);
	}

	public function testInvalidConstructorArgumentThrowsException() {

		$this->setExpectedException( 'RuntimeException' );
		$instance = new MappingIterator( 2, function() {
		} );
	}

	public function testdoIterateOnArray() {

		$expected = [
			1 , 42
		];

		$mappingIterator = new MappingIterator( $expected, function( $counter ) {
			return $counter;
		} );

		foreach ( $mappingIterator as $key => $value ) {
			$this->assertEquals(
				$expected[$key],
				$value
			);
		}
	}

	public function testdoIterateOnArrayIterator() {

		$expected = [
			1001 , 42
		];

		$mappingIterator = new MappingIterator( new ArrayIterator( $expected ), function( $counter ) {
			return $counter;
		} );

		$this->assertCount(
			2,
			$mappingIterator
		);

		foreach ( $mappingIterator as $key => $value ) {
			$this->assertEquals(
				$expected[$key],
				$value
			);
		}
	}

}
