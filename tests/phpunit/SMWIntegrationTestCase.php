<?php

namespace SMW\Tests;

use SMW\StoreFactory;
use SMW\Tests\TestEnvironment;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\TestResult;

/**
 * @group semantic-mediawiki
 * @group Database
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
	 * @var TestEnvironment
	 */
	protected $testEnvironment;

    protected function setUp() : void {
		parent::setUp();

        $this->testEnvironment = new TestEnvironment();
    }

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function run( ?TestResult $result = null ) : TestResult {
		$this->getStore()->clear();
		if( $GLOBALS['wgDBtype'] == 'mysql' ) {

			// Don't use temporary tables to avoid "Error: 1137 Can't reopen table" on mysql
			// https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/80/commits/565061cd0b9ccabe521f0382938d013a599e4673
			$this->setCliArg( 'use-normal-tables', true );
		}

		return parent::run( $result );
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