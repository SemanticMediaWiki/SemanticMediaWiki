<?php

namespace SMW\Tests;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use SMW\DataValueFactory;
use SMW\MediaWiki\LinkBatch;
use SMW\PropertyRegistry;
use SMW\Services\ServicesFactory;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\StoreFactory;
use SMWExporter as Exporter;
use SMWQueryProcessor;
use Wikimedia\ObjectCache\HashBagOStuff;

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

	protected ?TestEnvironment $testEnvironment = null;

	/**
	 * Setup configuration required for SMW integration tests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Load default settings specific to SMW
		TestEnvironment::loadDefaultSettings( [ 'smwgQEqualitySupport' ] );

		// Don't use temporary tables to avoid "Error: 1137 Can't reopen table" on mysql.
		// Must be set before maybeSetupDB() reads this flag.
		// https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/80/commits/565061cd0b9ccabe521f0382938d013a599e4673
		static::setCliArg( 'use-normal-tables', true );
	}

	/**
	 * Called once per test class after overrideMwServices(). Creates SMW
	 * tables using MW's native DB connection so that both MW and SMW share
	 * the same transaction context, avoiding lock contention on teardown.
	 */
	public function addDBDataOnce(): void {
		// Release cached connections so the Store picks up the test DB
		// prefix (unittest_) set by MW's test framework. Without this,
		// connections cached during boot have no prefix and the Store
		// reads/writes the wrong tables.
		ServicesFactory::getInstance()->getConnectionManager()->releaseConnections();

		// Idempotent: creates SMW tables if they don't exist yet
		$this->getStore()->setup( false );
	}

	protected function setUp(): void {
		parent::setUp();

		// Reset SMW services first so stale singletons from prior tests
		// (or the unit suite) are destroyed before we set up fresh ones.
		$this->resetSMWServices();

		// Release cached connections on the fresh ServicesFactory so the
		// Store picks up the test DB connection (with unittest_ prefix)
		// instead of stale boot connections.
		ServicesFactory::getInstance()->getConnectionManager()->releaseConnections();

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
		CachingSemanticDataLookup::clear();
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
		$this->testEnvironment->disableSoftwareChangeTags();
		$this->testEnvironment->registerObject( 'Store', $this->getStore() );
		$this->testEnvironment->registerObject( 'Cache', $fixedInMemoryLruCache );

		PropertyRegistry::clear();

		$this->testEnvironment->clearPendingDeferredUpdates();

		// Set cache to avoid unexpected database interactions
		$this->disableGlobalCaches();
	}

	protected function clearGlobalCaches(): void {
		// Clear the main cache and other relevant MediaWiki caches
		$cache = MediaWikiServices::getInstance()->getObjectCacheFactory()->getInstance( CACHE_ANYTHING );
		if ( $cache instanceof HashBagOStuff ) {
			$cache->clear();
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

	protected function tearDown(): void {
		if ( $this->testEnvironment !== null ) {
			$this->testEnvironment->tearDown();
		}

		// Commit or rollback any open transactions from page deletions
		// (flushPages) before MW's tearDown truncates tables. Without this,
		// delete transactions can hold row locks that block TRUNCATE,
		// causing "Lock wait timeout" errors.
		MediaWikiServices::getInstance()
			->getDBLoadBalancerFactory()
			->commitPrimaryChanges( __METHOD__ );

		parent::tearDown();
	}

	protected function getStore() {
		return StoreFactory::getStore();
	}

	protected function skipTestForMediaWikiVersionLowerThan( string $version, string $message = '' ): void {
		if ( $message === '' ) {
			$message = "This test is skipped for MediaWiki version " . MW_VERSION;
		}

		if ( version_compare( MW_VERSION, $version, '<' ) ) {
			$this->markTestSkipped( $message );
		}
	}

	protected function skipTestForDatabase( string|array $excludedDatabase, string $message = '' ): void {
		if ( is_string( $excludedDatabase ) ) {
			$excludedDatabase = [ $excludedDatabase ];
		}

		if ( $message === '' ) {
			$message = "Database was excluded and is not expected to support this test";
		}

		if ( in_array( $this->getDb()->getType(), $excludedDatabase ) ) {
			$this->markTestSkipped( $message );
		}
	}
}
