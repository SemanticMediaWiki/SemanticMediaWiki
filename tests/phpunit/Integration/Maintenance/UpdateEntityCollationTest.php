<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\PHPUnitCompat;
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
class UpdateEntityCollationTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $runnerFactory;
	private $spyMessageReporter;
	private $hookDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->runnerFactory  = $this->testEnvironment::getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\updateEntityCollation'
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setHookDispatcher(
			$this->hookDispatcher
		);

		$maintenanceRunner->run();

		$this->assertContains(
			'Collation update(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
