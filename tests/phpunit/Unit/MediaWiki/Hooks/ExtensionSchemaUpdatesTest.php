<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use DatabaseUpdater;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;

/**
 * @covers \SMW\MediaWiki\Hooks\ExtensionSchemaUpdates
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionSchemaUpdatesTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExtensionSchemaUpdates::class,
			new ExtensionSchemaUpdates()
		);
	}

	public function testProcess() {
		$databaseUpdater = $this->getMockBuilder( DatabaseUpdater::class )
			->disableOriginalConstructor()
			->setMethods( [ 'addExtensionUpdate', 'output' ] )
			->getMockForAbstractClass();

		$databaseUpdater->expects( $this->once() )
			->method( 'addExtensionUpdate' );

		$instance = new ExtensionSchemaUpdates();
		$instance->onLoadExtensionSchemaUpdates( $databaseUpdater );
	}

	public function testAddMaintenanceUpdateParams() {
		$params = [];

		ExtensionSchemaUpdates::addMaintenanceUpdateParams(
			$params
		);

		$this->assertArrayHasKey(
			'skip-optimize',
			$params
		);
	}

}
