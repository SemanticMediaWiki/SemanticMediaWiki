<?php

namespace SMW\Tests\Integration\Maintenance;

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
class RebuildFulltextSearchTableTest extends SMWIntegrationTestCase {

	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->addConfiguration( 'smwgEnabledFulltextSearch', true );
		$this->runnerFactory  = $this->testEnvironment::getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\rebuildFulltextSearchTable'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setQuiet();

		$maintenanceRunner->run();

		$this->assertStringContainsString(
			'script is used to rebuild or optimize the search index',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testRunInChunks() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\rebuildFulltextSearchTable'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setOptions( [
			'n' => 250,
			's' => 0,
			'max-time' => 60,
			'quick' => true
		] );

		$maintenanceRunner->setQuiet();

		$maintenanceRunner->run();

		// Message from RebuildFulltextSearchTable::execute()
		$this->assertStringContainsString(
			'It is rebuilt in chunks of 250 subject IDs',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testInsertJobIntoJobQueueInsteadOfRebuilding() {
		$jobQueueGroup = $this->getServiceContainer()->getJobQueueGroup();
		$jobQueueGroup->get( 'smw.fulltextSearchTableRebuild' )->delete();

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\rebuildFulltextSearchTable'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setOptions( [
			'use-job' => true,
			'n' => 250,
			's' => 0,
			'quick' => true
		] );

		$maintenanceRunner->setQuiet();

		$maintenanceRunner->run();

		// Message from RebuildFulltextSearchTable::queueRebuildJob()
		$this->assertStringContainsString(
			'... queued (s=0, n=250)',
			$this->spyMessageReporter->getMessagesAsString()
		);

		// 1 job
		$this->assertSame(
			1,
			$jobQueueGroup->get( 'smw.fulltextSearchTableRebuild' )->getSize()
		);
	}

}
