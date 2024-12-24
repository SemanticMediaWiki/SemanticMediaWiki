<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use SMW\MediaWiki\Specials\Admin\Alerts\MaintenanceAlertsTaskHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\MaintenanceAlertsTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class MaintenanceAlertsTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MaintenanceAlertsTaskHandler::class,
			new MaintenanceAlertsTaskHandler( [] )
		);
	}

	public function testGetHtml() {
		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getHtml' ] )
			->getMockForAbstractClass();

		$taskHandler->expects( $this->once() )
			->method( 'getHtml' )
			->willReturn( 'FOO' );

		$instance = new MaintenanceAlertsTaskHandler(
			[
				$taskHandler
			]
		);

		$this->assertContains(
			'FOO',
			$instance->getHtml()
		);
	}

}
