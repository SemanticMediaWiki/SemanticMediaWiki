<?php

namespace SMW\Test;

use SMW\Tests\Util\PageDeleter;
use SMW\Tests\Util\XmlImportRunner;

use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\StoreFactory;

use Title;

/**
 * Mostly runs regression and integration tests to verify cross-functional
 * interaction with MediaWiki
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
abstract class MwRegressionTestCase extends \MediaWikiTestCase {

	protected $databaseIsUsable = false;
	protected $expectedAssertions = 0;

	/**
	 * @see MediaWikiTestCase::run
	 *
	 * Only where teardownTestDB is available (excludes 1.19/1.20 or you need to
	 * run phpunit ... --use-normal-tables) we are able to rebuild the DB (in
	 * order to exclude temporary table usage) otherwise some tests will fail with
	 * "Error: 1137 Can't reopen table" on MySQL (see Issue #80)
	 */
	function run( \PHPUnit_Framework_TestResult $result = null ) {

		if ( method_exists( $this, 'teardownTestDB' ) ) {
			$this->databaseIsUsable = true;
			$this->teardownTestDB();
			$this->setCliArg( 'use-normal-tables', true );
		}

		parent::run( $result );
	}

	protected function tearDown() {
		parent::tearDown();
		$this->cleanCloneTablesAfterTestRun();
	}

	/**
	 * Specifies the source file
	 *
	 * @return string
	 */
	public abstract function getSourceFile();

	/**
	 * Specifies a pool of titles that are expected to be imported
	 *
	 * @return array
	 */
	public abstract function acquirePoolOfTitles();

	/**
	 * Main asserts are implemented by the subclass
	 */
	public abstract function assertDataImport();

	public function testPoolOfTitlesAreNotKnownPriorImport() {
		$this->assertTitleIsNotKnownBeforeImport( $this->acquirePoolOfTitles() );
	}

	/**
	 * @note It is suggested not to add other "test..." unless you want to
	 * re-import the data
	 *
	 * @depends testPoolOfTitlesAreNotKnownPriorImport
	 */
	public function testDataImport() {

		if ( !$this->databaseIsUsable ) {
			$this->markTestSkipped(
				'DB setup did not satisfy the test requirements (probably MW 1.19/1.20)'
			);
		}

		$importRunner = new XmlImportRunner( $this->getSourceFile() );
		$importRunner->setVerbose( true );

		if ( !$importRunner->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}

		$this->assertTitleIsKnownAfterImport( $this->acquirePoolOfTitles() );
		$this->runUpdateJobs( $this->acquirePoolOfTitles() );
		$this->assertDataImport();
		$this->deletePoolOfTitles( $this->acquirePoolOfTitles() );
	}

	protected function getStore() {
		return StoreFactory::getStore();
	}

	private function assertTitleIsNotKnownBeforeImport( $titles ) {
		$this->assertTitleExists( false, $titles );
	}

	private function assertTitleIsKnownAfterImport( $titles ) {
		$this->assertTitleExists( true, $titles );
	}

	private function assertTitleExists( $isExpected, $titles ) {
		foreach ( $titles as $title ) {
			$this->assertEquals(
				$isExpected,
				Title::newFromText( $title )->exists(),
				__METHOD__ . "Assert title {$title}"
			);
		}
	}

	private function runUpdateJobs( $titles ) {
		foreach ( $titles as $title ) {
			$job = new UpdateJob( Title::newFromText( $title ) );
			$job->run();
		}
	}

	private function deletePoolOfTitles( $titles ) {
		$pageDeleter = new PageDeleter();

		foreach ( $titles as $title ) {
			$pageDeleter->deletePage( Title::newFromText( $title ) );
		}
	}

	private function cleanCloneTablesAfterTestRun() {

		if ( !$this->databaseIsUsable ) {
			return;
		}

		$tables = self::listTables( $this->db );

		foreach ( $tables as $table ) {
			$this->db->dropTable( $table, __METHOD__ );
		}
	}

}