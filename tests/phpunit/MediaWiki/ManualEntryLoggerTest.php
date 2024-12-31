<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\ManualEntryLogger;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\ManualEntryLogger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ManualEntryLoggerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\ManualEntryLogger',
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

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->onlyMethods( [ 'newManualLogEntryForType' ] )
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
		$performer = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry = $this->getMockBuilder( '\ManualLogEntry' )
			->disableOriginalConstructor()
			->getMock();

		$manualLogEntry->expects( $this->once() )
			->method( 'insert' )
			->willReturn( 42 );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->onlyMethods( [ 'newManualLogEntryForType' ] )
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
