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

	protected function setUp() {
		parent::setUp();

		$store = ApplicationFactory::getInstance()->getStore();

		if ( !$store instanceof \SMW\Elastic\ElasticStore ) {
			$this->markTestSkipped( "Skipping test because a ElasticStore instance is required." );
		}

		$utilityFactory = TestEnvironment::getUtilityFactory();

		$this->runnerFactory  = $utilityFactory->newRunnerFactory();
		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testRun() {

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'SMW\Maintenance\RebuildElasticIndex'
		);

		$maintenanceRunner->setMessageReporter( $this->spyMessageReporter );
		$maintenanceRunner->setQuiet();

		// Testing against ES 5.6 may cause a "Can't update
		// [index.number_of_replicas] on closed indices" see
		// https://github.com/elastic/elasticsearch/issues/22993
		//
		// Should be fixed with ES 6.4
		// https://github.com/elastic/elasticsearch/pull/30423

		try {
			$res = $maintenanceRunner->run();
		} catch( \Elasticsearch\Common\Exceptions\BadRequest400Exception $e ) {
			$res = true;
		}

		$this->assertTrue(
			$res
		);
	}

}
