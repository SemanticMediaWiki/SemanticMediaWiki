<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\DatabaseTestCase;
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
class UpdateEntityCountMapTest extends DatabaseTestCase {

	use PHPUnitCompat;

	protected $destroyDatabaseTablesAfterRun = true;
	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp() : void {
		parent::setUp();

		$this->runnerFactory = $this->testEnvironment->getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testRun() {

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'SMW\Maintenance\UpdateEntityCountMap'
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
