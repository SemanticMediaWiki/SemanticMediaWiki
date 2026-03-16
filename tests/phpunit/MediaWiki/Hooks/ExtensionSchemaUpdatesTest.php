<?php

namespace SMW\Tests\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;
use SMW\Store;

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

	private $databaseUpdater;
	private $store;

	protected function setUp(): void {
		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExtensionSchemaUpdates::class,
			new ExtensionSchemaUpdates( $this->databaseUpdater )
		);
	}

	public function testProcess() {
		$this->databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->setMethods( [ 'addExtensionUpdate' ] )
			->getMockForAbstractClass();

		$this->databaseUpdater->expects( $this->once() )
			->method( 'addExtensionUpdate' );

		$instance = new ExtensionSchemaUpdates( $this->databaseUpdater );
		$instance->process( $this->store );
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
