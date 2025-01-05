<?php

namespace SMW\Tests;

use BacklinkCache;
use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use ObjectCache;
use PHPUnit\Framework\TestResult;
use RuntimeException;
use SMW\DataValueFactory;
use SMW\MediaWiki\LinkBatch;
use SMW\PropertyRegistry;
use SMW\Services\ServicesFactory;
use SMW\StoreFactory;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;
use SMWExporter as Exporter;
use SMWQueryProcessor;
use Title;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author Marko Ilic
 * @author Luke Eversfield
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
	 * @var bool
	 */
	protected $destroyDatabaseTablesBeforeRun = false;

	/**
	 * @var bool
	 */
	protected $destroyDatabaseTablesAfterRun = false;

	/**
	 * @var bool
	 */
	protected $isUsableUnitTestDatabase = true;

	/**
	 * Setup configuration required for SMW integration tests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Load default settings specific to SMW
		TestEnvironment::loadDefaultSettings( [ 'smwgQEqualitySupport' ] );
	}

	protected function setUp(): void {
		parent::setUp();

		// Clear any cached user to ensure a clean state for each test
		$user = $this->getTestUser()->getUser();
		$user->clearInstanceCache( $user->mFrom );

		// Reset services and caches that SMW tests rely on
		$this->resetSMWServices();
		$this->clearGlobalCaches();

		// Prepare test environment for SMW-specific requirements
		$this->initializeTestEnvironment();
	}

	 /**
	  * Reset Semantic MediaWiki-related services and caches.
	  */
	private function resetSMWServices(): void {
		LinkBatch::reset();
		DataValueFactory::getInstance()->clear();
		Exporter::clear();
		MediaWikiServices::getInstance()->resetGlobalInstance();
		StoreFactory::clear();
		ServicesFactory::clear();
		SMWQueryProcessor::setRecursiveTextProcessor();
	}

	/**
	 * Initialize SMW test environment configuration.
	 */
	private function initializeTestEnvironment(): void {
		$fixedInMemoryLruCache = ServicesFactory::getInstance()->create( 'FixedInMemoryLruCache' );

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->addConfiguration( 'smwgEnabledDeferredUpdate', false );
		$this->testEnvironment->registerObject( 'Store', $this->getStore() );
		$this->testEnvironment->registerObject( 'Cache', $fixedInMemoryLruCache );
		$this->testEnvironment->resetDBLoadBalancer();

		PropertyRegistry::clear();

		$this->clearPendingDeferredUpdates();

		// Set cache to avoid unexpected database interactions
		$this->disableGlobalCaches();
	}

	protected function clearGlobalCaches(): void {
		// Clear the main cache and other relevant MediaWiki caches
		$cache = ObjectCache::getInstance( CACHE_ANYTHING );
		$oldServices = MediaWikiServices::getInstance();
		if ( $cache instanceof HashBagOStuff ) {
			$cache->clear();
		}
		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			if ( !$oldServices->hasService( 'BacklinkCacheFactory' ) ) {
				// BacklinkCacheFactory is available starting with MW 1.37, reset the legacy singleton otherwise.
				// Use a mock title for this to avoid premature service realization.
				$title = $this->createMock( Title::class );
				$title->expects( $this->any() )
					->method( 'getPrefixedDBkey' )
					->willReturn( 'Badtitle/Dummy title for BacklinkCache reset' );

				BacklinkCache::get( $title )->clear();
			}
		}
	}

	/**
	 * Disable global caches for predictable test behavior.
	 */
	private function disableGlobalCaches(): void {
		$GLOBALS['wgMainCacheType'] = CACHE_NONE;
		$GLOBALS['wgMessageCacheType'] = CACHE_NONE;
		$GLOBALS['wgParserCacheType'] = CACHE_NONE;
		$GLOBALS['wgLanguageConverterCacheType'] = CACHE_NONE;
		$GLOBALS['wgUseDatabaseMessages'] = false;
	}

	/**
	 * Clear pending deferred updates to ensure no state leaks between tests.
	 */
	private function clearPendingDeferredUpdates(): void {
		$this->testEnvironment->clearPendingDeferredUpdates();
	}

	protected function tearDown(): void {
		if ( $this->testEnvironment !== null ) {
			$this->testEnvironment->tearDown();
		}
		// Ensure all transactions are closed before ending the test
		$dbw = $this->getDBConnection();
		$dbw->rollback();

		parent::tearDown();
	}

	public function run( ?TestResult $result = null ): TestResult {
		$this->getStore()->clear();
		if ( $GLOBALS['wgDBtype'] == 'mysql' ) {

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

	protected function removeDatabaseTypeFromTest( $databaseToBeExcluded ) {
		$this->databaseToBeExcluded = $databaseToBeExcluded;
	}

	protected function destroyDatabaseTablesAfterRun() {
		$this->destroyDatabaseTablesAfterRun = true;
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
