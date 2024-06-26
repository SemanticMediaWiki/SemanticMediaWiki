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
abstract class SMWIntegrationTestCase extends \PHPUnit_Framework_TestCase {

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

    protected function skipTestForMediaWikiVersionLowerThan( $version, $message = '' ) {

		if ( $message === '' ) {
			$message = "This test is skipped for MediaWiki version " . MW_VERSION;
		}

		if ( version_compare( MW_VERSION, $version, '<' ) ) {
			$this->markTestSkipped( $message );
		}
	}

}
