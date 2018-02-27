<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Lru;

/**
 * @covers \SMW\Utils\Lru
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LruTest extends \PHPUnit_Framework_TestCase {

	public function testSetGet() {

		$instance = new Lru( 3 );

		$instance->set( 'a', 3 );
		$instance->set( 'b', 'abc' );
		$instance->set( 'c', 'def' );
		$instance->set( 'd', 2 );

		$this->assertEquals(
			[
				'b' => 'abc',
				'c' => 'def',
				'd' => 2
			],
			$instance->toArray()
		);

		$this->assertEquals(
			'def',
			$instance->get( 'c' )
		);

		$instance->get( 'b' );
		$instance->set( 'foo', 'bar' );

		$this->assertEquals(
			[
				'b' => 'abc',
				'c' => 'def',
				'foo' => 'bar'
			],
			$instance->toArray()
		);

		$instance->get( 'c' );
		$instance->get( 'foo' );
		$instance->set( 'foobar', 'xyz' );

		$this->assertEquals(
			[
				'c' => 'def',
				'foo' => 'bar',
				'foobar' => 'xyz'
			],
			$instance->toArray()
		);
	}

	public function testDelete() {

		$instance = new Lru( 3 );

		$instance->set( 'a', 3 );
		$instance->delete( 'a' );

		$this->assertEquals(
			[],
			$instance->toArray()
		);
	}

}
