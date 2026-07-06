<?php

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\MaintenanceLogger;

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
		$this->assertInstanceOf(
			MaintenanceLogger::class,
			new MaintenanceLogger( 'Foo' )
		);
	}

	public function testLogWithInvalidNameLengthThrowsException() {
		$instance = new MaintenanceLogger( 'Foo' );
		$instance->setMaxNameChars( 2 );

		$this->expectException( 'RuntimeException' );
		$instance->log( 'bar' );
	}

}
