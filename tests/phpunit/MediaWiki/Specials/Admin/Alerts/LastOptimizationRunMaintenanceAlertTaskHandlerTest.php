<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Alerts;

use SMW\MediaWiki\Specials\Admin\Alerts\LastOptimizationRunMaintenanceAlertTaskHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\LastOptimizationRunMaintenanceAlertTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class LastOptimizationRunMaintenanceAlertTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $setupFile;

	protected function setUp() : void {
		parent::setUp();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			LastOptimizationRunMaintenanceAlertTaskHandler::class,
			new LastOptimizationRunMaintenanceAlertTaskHandler( $this->setupFile )
		);
	}

	public function testGetHtml() {

		$this->setupFile->expects( $this->once() )
			->method( 'get' )
			->with( $this->equalTo( 'last_optimization_run' ) )
			->will( $this->returnValue( '1970-01-01' ) );

		$instance = new LastOptimizationRunMaintenanceAlertTaskHandler(
			$this->setupFile
		);

		$instance->setFeatureSet( SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN );

		$this->assertContains(
			'smw-admin-alerts-last-optimization-run',
			$instance->getHtml()
		);
	}

}
