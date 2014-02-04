<?php

namespace SMW\Test;

use SMW\StoreFactory;
use SMW\UpdateJob;

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
 * @since 1.9.0.3
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

		$importer = new MwImporter( $this->getSourceFile() );
		$importer->setVerbose( true );

		if ( !$importer->run() ) {
			$importer->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}

		$this->assertTitleIsKnownAfterImport( $this->acquirePoolOfTitles() );
		$this->scheduleUpdateJobs( $this->acquirePoolOfTitles() );
		$this->assertDataImport();

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
			$title = $this->makeTitle( $title );

			$this->assertEquals(
				$isExpected,
				$title->exists(),
				__METHOD__ . "Assert title {$title}"
			);
		}
	}

	private function scheduleUpdateJobs( $titles ) {
		foreach ( $titles as $title ) {
			$job = new UpdateJob( $this->makeTitle( $title ) );
			$job->run();
		}
	}

	private function makeTitle( $title ) {

		if ( $title instanceof Title ) {
			return $title;
		}

		if ( is_array( $title ) ) {
			return Title::newFromText( $title[0], $title[1] );
		}

		return Title::newFromText( $title );
	}

}