<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.1.0
 */
class OptimizeStoreMaintenanceTest extends SMWIntegrationTestCase {

	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->runnerFactory = $utilityFactory->newRunnerFactory();
		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
	}

	public function testOptimizeStore() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( '\SMW\Maintenance\optimizeStore' );

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setQuiet();
		$maintenanceRunner->run();

		$this->assertStringContainsString(
			'Core table(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testOptimizeStoreWithMaintenanceLog() {
		$db = $this->getDb();

		$logCountBefore = (int)$db->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'logging' )
			->where( [ 'log_type' => 'smw', 'log_action' => 'maintenance' ] )
			->caller( __METHOD__ )
			->fetchField();

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( '\SMW\Maintenance\optimizeStore' );

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setOptions(
			[
				'with-maintenance-log' => true
			]
		);

		$maintenanceRunner->setQuiet();
		$maintenanceRunner->run();

		$this->assertStringContainsString(
			'Core table(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);

		$logCountAfter = (int)$db->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'logging' )
			->where( [ 'log_type' => 'smw', 'log_action' => 'maintenance' ] )
			->caller( __METHOD__ )
			->fetchField();

		$this->assertGreaterThan(
			$logCountBefore,
			$logCountAfter,
			'Expected --with-maintenance-log to record an smw/maintenance log entry'
		);
	}

}
