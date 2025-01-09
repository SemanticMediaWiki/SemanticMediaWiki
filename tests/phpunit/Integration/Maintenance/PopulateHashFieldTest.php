<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PopulateHashFieldTest extends SMWIntegrationTestCase {

	private $runnerFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory = TestEnvironment::getUtilityFactory()->newRunnerFactory();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\populateHashField'
		);

		$maintenanceRunner->setQuiet();

		$this->assertTrue(
			$maintenanceRunner->run()
		);
	}

}
