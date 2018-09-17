<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\ManualEntryLogger;

/**
 * @covers \SMW\MediaWiki\ManualEntryLogger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ManualEntryLoggerTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( 42 ) );

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
			->will( $this->returnValue( 42 ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->setMethods( [ 'newManualLogEntryForType' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'newManualLogEntryForType' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( $manualLogEntry ) );

		$instance->registerLoggableEventType( 'Foo' );

		$this->assertInternalType(
			'integer',
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
			->will( $this->returnValue( 42 ) );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->setMethods( [ 'newManualLogEntryForType' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'newManualLogEntryForType' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( $manualLogEntry ) );

		$instance->registerLoggableEventType( 'Foo' );

		$this->assertInternalType(
			'integer',
			$instance->log( 'Foo', $performer, 'Baz', 'Yui' )
		);
	}

}
