<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\PHPUnitCompat;
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
class RebuildElasticMissingDocumentsTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$store = ApplicationFactory::getInstance()->getStore();

		if ( !$store instanceof \SMW\Elastic\ElasticStore ) {
			$this->markTestSkipped( "Skipping test because a ElasticStore instance is required." );
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
			'\SMW\Maintenance\rebuildElasticMissingDocuments'
		);

		$maintenanceRunner->setMessageReporter( $this->spyMessageReporter );
		$maintenanceRunner->setQuiet();

		$this->assertTrue(
			$maintenanceRunner->run()
		);

		$this->assertContains(
			'removed replication trail',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
