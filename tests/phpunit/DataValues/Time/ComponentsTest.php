<?php

namespace SMW\Tests\DataValues\Time;

use SMW\DataValues\Time\Components;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\Time\Components
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ComponentsTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testPublicProperties() {
		$this->assertIsArray(

			Components::$months
		);

		$this->assertIsArray(

			Components::$monthsShort
		);
	}

	public function testGet() {
		$instance = new Components( [ 'foo' => 'bar' ] );

		$this->assertFalse(
						$instance->get( 'bar' )
		);

		$this->assertEquals(
			'bar',
			$instance->get( 'foo' )
		);
	}

}
