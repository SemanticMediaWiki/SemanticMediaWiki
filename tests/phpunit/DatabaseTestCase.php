<?php

namespace SMW\Tests;

use BacklinkCache;
use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use PHPUnit\Framework\TestResult;
use RequestContext;
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
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
abstract class DatabaseTestCase extends \PHPUnit\Framework\TestCase {

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
	 * Tests are written with a specific default behaviour in mind and should be
	 * independent from any `LocalSettings.php` configuration that may alter functional
	 * components therefore add configurations that needs to be initialized before
	 * any service is created.
	 */
	public static function setUpBeforeClass(): void {
		$defaultSettingKeys = [
			'smwgQEqualitySupport'
		];

		TestEnvironment::loadDefaultSettings( $defaultSettingKeys );
	}

	protected function setUp(): void {
		parent::setUp();
		// #3916
		// Reset $wgUser, which is probably 127.0.0.1, as its loaded data is probably not valid
		// @todo Should we start setting $wgUser to something nondeterministic
		//  to encourage tests to be updated to not depend on it?
		$user = RequestContext::getMain()->getUser();
		$user->clearInstanceCache( $user->mFrom );

		// Explicitly close all DB connections tracked by existing LBs
		// to make it easier to track down places that may hold onto a stale
		// LB or connection reference.
		$oldServices = MediaWikiServices::getInstance();
		$oldServices->getDBLoadBalancerFactory()->destroy();
		ServicesFactory::getInstance()->getConnectionManager()->releaseConnections();

		// Reset all MediaWiki services, as well as SMW services and singletons
		// that may have captured references to them.
		LinkBatch::reset();
		DataValueFactory::getInstance()->clear();
		Exporter::clear();
		MediaWikiServices::resetGlobalInstance();
		StoreFactory::clear();
		ServicesFactory::clear();
		SMWQueryProcessor::setRecursiveTextProcessor();
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
		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->addConfiguration( 'smwgEnabledDeferredUpdate', false );

		PropertyRegistry::clear();

		$this->checkIfDatabaseCanBeUsedOtherwiseSkipTest();
		$this->checkIfStoreCanBeUsedOtherwiseSkipTest();

		$fixedInMemoryLruCache = ServicesFactory::getInstance()->create( 'FixedInMemoryLruCache' );

		$this->testEnvironment->registerObject( 'Store', $this->getStore() );
		$this->testEnvironment->registerObject( 'Cache', $fixedInMemoryLruCache );

		if ( !defined( 'SMW_PHPUNIT_DB_VERSION' ) ) {
			define( 'SMW_PHPUNIT_DB_VERSION', $this->getDBConnection()->getServerInfo() );
		}

		$this->testEnvironment->clearPendingDeferredUpdates();

		ObjectCache::$instances[CACHE_DB] = new HashBagOStuff();

		// Avoid Error while sending QUERY packet / SqlBagOStuff seen on MW 1.24
		// https://s3.amazonaws.com/archive.travis-ci.org/jobs/30408638/log.txt
		ObjectCache::$instances[CACHE_ANYTHING] = new HashBagOStuff();

		$GLOBALS['wgDevelopmentWarnings'] = true;
		$GLOBALS['wgMainCacheType'] = CACHE_NONE;
		$GLOBALS['wgMessageCacheType'] = CACHE_NONE;
		$GLOBALS['wgParserCacheType'] = CACHE_NONE;
		$GLOBALS['wgLanguageConverterCacheType'] = CACHE_NONE;
		$GLOBALS['wgUseDatabaseMessages'] = false;
	}

	protected function tearDown(): void {
		// If setUp is skipped early this might not be initialized
		if ( $this->testEnvironment !== null ) {
			$this->testEnvironment->tearDown();
		}

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
	public function run( ?TestResult $result = null ): TestResult {
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

		$testResult = parent::run( $result );

		$this->destroyDatabaseTables( $this->destroyDatabaseTablesAfterRun );

		return $testResult;
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
