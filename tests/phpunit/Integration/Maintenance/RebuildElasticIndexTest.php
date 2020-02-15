<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\TestEnvironment;
use SMW\ApplicationFactory;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class RebuildElasticIndexTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;
	private $runnerFactory;
	private $spyMessageReporter;
	private $store;

	protected function setUp() : void {
		parent::setUp();

		$this->store = ApplicationFactory::getInstance()->getStore();

		if ( !$this->store instanceof \SMW\Elastic\ElasticStore ) {
			$this->markTestSkipped( "Skipping test because it requires a `ElasticStore` instance." );
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
			'SMW\Maintenance\RebuildElasticIndex'
		);

		$maintenanceRunner->setMessageReporter( $this->spyMessageReporter );
		$maintenanceRunner->setQuiet();

		$this->assertTrue(
			$maintenanceRunner->run()
		);
	}

}
