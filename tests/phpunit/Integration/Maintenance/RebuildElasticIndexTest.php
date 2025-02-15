<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Services\ServicesFactory as ApplicationFactory;
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
class RebuildElasticIndexTest extends SMWIntegrationTestCase {

	private $runnerFactory;
	private $spyMessageReporter;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = ApplicationFactory::getInstance()->getStore();

		if ( !$this->store instanceof \SMW\Elastic\ElasticStore ) {
			$this->markTestSkipped( "Skipping test because it requires a `ElasticStore` instance." );
		}

		$utilityFactory = TestEnvironment::getUtilityFactory();

		$this->runnerFactory  = $utilityFactory->newRunnerFactory();
		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\rebuildElasticIndex'
		);

		$maintenanceRunner->setMessageReporter( $this->spyMessageReporter );
		$maintenanceRunner->setQuiet();

		$this->assertTrue(
			$maintenanceRunner->run()
		);
	}

}
