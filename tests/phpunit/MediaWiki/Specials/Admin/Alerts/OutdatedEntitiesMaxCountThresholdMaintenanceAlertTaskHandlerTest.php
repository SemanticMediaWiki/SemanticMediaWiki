<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Specials\Admin\Alerts\OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandlerTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler::class,
			new OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler( $this->store )
		);
	}

	public function testGetHtml() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( (object)[ 'count' => 50000 ] );

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler(
			$this->store
		);

		$this->assertStringContainsString(
			'smw-admin-alerts-outdates-entities',
			$instance->getHtml()
		);
	}

}
