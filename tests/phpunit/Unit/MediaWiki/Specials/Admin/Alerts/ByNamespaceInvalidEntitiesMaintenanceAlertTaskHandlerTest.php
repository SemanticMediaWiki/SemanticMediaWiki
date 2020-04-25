<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use SMW\MediaWiki\Specials\Admin\Alerts\ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp() : void {
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

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)[ 'count' => 50000 ] ) );

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler(
			$this->store
		);

		$this->assertContains(
			'smw-admin-alerts-invalid-entities',
			$instance->getHtml()
		);
	}

}
