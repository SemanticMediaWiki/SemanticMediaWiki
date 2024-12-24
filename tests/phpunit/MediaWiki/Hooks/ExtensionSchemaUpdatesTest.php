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
class ExtensionSchemaUpdatesTest extends \PHPUnit\Framework\TestCase {

	private $databaseUpdater;
	private $store;

	protected function setUp(): void {
		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
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
			->onlyMethods( [ 'addExtensionUpdate' ] )
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
