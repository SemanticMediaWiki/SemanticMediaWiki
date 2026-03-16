<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\ManualEntryLogger;

/**
 * @covers \SMW\MediaWiki\ManualEntryLogger
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ManualEntryLoggerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ManualEntryLogger::class,
			new ManualEntryLogger()
		);
	}

	public function testLogToTableForNonLoggableEvent() {
		$instance = new ManualEntryLogger();

		$this->assertNull(
			$instance->log( 'Foo', 'Bar', 'Baz', 'Yui' )
		);
	}

	public function testRegisterLoggableEventType() {
		$manualLogEntry = $this->getMockBuilder( '\ManualLogEntry' )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry->expects( $this->once() )
			->method( 'insert' )
			->willReturn( 42 );

		$instance = new ManualEntryLogger( $manualLogEntry );
		$instance->registerLoggableEventType( 'Foo' );

		$this->assertEquals(
			42,
			$instance->log( 'Foo', 'Bar', 'Baz', 'Yui' )
		);
	}

	public function testLogToTableForLoggableEvent() {
		$manualLogEntry = $this->getMockBuilder( '\ManualLogEntry' )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry->expects( $this->once() )
			->method( 'insert' )
			->willReturn( 42 );

		$instance = $this->getMockBuilder( ManualEntryLogger::class )
			->setMethods( [ 'newManualLogEntryForType' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'newManualLogEntryForType' )
			->with( 'Foo' )
			->willReturn( $manualLogEntry );

		$instance->registerLoggableEventType( 'Foo' );

		$this->assertIsInt(

			$instance->log( 'Foo', 'Bar', 'Baz', 'Yui' )
		);
	}

	public function testLogToTableForLoggableEventWithPerformer() {
		$performer = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry = $this->getMockBuilder( '\ManualLogEntry' )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry->expects( $this->once() )
			->method( 'insert' )
			->willReturn( 42 );

		$instance = $this->getMockBuilder( ManualEntryLogger::class )
			->setMethods( [ 'newManualLogEntryForType' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'newManualLogEntryForType' )
			->with( 'Foo' )
			->willReturn( $manualLogEntry );

		$instance->registerLoggableEventType( 'Foo' );

		$this->assertIsInt(

			$instance->log( 'Foo', $performer, 'Baz', 'Yui' )
		);
	}

}
