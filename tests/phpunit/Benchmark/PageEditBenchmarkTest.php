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
	protected $destroyDatabaseTablesOnEachRun = false;

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

		$dataset = 'ExtendedLoremIpsumDataset.v1.xml';

		$this->benchmarkRunner->addMessage( "\n" . "Use $dataset on MW " . $this->benchmarkRunner->getMediaWikiVersion() . ', ' . $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( " |- pageEditThreshold: " . $this->pageEditThreshold );
		$this->benchmarkRunner->addMessage( " |- showMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );

		$this->benchmarkRunner->addMessage( "\n" . 'Dataset import benchmarks' );
		$this->benchmarkRunner->doImportDataset( $dataset );

		$this->benchmarkRunner->addMessage( "\n" . 'Edit benchmarks (Lorem donec = [[::]]; Lorem enim = #subobject; Lorem sit = #set/template) ' );

		$datasetFixture = Title::newFromText( 'Lorem donec' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		$datasetFixture = Title::newFromText( 'Lorem enim' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		$datasetFixture = Title::newFromText( 'Lorem sit' );
		$this->assertTrue( $datasetFixture->exists() );
		$this->benchmarkRunner->editPageContent( $datasetFixture, $this->pageEditThreshold );

		$this->benchmarkRunner->printMessages();
	}

}
