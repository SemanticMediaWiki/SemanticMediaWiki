<?php

namespace SMW\Tests\Unit\DataValues\Time;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\Time\Components;

/**
 * @covers \SMW\DataValues\Time\Components
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ComponentsTest extends TestCase {

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
