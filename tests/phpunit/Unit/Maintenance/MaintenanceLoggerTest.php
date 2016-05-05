<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\MaintenanceLogger;

/**
 * @covers \SMW\Maintenance\MaintenanceLogger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MaintenanceLoggerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$manualEntryLogger = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceLogger',
			new MaintenanceLogger( 'Foo', $manualEntryLogger )
		);
	}

	public function testLog() {

		$manualEntryLogger = $this->getMockBuilder( '\SMW\MediaWiki\ManualEntryLogger' )
			->disableOriginalConstructor()
			->getMock();

		$manualEntryLogger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'maintenance' ),
				$this->equalTo( 'Foo' ),
				$this->equalTo( 'Foo' ),
				$this->stringContains( 'bar' ) );

		$instance = new MaintenanceLogger( 'Foo', $manualEntryLogger );
		$instance->log( 'bar' );
	}

}
