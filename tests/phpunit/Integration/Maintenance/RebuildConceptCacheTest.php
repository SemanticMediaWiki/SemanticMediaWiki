<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\DatabaseTestCase;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class RebuildConceptCacheTest extends DatabaseTestCase {

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
			'SMW\Maintenance\RebuildConceptCache'
		);

		$maintenanceRunner->setQuiet();

		$maintenanceRunner->setOptions(
			[ 'create' => true ]
		);

		$this->assertTrue(
			$maintenanceRunner->run()
		);
	}

}
