<?php

namespace SMW\Tests\Util;

use SMW\Tests\Util\PageCreator;
use SMW\Store;

use ObjectCache;
use HashBagOStuff;
use DatabaseBase;
use User;
use Title;

use RuntimeException;
use CloneDatabase;

/**
 * @ingroup Test
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MwUnitTestDatabaseInstaller {

	private static $UTDB_PREFIX = null;
	private static $MWDB_PREFIX = null;

	/* @var Store */
	protected $store = null;

	/* @var DatabaseBase */
	protected $dbConnection = null;

	/* @var CloneDatabase */
	protected $cloneDatabase = null;

	protected $supportedDatabase = array(
		'mysql',
		'sqlite',
		'postgres'
	);

	private static $dbSetup = false;

	/**
	 * @since 1.9.3
	 *
	 * @param Store $store
	 * @param DatabaseBase|null $dbConnection
	 */
	public function __construct( Store $store, DatabaseBase $dbConnection = null ) {
		$this->store = $store;
		$this->dbConnection = $dbConnection;

		if ( $this->dbConnection === null ) {
			$this->dbConnection = wfGetDB( DB_MASTER );
		}

		self::$UTDB_PREFIX = 'sunittest_';
		self::$MWDB_PREFIX = $GLOBALS['wgDBprefix'];

		// MediaWikiTestCase changes the GLOBAL state therefore
		// we have to ensure that we really use the original DB and
		// not an existing 'unittest_' created by a MW test
		if ( self::$MWDB_PREFIX === 'unittest_' ) {
			self::$MWDB_PREFIX = '';
		}
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string|array|null $database
	 */
	public function removeSupportedDatabase( $database = null ) {
		$this->supportedDatabase = array_diff( $this->supportedDatabase, (array)$database );
	}

	/**
	 * @since 1.9.3
	 */
	public function setup() {

		if ( !$this->isSupportedDatabase() ) {
			throw new RuntimeException( 'Requested DB type is not enabled for the installer' );
		}

		ObjectCache::$instances[CACHE_DB] = new HashBagOStuff();

		$GLOBALS['wgDevelopmentWarnings'] = true;
		$GLOBALS['wgMainCacheType'] = CACHE_NONE;
		$GLOBALS['wgMessageCacheType'] = CACHE_NONE;
		$GLOBALS['wgParserCacheType'] = CACHE_NONE;
		$GLOBALS['wgLanguageConverterCacheType'] = CACHE_NONE;
		$GLOBALS['wgUseDatabaseMessages'] = false;

		$this->setupUnitTestDatabase();
		$this->cleanOpenDBTransactions();
	}

	/**
	 * @since 1.9.3
	 */
	public function tearDown() {
		$this->destroyUnitTestDatabase();
		$this->cleanOpenDBTransactions();
	}

	/**
	 * @since  1.9.3
	 *
	 * @return string
	 */
	public function getDBPrefix() {
		return self::$UTDB_PREFIX;
	}

	/**
	 * @since  1.9.3
	 *
	 * @param DatabaseBase $dbConnection
	 */
	public function setDBConnection( DatabaseBase $dbConnection ) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @since  1.9.3
	 *
	 * @return DatabaseBase
	 */
	public function getDBConnection() {
		return $this->dbConnection;
	}

	/**
	 * @see MediaWikiTestCase::listTables
	 *
	 * @return array
	 */
	public function generateListOfTables() {

		$dbConnection = $this->getDBConnection();

		$tables = $dbConnection->listTables(
			self::$MWDB_PREFIX,
			__METHOD__
		);

		if ( $dbConnection->getType() === 'mysql' ) {

			// MW 1.19
			if ( !method_exists( $dbConnection, 'listViews' ) ) {
				throw new RuntimeException( 'DatabaseBase listViews is not available (MW 1.19/1.20)' );
			}

			# bug 43571: cannot clone VIEWs under MySQL
			$views = $dbConnection->listViews(
				self::$MWDB_PREFIX,
				__METHOD__
			);

			$tables = array_diff( $tables, $views );
		}

		$tables = array_map( array( $this, 'unprefixTable' ), $tables );

		// Don't duplicate test tables from the previous failed run
		$tables = array_filter( $tables, array( $this, 'isNotUnittest' ) );

		// @see MediaWikiTestCase::listTables
		if ( $dbConnection->getType() === 'sqlite' ) {
			$tables = array_filter( $tables, array( $this, 'isNotSearchindex' ) );
		}

		return $tables;
	}

	protected function setupUnitTestDatabase() {

		if ( self::$dbSetup ) {
			return true;
		}

		if ( $GLOBALS['wgDBprefix'] === $this->getDBPrefix() ) {
			throw new RuntimeException( 'The database prefix is already set to "' . $this->getDBPrefix() . '"' );
		}

		$this->createTestDatabase();
		$this->store->setup( false );
//		$this->initMediaWikiCoreDBData();

		$this->createPageWithText(
			Title::newFromText( 'SMWUTpage' ),
			'SMW unit test'
		);

		self::$dbSetup = true;
	}

	protected function destroyUnitTestDatabase() {

		if ( !$this->cloneDatabase instanceof CloneDatabase ) {
			return null;
		}

		$this->cloneDatabase->destroy( true );
		self::$dbSetup = false;
	}

	private function createTestDatabase() {

		// MW's DatabaseSqlite does some magic on its own therefore
		// we force our way
		if ( $this->getDBConnection()->getType() === 'sqlite' ) {
			CloneDatabase::changePrefix( self::$MWDB_PREFIX );
		}

		$tablesToBeCloned = $this->generateListOfTables();

		$this->cloneDatabase = new CloneDatabase(
			$this->getDBConnection(),
			$tablesToBeCloned,
			$this->getDBPrefix()
		);

		// Rebuild the DB (in order to exclude temporary table usage)
		// otherwise some tests will fail with
		// "Error: 1137 Can't reopen table" on MySQL (see Issue #80)
		$this->cloneDatabase->useTemporaryTables( false );
		$this->cloneDatabase->cloneTableStructure();
	}

	/**
	 * @see MediaWikiTestCase::addCoreDBData
	 */
	private function initMediaWikiCoreDBData() {

		User::resetIdByNameCache();

		$user = User::newFromName( 'UTSysop' );

		if ( $user->idForName() == 0 ) {
			$user->addToDatabase();
			$user->setPassword( 'UTSysopPassword' );

			$user->addGroup( 'sysop' );
			$user->addGroup( 'bureaucrat' );
			$user->saveSettings();
		}

		$this->createPageWithText(
			Title::newFromText( 'UTPage' ),
			'SMW unit test'
		);
	}

	protected function createPageWithText( Title $title, $text ) {

		$pageCreator = new PageCreator();
		$pageCreator
			->createPage( $title )
			->doEdit( $text );

		return $pageCreator->getPage();
	}

	private function cleanOpenDBTransactions() {

		if ( $this->getDBConnection() ) {

			while ( $this->getDBConnection()->trxLevel() > 0 ) {
				$this->getDBConnection()->rollback();
			}

			$this->getDBConnection()->ignoreErrors( false );
		}
	}

	private function unprefixTable( $tableName ) {
		return substr( $tableName, strlen( self::$MWDB_PREFIX ) );
	}

	private function isNotUnittest( $table ) {
		return strpos( $table, 'unittest_' ) === false;
	}

	private function isNotSearchindex( $table ) {
		return strpos( $table, 'searchindex' ) === false;
	}

	private function isSupportedDatabase() {
		return in_array( $this->getDBConnection()->getType(), $this->supportedDatabase );
	}

}
