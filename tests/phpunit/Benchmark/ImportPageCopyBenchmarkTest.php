<?php

namespace SMW\Tests\Benchmark;

use SMW\Tests\MwDBaseUnitTestCase;

use Title;

/**
 * @group semantic-mediawiki-benchmark
 * @large
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ImportPageCopyBenchmarkTest extends MwDBaseUnitTestCase {

	/**
	 * @var array
	 */
	protected $databaseToBeExcluded = array( 'postgres', 'sqlite' );

	/**
	 * @var boolean
	 */
	protected $destroyDatabaseTablesAfterRun = false;

	/**
	 * @var BenchmarkRunner
	 */
	private $benchmarkRunner = null;

	private	$pageCopyThreshold = 50;
	private $showMemoryUsage = false;
	private $reuseDatasets = true;

	protected function setUp() {
		parent::setUp();

		// Variable set using phpunit.xml
		if ( isset( $GLOBALS['benchmarkPageCopyThreshold'] ) ) {
			$this->pageCopyThreshold = $GLOBALS['benchmarkPageCopyThreshold'];
		}

		if ( isset( $GLOBALS['benchmarkShowMemoryUsage'] ) ) {
			$this->showMemoryUsage = $GLOBALS['benchmarkShowMemoryUsage'];
		}

		if ( isset( $GLOBALS['benchmarkReuseDatasets'] ) ) {
			$this->reuseDatasets = $GLOBALS['benchmarkReuseDatasets'];
		}

		$this->benchmarkRunner = new BenchmarkRunner( $this->showMemoryUsage );
		//$this->getStore()->getConnection( 'sparql' )->deleteAll();
	}

	/**
	 * @test
	 */
	public function doBenchmark() {

		$dataset = $this->benchmarkRunner->getDefaultDataset();

		$this->benchmarkRunner->addMessage( "\n" . '==========================================================================================' );
		$this->benchmarkRunner->addMessage( 'Dataset import benchmarks' );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );
		$this->benchmarkRunner->addMessage( "- Dataset: " . $dataset );
		$this->benchmarkRunner->addMessage( "- MediaWiki: " . $this->benchmarkRunner->getMediaWikiVersion() );
		$this->benchmarkRunner->addMessage( "- Store: " .  $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( "- ShowMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );
		$this->benchmarkRunner->addMessage( "- ReuseDatasets: " . var_export( $this->reuseDatasets, true ) );
		$this->benchmarkRunner->addMessage( "- PageCopyThreshold: " . $this->pageCopyThreshold );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );

		$this->benchmarkRunner->doImportDataset( $dataset );

		$datasetFixture = Title::newFromText( 'Lorem ipsum' );
		$this->assertTrue( $datasetFixture->exists() );

		$this->benchmarkRunner->copyPageContent( $datasetFixture, $this->pageCopyThreshold );
		$this->benchmarkRunner->addMessage( '==========================================================================================' );

		$this->benchmarkRunner->printMessages();
	}

}
