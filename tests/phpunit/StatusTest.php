<?php

namespace SMW\Tests;

use SMW\Status;

/**
 * @covers \SMW\Status
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class StatusTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Status::class,
			new Status()
		);
	}

	public function testHas() {

		$instance = new Status( [ 'Foo' => 123 ] );

		$this->assertTrue(
			$instance->has( 'Foo' )
		);
	}

	public function testIs() {

		$instance = new Status( [ 'Foo' => 123 ] );

		$this->assertTrue(
			$instance->is( 'Foo', 123 )
		);
	}

	public function testGet() {

		$instance = new Status( [ 'Foo' => 123 ] );

		$this->assertEquals(
			123,
			$instance->get( 'Foo' )
		);
	}

	public function testUnregisteredKeyThrowsException() {

		$instance = new Status();

		$this->expectException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

}
