<?php

namespace SMW\Tests;

use MediaWikiIntegrationTestCase;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;
use SMW\StoreFactory;

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

    protected function setUp() : void {
		parent::setUp();

        $this->testDatabaseTableBuilder = TestDatabaseTableBuilder::getInstance(
			$this->getStore()
		);

		$this->testDatabaseTableBuilder->doBuild();

    }

    protected function getStore() {
		return StoreFactory::getStore();
	}

}
