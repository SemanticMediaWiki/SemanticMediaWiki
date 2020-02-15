<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\DatabaseTestCase;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RemoveDuplicateEntitiesTest extends DatabaseTestCase {

	protected $destroyDatabaseTablesAfterRun = true;
	private $runnerFactory;

	protected function setUp() : void {
		parent::setUp();

		$this->runnerFactory  = TestEnvironment::getUtilityFactory()->newRunnerFactory();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testRun() {

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'SMW\Maintenance\RemoveDuplicateEntities'
		);

		$maintenanceRunner->setQuiet();

		$this->assertTrue(
			$maintenanceRunner->run()
		);
	}

}
