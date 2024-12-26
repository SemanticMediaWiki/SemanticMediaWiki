<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use SMW\MediaWiki\Specials\Admin\Alerts\OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
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

		$this->assertContains(
			'smw-admin-alerts-outdates-entities',
			$instance->getHtml()
		);
	}

}
