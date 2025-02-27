<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\PHPUnitCompat;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DisposeOutdatedEntitiesTest extends SMWIntegrationTestCase {

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
			'\SMW\Maintenance\disposeOutdatedEntities'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$this->assertTrue(
			$maintenanceRunner->run()
		);

		$this->assertContains(
			'Removing outdated and invalid entities',
			$this->spyMessageReporter->getMessagesAsString()
		);

		$this->assertContains(
			'Removing query links',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
