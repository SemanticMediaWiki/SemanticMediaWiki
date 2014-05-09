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
 * @grpup mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
abstract class MwDBaseUnitTestCase extends \PHPUnit_Framework_TestCase {

	protected $mwUnitTestDatabaseInstaller = null;
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

		if ( $this->isUsableUnitTestDatabase ) {
			$this->mwUnitTestDatabaseInstaller->tearDown();
		}
	}

	protected function markDatabaseToBeExcludedFromTest( $databaseToBeExcluded ) {
		$this->databaseToBeExcluded = $databaseToBeExcluded;
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
