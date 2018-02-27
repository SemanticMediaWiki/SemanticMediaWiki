<?php

namespace SMW\Tests\Utils;

use CloneDatabase;
use HashBagOStuff;
use ObjectCache;
use RuntimeException;
use SMW\Connection\ConnectionProvider;
use SMW\Store;
use Title;

/**
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class MediaWikiTestDatabaseTableBuilder {

	/**
	 * @var MediaWikiTestDatabaseTableBuilder
	 */
	private static $instance = null;

	/**
	 * @var Store
	 */
	protected $store;

	/**
	 * @var ConnectionProvider
	 */
	protected $connectionProvider;

	/**
	 * @var CloneDatabase
	 */
	protected $cloneDatabase;

	protected $defaultDatabaseTypes = array(
		'mysql',
		'sqlite',
		'postgres'
	);

	protected $availableDatabaseTypes = array();

	private static $UTDB_PREFIX = null;
	private static $MWDB_PREFIX = null;

	private $dbSetup = false;

	/**
	 * @since 2.0
	 *
	 * @param Store $store
	 * @param connectionProvider $connectionProvider
	 */
	public function __construct( Store $store, ConnectionProvider $connectionProvider ) {
		$this->store = $store;
		$this->connectionProvider = $connectionProvider;
		$this->availableDatabaseTypes = $this->defaultDatabaseTypes;

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
	 * @since 2.0
	 *
	 * @param Store $store
	 *
	 * @return self
	 */
	public static function getInstance( Store $store ) {

		if ( self::$instance === null ) {
			self::$instance = new self( $store, new MediaWikiTestConnectionProvider() );
		}

		return self::$instance;
	}

	/**
	 * @since 2.0
	 *
	 * @param string|null $databaseType
	 *
	 * @return self
	 */
	public function removeAvailableDatabaseType( $databaseType = null ) {
		$this->availableDatabaseTypes = array_diff( $this->defaultDatabaseTypes, (array)$databaseType );
		return $this;
	}

	/**
	 * @since 2.0
	 *
	 * @throws RuntimeException
	 */
	public function doBuild() {

		if ( !$this->isAvailableDatabaseType() ) {
			throw new RuntimeException( 'Requested DB type is not available through this installer' );
		}

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

		$this->setupDatabaseTables();
		$this->rollbackOpenDatabaseTransactions();
	}

	/**
	 * @since 2.0
	 */
	public function doDestroy() {
		$this->destroyDatabaseTables();
		$this->rollbackOpenDatabaseTransactions();
	}

	/**
	 * @since  2.0
	 *
	 * @return string
	 */
	public function getDBPrefix() {
		return self::$UTDB_PREFIX;
	}

	/**
	 * @since  2.0
	 *
	 * @return DatabaseBase
	 */
	public function getDBConnection() {
		return $this->connectionProvider->getConnection();
	}

	/**
	 * @since  2.0
	 *
	 * @return connectionProvider
	 */
	public function getconnectionProvider() {
		return $this->connectionProvider;
	}

	/**
	 * @see MediaWikiTestCase::listTables
	 */
	protected function generateListOfTables() {

		$dbConnection = $this->getDBConnection();

		$tables = $dbConnection->listTables(
			self::$MWDB_PREFIX,
			__METHOD__
		);

		if ( $dbConnection->getType() === 'mysql' && method_exists( $dbConnection, 'listViews' ) ) {

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

	private function setupDatabaseTables() {

		if ( $this->dbSetup ) {
			return true;
		}

		if ( $GLOBALS['wgDBprefix'] === $this->getDBPrefix() ) {
			throw new RuntimeException( 'The database prefix is already set to "' . $this->getDBPrefix() . '"' );
		}

		$this->cloneDatabaseTables();
		$this->store->setup( false );
		$this->createDummyPage();

		$this->dbSetup = true;
	}

	private function cloneDatabaseTables() {

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

		// Ensure no leftovers
		if ( $this->getDBConnection()->getType() === 'postgres' ) {
			$this->cloneDatabase->destroy( true );
		}

		// Rebuild the DB (in order to exclude temporary table usage)
		// otherwise some tests will fail with
		// "Error: 1137 Can't reopen table" on MySQL (see Issue #80)
		$this->cloneDatabase->useTemporaryTables( false );
		$this->cloneDatabase->cloneTableStructure();
	}

	private function createDummyPage() {

		$pageCreator = new PageCreator();
		$pageCreator
			->createPage( Title::newFromText( 'SMWUTDummyPage' )  )
			->doEdit( 'SMW dummy page' );
	}

	private function destroyDatabaseTables() {

		if ( !$this->cloneDatabase instanceof CloneDatabase ) {
			throw new RuntimeException( 'CloneDatabase instance is missing, unable to destory the database tables' );
		}

		$this->cloneDatabase->destroy( true );
		$this->availableDatabaseTypes = $this->defaultDatabaseTypes;
		$this->dbSetup = false;
	}

	private function rollbackOpenDatabaseTransactions() {

		if ( $this->getDBConnection() ) {

			while ( $this->getDBConnection()->trxLevel() > 0 ) {
				$this->getDBConnection()->rollback();
			}

			// 1.26 was mde protected
			// $this->getDBConnection()->ignoreErrors( false );
		}
	}

	private function unprefixTable( $tableName ) {
		return substr( $tableName, strlen( self::$MWDB_PREFIX ) );
	}

	private function isNotUnittest( $table ) {
		return strpos( $table, 'unittest_' ) === false;
	}

	private function isNotSearchindex( $table ) {
		return strpos( $table, 'searchindex' ) === false && strpos( $table, 'smw_ft_search' ) === false;
	}

	private function isAvailableDatabaseType() {
		return in_array( $this->getDBConnection()->getType(), $this->availableDatabaseTypes );
	}

}
