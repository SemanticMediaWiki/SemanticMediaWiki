<?php

namespace SMW\Tests\DataValues\Time;

use SMW\DataValues\Time\Components;

/**
 * @covers \SMW\DataValues\Time\Components
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ComponentsTest extends \PHPUnit_Framework_TestCase {

	public function testPublicProperties() {

		$this->assertInternalType(
			'array',
			Components::$months
		);

		$this->assertInternalType(
			'array',
			Components::$monthsShort
		);
	}

	public function testGet() {

		$instance = new Components( [ 'foo' => 'bar' ] );

		$this->assertEquals(
			false,
			$instance->get( 'bar' )
		);

		$this->assertEquals(
			'bar',
			$instance->get( 'foo' )
		);
	}

}
