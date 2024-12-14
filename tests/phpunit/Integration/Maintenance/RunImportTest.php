<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class RunImportTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->addConfiguration( 'smwgImportReqVersion', 1 );
		$this->testEnvironment->addConfiguration( 'smwgEnabledFulltextSearch', false );

		$this->runnerFactory  = $this->testEnvironment::getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\runImport'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$this->assertTrue(
			$maintenanceRunner->run()
		);

		$this->assertContains(
			'Importing from smw.vocab.json',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
