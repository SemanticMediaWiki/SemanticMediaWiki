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
class PageEditBenchmarkTest extends MwDBaseUnitTestCase {

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

	private	$pageEditThreshold = 50;
	private $showMemoryUsage = false;
	private $reuseDatasets = true;

	protected function setUp() {
		parent::setUp();

		// Variable set using phpunit.xml
		if ( isset( $GLOBALS['benchmarkShowMemoryUsage'] ) ) {
			$this->showMemoryUsage = $GLOBALS['benchmarkShowMemoryUsage'];
		}

		$this->benchmarkRunner = new BenchmarkRunner( $this->showMemoryUsage );
	}

	/**
	 * @test
	 */
	public function doBenchmark() {

		$dataset = 'ExtendedLoremIpsumDataset.v2.xml';

		$this->benchmarkRunner->addMessage( "\n" . '==========================================================================================' );
		$this->benchmarkRunner->addMessage( 'Edit benchmarks (Lorem donec = [[::]]; Lorem enim = #subobject; Lorem sit = #set/template) ' );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );
		$this->benchmarkRunner->addMessage( "- Dataset: " . $dataset );
		$this->benchmarkRunner->addMessage( "- MediaWiki: " . $this->benchmarkRunner->getMediaWikiVersion() );
		$this->benchmarkRunner->addMessage( "- Store: " .  $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( "- ShowMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );
		$this->benchmarkRunner->addMessage( "- PageEditThreshold: " . $this->pageEditThreshold );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );

		$this->benchmarkRunner->doImportDataset( $dataset );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );

		// Standard annotation
		$datasetFixture = Title::newFromText( 'Lorem donec' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		// Subobject annotation
		$datasetFixture = Title::newFromText( 'Lorem enim' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		// Template
		$datasetFixture = Title::newFromText( 'Lorem sit' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		// Queries
		$datasetFixture = Title::newFromText( 'Lorem tempor' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		$this->benchmarkRunner->addMessage( '==========================================================================================' );
		$this->benchmarkRunner->printMessages();
	}

}
