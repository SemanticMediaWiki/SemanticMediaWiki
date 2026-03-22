<?php

namespace SMW\Tests\Unit\Iterators;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use SMW\Iterators\MappingIterator;
use TypeError;

/**
 * @covers \SMW\Iterators\MappingIterator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class MappingIteratorTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MappingIterator::class,
			new MappingIterator( [], static function () {
			} )
		);
	}

	public function testInvalidConstructorArgumentThrowsException() {
		$this->expectException( TypeError::class );
		$instance = new MappingIterator( 2, static function () {
		} );
	}

	public function testdoIterateOnArray() {
		$expected = [
			1, 42
		];

		$mappingIterator = new MappingIterator( $expected, static function ( $counter ) {
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
			1001, 42
		];

		$mappingIterator = new MappingIterator( new ArrayIterator( $expected ), static function ( $counter ) {
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
