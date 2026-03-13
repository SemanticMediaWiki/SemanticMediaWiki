<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use SMW\MediaWiki\Specials\Admin\Alerts\ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler::class,
			new ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler( $this->store )
		);
	}

	public function testGetHtml() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( (object)[ 'count' => 50000 ] );

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler(
			$this->store
		);

		$this->assertStringContainsString(
			'smw-admin-alerts-invalid-entities',
			$instance->getHtml()
		);
	}

}
