<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\TestEnvironment;
use SMW\ApplicationFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class RebuildElasticMissingDocumentsTest extends MwDBaseUnitTestCase {

	use PHPUnitCompat;

	protected $destroyDatabaseTablesAfterRun = true;
	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp() : void {
		parent::setUp();

		$store = ApplicationFactory::getInstance()->getStore();

		if ( !$store instanceof \SMW\Elastic\ElasticStore ) {
			$this->markTestSkipped( "Skipping test because a ElasticStore instance is required." );
		}

		$utilityFactory = TestEnvironment::getUtilityFactory();

		$this->runnerFactory  = $utilityFactory->newRunnerFactory();
		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testRun() {

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'SMW\Maintenance\RebuildElasticMissingDocuments'
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
