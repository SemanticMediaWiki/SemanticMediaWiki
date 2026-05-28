<?php

namespace SMW\Tests\Integration\Maintenance;

use MediaWiki\HookContainer\HookContainer;
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

	private $runnerFactory;
	private $spyMessageReporter;
	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
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

		$maintenanceRunner->setHookContainer(
			$this->hookContainer
		);

		$maintenanceRunner->run();

		$this->assertStringContainsString(
			'Collation update(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
