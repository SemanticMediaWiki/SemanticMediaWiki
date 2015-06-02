<?php

namespace SMW\Tests;

use SMW\Tests\Utils\MwDatabaseTableBuilder;
use SMW\StoreFactory;
use SMW\ApplicationFactory;
use SMW\NamespaceExaminer;
use SMW\PropertyRegistry;
use SMW\Settings;

use SMWExporter as Exporter;

use RuntimeException;

/**
 * @group SMW
 * @group SMWExtension
 *
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
abstract class MwDBaseUnitTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MwDatabaseTableBuilder
	 */
	protected $mwDatabaseTableBuilder = null;

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

		PropertyRegistry::clear();

		$this->checkIfDatabaseCanBeUsedOtherwiseSkipTest();
		$this->checkIfStoreCanBeUsedOtherwiseSkipTest();

		ApplicationFactory::getInstance()->registerObject( 'Store', $this->getStore() );

		ApplicationFactory::getInstance()->registerObject(
			'Cache',
			ApplicationFactory::getInstance()->newCacheFactory()->newFixedInMemoryCache()
		);
	}

	protected function tearDown() {
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

		$this->mwDatabaseTableBuilder = MwDatabaseTableBuilder::getInstance( $this->getStore() );
		$this->mwDatabaseTableBuilder->removeAvailableDatabaseType( $this->databaseToBeExcluded );

		$this->destroyDatabaseTables( $this->destroyDatabaseTablesBeforeRun );

		try {
			$this->mwDatabaseTableBuilder->doBuild();
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
			$message = "This test is skipped for MediaWiki version {$GLOBALS['wgVersion']}";
		}

		if ( version_compare( $GLOBALS['wgVersion'], $version, '<' ) ) {
			$this->markTestSkipped( $message );
		}
	}

	protected function skipTestForDatabase( $excludedDatabase, $message = '' ) {

		if ( is_string( $excludedDatabase ) ) {
			$excludedDatabase = array( $excludedDatabase );
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
		return $this->mwDatabaseTableBuilder->getDBConnection();
	}

	protected function getDBConnectionProvider() {
		return $this->mwDatabaseTableBuilder->getDBConnectionProvider();
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
				$this->mwDatabaseTableBuilder->doDestroy();
			} catch ( \Exception $e ) { // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.EmptyStatement
				// Do nothing because an instance was not available
			} // @codingStandardsIgnoreEnd
		}
	}

}
