<?php

namespace SMW\Tests\Utils;

use PHPUnit\Framework\TestCase;
use SMW\Utils\Timer;

/**
 * @covers \SMW\Utils\Timer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TimerTest extends TestCase {

	public function testGetElapsedTime() {
		Timer::start( __CLASS__ );

		$this->assertIsFloat(

			Timer::getElapsedTime( __CLASS__ )
		);

		$this->assertIsFloat(

			Timer::getElapsedTime( __CLASS__, 5 )
		);
	}

	public function testGetElapsedTimeWithoutStart() {
		$this->assertSame(
			0,
			Timer::getElapsedTime( 'Foo' )
		);
	}

	public function testGetElapsedTimeAsLoggableMessage() {
		$this->assertIsString(

			Timer::getElapsedTimeAsLoggableMessage( 'Foo' )
		);
	}

}
