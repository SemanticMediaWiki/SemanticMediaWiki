<?php

namespace SMW\Tests;

use SMW\StoreFactory;
use SMW\Tests\TestEnvironment;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\TestResult;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;

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

	/**
	 * @var TestDatabaseTableBuilder
	 */
	protected $testDatabaseTableBuilder;

	/**
	 * @var array|null
	 */
	protected $databaseToBeExcluded = null;

	/**
	 * @var array|null
	 */
	protected $storesToBeExcluded = null;

	/**
	 * @var boolean
	 */
	protected $destroyDatabaseTablesBeforeRun = false;

	/**
	 * @var boolean
	 */
	protected $destroyDatabaseTablesAfterRun = false;

	/**
	 * @var boolean
	 */
	protected $isUsableUnitTestDatabase = true;

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

		$this->testDatabaseTableBuilder = TestDatabaseTableBuilder::getInstance(
			$this->getStore()
		);

		$this->testDatabaseTableBuilder->removeAvailableDatabaseType(
			$this->databaseToBeExcluded
		);

		$this->destroyDatabaseTables( $this->destroyDatabaseTablesBeforeRun );

		try {
			$this->testDatabaseTableBuilder->doBuild();
		} catch ( RuntimeException $e ) {
			$this->isUsableUnitTestDatabase = false;
		}

		$testResult = parent::run( $result );

		$this->destroyDatabaseTables( $this->destroyDatabaseTablesAfterRun );

		return $testResult;

		
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

	private function destroyDatabaseTables( $destroyDatabaseTables ) {
		if ( $this->isUsableUnitTestDatabase && $destroyDatabaseTables ) {
			try {
				$this->testDatabaseTableBuilder->doDestroy();
			} catch ( \Exception $e ) { // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.EmptyStatement
				// Do nothing because an instance was not available
			} // @codingStandardsIgnoreEnd
		}
	}
	
}