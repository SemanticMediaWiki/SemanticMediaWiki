<?php

namespace SMW\Tests\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\MaintenanceLogger;
use SMW\MediaWiki\ManualEntryLogger;

/**
 * @covers \SMW\Maintenance\MaintenanceLogger
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class MaintenanceLoggerTest extends TestCase {

	public function testCanConstruct() {
		$manualEntryLogger = $this->getMockBuilder( ManualEntryLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			MaintenanceLogger::class,
			new MaintenanceLogger( 'Foo', $manualEntryLogger )
		);
	}

	public function testLog() {
		$manualEntryLogger = $this->getMockBuilder( ManualEntryLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$manualEntryLogger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'maintenance' ),
				'Foo',
				'Foo',
				$this->stringContains( 'bar' ) );

		$instance = new MaintenanceLogger( 'Foo', $manualEntryLogger );
		$instance->log( 'bar' );
	}

	public function testLogWithInvalidNameLengthThrowsException() {
		$manualEntryLogger = $this->getMockBuilder( ManualEntryLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$manualEntryLogger->expects( $this->never() )
			->method( 'log' );

		$instance = new MaintenanceLogger( 'Foo', $manualEntryLogger );
		$instance->setMaxNameChars( 2 );

		$this->expectException( 'RuntimeException' );
		$instance->log( 'bar' );
	}

}
