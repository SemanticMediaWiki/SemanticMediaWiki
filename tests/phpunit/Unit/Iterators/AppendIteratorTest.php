<?php

namespace SMW\Iterators\Tests;

use SMW\Iterators\AppendIterator;

/**
 * @covers \SMW\Iterators\AppendIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class AppendIteratorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AppendIterator::class,
			new AppendIterator()
		);
	}

	/**
	 * @dataProvider iterableProvider
	 */
	public function testCount( $iterable, $expected ) {

		$instance = new AppendIterator();
		$instance->add( $iterable );

		$this->assertEquals(
			$expected,
			$instance->count()
		);
	}

	public function testAddOnNonIterableThrowsException() {

		$instance = new AppendIterator();

		$this->setExpectedException( 'RuntimeException' );
		$instance->add( 'Foo' );
	}

	public function iterableProvider() {

		$provider[] = array(
			array(
				1, 42, 1001, 9999
			),
			4
		);

		$iterator = new AppendIterator();
		$iterator->add( [ 0 , 1 ] );

		$provider[] = array(
			$iterator,
			2
		);

		$iterator = new AppendIterator();
		$iterator->add( [ 0 , 1 ] );
		$iterator->add( $iterator );

		$provider[] = array(
			$iterator,
			4
		);

		return $provider;
	}

}
