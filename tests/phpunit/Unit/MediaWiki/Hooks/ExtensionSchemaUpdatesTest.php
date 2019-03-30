<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;

/**
 * @covers \SMW\MediaWiki\Hooks\ExtensionSchemaUpdates
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionSchemaUpdatesTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ExtensionSchemaUpdates',
			new ExtensionSchemaUpdates( $databaseUpdater )
		);
	}

	public function testProcess() {

		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->setMethods( [ 'addExtensionUpdate' ] )
			->getMockForAbstractClass();

		$databaseUpdater->expects( $this->once() )
			->method( 'addExtensionUpdate' );

		$instance = new ExtensionSchemaUpdates( $databaseUpdater );
		$instance->process();
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
