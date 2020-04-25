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
class DisposeOutdatedEntitiesTest extends DatabaseTestCase {

	use PHPUnitCompat;

	protected $destroyDatabaseTablesAfterRun = true;
	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment->addConfiguration( 'smwgImportReqVersion', 1 );
		$this->testEnvironment->addConfiguration( 'smwgEnabledFulltextSearch', false );

		$this->runnerFactory  = $this->testEnvironment::getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testRun() {

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'SMW\Maintenance\DisposeOutdatedEntities'
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
