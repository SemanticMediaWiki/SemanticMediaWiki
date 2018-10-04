<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Timer;

/**
 * @covers \SMW\Utils\Timer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TimerTest extends \PHPUnit_Framework_TestCase {

	public function testGetElapsedTime() {

		Timer::start( __CLASS__ );

		$this->assertInternalType(
			'float',
			Timer::getElapsedTime( __CLASS__ )
		);

		$this->assertInternalType(
			'float',
			Timer::getElapsedTime( __CLASS__, 5 )
		);
	}

	public function testGetElapsedTimeWithoutStart() {

		$this->assertEquals(
			0,
			Timer::getElapsedTime( 'Foo' )
		);
	}

	public function testGetElapsedTimeAsLoggableMessage() {

		$this->assertInternalType(
			'string',
			Timer::getElapsedTimeAsLoggableMessage( 'Foo' )
		);
	}

}
