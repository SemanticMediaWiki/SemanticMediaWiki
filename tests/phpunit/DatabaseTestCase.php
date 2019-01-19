<?php

namespace SMW\Tests;

use RuntimeException;
use SMW\ApplicationFactory;
use SMW\NamespaceExaminer;
use SMW\PropertyRegistry;
use SMW\Settings;
use SMW\StoreFactory;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;
use SMWExporter as Exporter;

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
abstract class DatabaseTestCase extends \PHPUnit_Framework_TestCase {

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

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->addConfiguration( 'smwgEnabledDeferredUpdate', false );

		PropertyRegistry::clear();

		$this->checkIfDatabaseCanBeUsedOtherwiseSkipTest();
		$this->checkIfStoreCanBeUsedOtherwiseSkipTest();

		ApplicationFactory::getInstance()->registerObject( 'Store', $this->getStore() );

		ApplicationFactory::getInstance()->registerObject(
			'Cache',
			ApplicationFactory::getInstance()->newCacheFactory()->newFixedInMemoryCache()
		);

		// Avoid surprise on revisions etc.
		// @see MediaWikiTestCase::doLightweightServiceReset
		$this->testEnvironment->resetMediaWikiService( 'MainObjectStash' );
		$this->testEnvironment->resetMediaWikiService( 'LocalServerObjectCache' );
		$this->testEnvironment->resetMediaWikiService( 'MainWANObjectCache' );

		$this->testEnvironment->clearPendingDeferredUpdates();
	}

	protected function tearDown() {

		// If setUp is skipped early this might not be initialized
		if ( $this->testEnvironment !== null ) {
			$this->testEnvironment->tearDown();
		}

		ApplicationFactory::clear();
		NamespaceExaminer::clear();
		PropertyRegistry::clear();
		Settings::clear();
		Exporter::getInstance()->clear();

		parent::tearDown();
	}

	/**
	 * It is assumed that each test that makes use of the TestCase is requesting
	 * a "real" DB connection
	 *
	 * By default, the database tables are being re-used but it is possible to
	 * request a trear down so that the next test can rebuild the tables from
	 * scratch
	 */
	public function run( \PHPUnit_Framework_TestResult $result = null ) {

		$this->getStore()->clear();

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

		parent::run( $result );

		$this->destroyDatabaseTables( $this->destroyDatabaseTablesAfterRun );
	}

	protected function removeDatabaseTypeFromTest( $databaseToBeExcluded ) {
		$this->databaseToBeExcluded = $databaseToBeExcluded;
	}

	protected function destroyDatabaseTablesAfterRun() {
		$this->destroyDatabaseTablesAfterRun = true;
	}

	protected function getStore() {
		return StoreFactory::getStore();
	}

	protected function setStoresToBeExcluded( array $storesToBeExcluded ) {
		return $this->storesToBeExcluded = $storesToBeExcluded;
	}

	protected function skipTestForMediaWikiVersionLowerThan( $version, $message = '' ) {

		if ( $message === '' ) {
			$message = "This test is skipped for MediaWiki version " . MW_VERSION;
		}

		if ( version_compare( MW_VERSION, $version, '<' ) ) {
			$this->markTestSkipped( $message );
		}
	}

	protected function skipTestForDatabase( $excludedDatabase, $message = '' ) {

		if ( is_string( $excludedDatabase ) ) {
			$excludedDatabase = [ $excludedDatabase ];
		}

		if ( $message === '' ) {
			$message = "Database was excluded and is not expected to support this test";
		}

		if ( in_array( $this->getDBConnection()->getType(), $excludedDatabase ) ) {
			$this->markTestSkipped( $message );
		}
	}

	protected function skipTestForStore( $excludeStore ) {

		$store = get_class( $this->getStore() );

		if ( $store == $excludeStore ) {
			$this->markTestSkipped(
				"{$store} was excluded and is not expected to support the test"
			);
		}
	}

	protected function getDBConnection() {
		return $this->testDatabaseTableBuilder->getDBConnection();
	}

	protected function getConnectionProvider() {
		return $this->testDatabaseTableBuilder->getConnectionProvider();
	}

	protected function isUsableUnitTestDatabase() {
		return $this->isUsableUnitTestDatabase;
	}

	protected function checkIfDatabaseCanBeUsedOtherwiseSkipTest() {

		if ( !$this->isUsableUnitTestDatabase ) {
			$this->markTestSkipped(
				"Database was excluded and is not expected to support the test"
			);
		}
	}

	protected function checkIfStoreCanBeUsedOtherwiseSkipTest() {

		$store = get_class( $this->getStore() );

		if ( in_array( $store, (array)$this->storesToBeExcluded ) ) {
			$this->markTestSkipped(
				"{$store} was excluded and is not expected to support the test"
			);
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
