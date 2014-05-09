<?php

namespace SMW\Tests;

use SMW\Tests\Util\MwUnitTestDatabaseInstaller;
use SMW\StoreFactory;

use RuntimeException;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
abstract class MwDBaseUnitTestCase extends \PHPUnit_Framework_TestCase {

	/* @var MwUnitTestDatabaseInstaller */
	protected $mwUnitTestDatabaseInstaller = null;

	protected $destroyTestDatabaseInstance = true;
	protected $isUsableUnitTestDatabase = true;
	protected $databaseToBeExcluded = null;

	/**
	 * It is assumed that each test that makes use of the TestCase is requesting
	 * a "real" DB connection
	 */
	public function run( \PHPUnit_Framework_TestResult $result = null ) {

		$this->mwUnitTestDatabaseInstaller = new MwUnitTestDatabaseInstaller( $this->getStore() );
		$this->mwUnitTestDatabaseInstaller->removeSupportedDatabase( $this->databaseToBeExcluded );

		try {
			$this->mwUnitTestDatabaseInstaller->setup();
		} catch ( RuntimeException $e ) {
			$this->isUsableUnitTestDatabase = false;
		}

		parent::run( $result );

		if ( $this->isUsableUnitTestDatabase && $this->destroyTestDatabaseInstance ) {
			$this->mwUnitTestDatabaseInstaller->tearDown();
		}
	}

	protected function removeDatabaseFromTest( $databaseToBeExcluded ) {
		$this->databaseToBeExcluded = $databaseToBeExcluded;
	}

	/**
	 * By default, each test will create a new DB environment to ensure that
	 * only conditions of that particular test are controlled
	 *
	 * It might be that tests within a suite depend on each other therefore
	 * using this option allows to suspend the removal (with its tables) of the
	 * DB instance
	 */
	protected function useSameUnitTestDatabaseInstance() {
		$this->destroyTestDatabaseInstance = false;
	}

	protected function getStore() {
		return StoreFactory::getStore();
	}

	protected function getDBConnection() {
		return $this->mwUnitTestDatabaseInstaller->getDBConnection();
	}

	protected function isUsableUnitTestDatabase() {
		return $this->isUsableUnitTestDatabase;
	}

}
