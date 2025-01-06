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
class UpdateEntityCountMapTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory = $this->testEnvironment->getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\updateEntityCountMap'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setQuiet();
		$maintenanceRunner->run();

		$this->assertContains(
			'update on the `smw_countmap` field',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
