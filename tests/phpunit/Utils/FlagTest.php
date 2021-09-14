<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Flag;

/**
 * @covers \SMW\Utils\Flag
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FlagTest extends \PHPUnit_Framework_TestCase {

	public function testIs() {

		$instance = new Flag( 2 | 4 | 16 );

		$this->assertTrue(
			$instance->is( 0 )
		);

		$this->assertTrue(
			$instance->is( 2 )
		);

		$this->assertTrue(
			$instance->is( 16 )
		);

		$this->assertTrue(
			$instance->is( 2 | 16 )
		);

		$this->assertFalse(
			$instance->is( 3 )
		);
	}

	public function testNot() {

		$instance = new Flag( 2 | 4 | 16 );

		$this->assertFalse(
			$instance->not( 0 )
		);

		$this->assertFalse(
			$instance->not( 2 | 16 )
		);

		$this->assertTrue(
			$instance->not( 2 | 32 )
		);
	}

}
