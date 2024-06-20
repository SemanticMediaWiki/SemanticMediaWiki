<?php

namespace SMW\Tests;

use MediaWikiIntegrationTestCase;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;
use SMW\StoreFactory;
use SMW\Tests\TestEnvironment;
/**
 * @group semantic-mediawiki
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
abstract class SMWIntegrationTestCase extends MediaWikiIntegrationTestCase {

    /**
	 * @var TestDatabaseTableBuilder
	 */
	protected $testDatabaseTableBuilder;

    /**
	 * @var TestEnvironment
	 */
	protected $testEnvironment;

    protected function setUp() : void {
		parent::setUp();

        $this->testEnvironment = new TestEnvironment();

        $this->testDatabaseTableBuilder = TestDatabaseTableBuilder::getInstance(
			$this->getStore()
		);

		$this->testDatabaseTableBuilder->doBuild();

    }

    protected function getStore() {
		return StoreFactory::getStore();
	}

}
