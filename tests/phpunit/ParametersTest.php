<?php

namespace SMW\Tests;

use SMW\Parameters;

/**
 * @covers \SMW\Parameters
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class ParametersTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Parameters::class,
			new Parameters()
		);
	}

	public function testAddOption() {
		$instance = new Parameters();

		$this->assertFalse(
			$instance->has( 'Foo' )
		);

		$instance->set( 'Foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'Foo' )
		);
	}

	public function testUnregisteredKeyThrowsException() {
		$instance = new Parameters();

		$this->expectException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

}
