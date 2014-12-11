<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\Logger;

/**
 * @covers \SMW\MediaWiki\Logger
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class LoggerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Logger',
			new Logger()
		);
	}

	public function testLog() {

		$instance = new Logger();

		$this->assertNull(
			$instance->log( 'Foo', 'Bar' )
		);
	}

	public function testLogToTableForNonLoggableEvent() {

		$instance = new Logger();

		$this->assertNull(
			$instance->logToTable( 'Foo', 'Bar', 'Baz', 'Yui' )
		);
	}

	public function testLogToTableForLoggableEvent() {

		$manualLogEntry = $this->getMockBuilder( '\ManualLogEntry' )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry->expects( $this->once() )
			->method( 'insert' )
			->will( $this->returnValue( 42 ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\Logger' )
			->setMethods( array( 'newManualLogEntryForType' ) )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'newManualLogEntryForType' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( $manualLogEntry ) );

		$instance->registerLoggableEventTypes( array( 'Foo' => true ) );

		$this->assertInternalType(
			'integer',
			$instance->logToTable( 'Foo', 'Bar', 'Baz', 'Yui' )
		);
	}

}
