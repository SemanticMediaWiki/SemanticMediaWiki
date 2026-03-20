<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Admin\Alerts;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\Alerts\LastOptimizationRunMaintenanceAlertTaskHandler;
use SMW\SetupFile;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Alerts\LastOptimizationRunMaintenanceAlertTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class LastOptimizationRunMaintenanceAlertTaskHandlerTest extends TestCase {

	private $setupFile;

	protected function setUp(): void {
		parent::setUp();

		$this->setupFile = $this->getMockBuilder( SetupFile::class )
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
			->with( 'last_optimization_run' )
			->willReturn( '1970-01-01' );

		$instance = new LastOptimizationRunMaintenanceAlertTaskHandler(
			$this->setupFile
		);

		$instance->setFeatureSet( SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN );

		$this->assertStringContainsString(
			'smw-admin-alerts-last-optimization-run',
			$instance->getHtml()
		);
	}

}
