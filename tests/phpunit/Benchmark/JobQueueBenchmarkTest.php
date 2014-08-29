<?php

namespace SMW\Tests\Benchmark;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\JobQueueRunner;

use SMW\MediaWiki\Jobs\RefreshJob;

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
class JobQueueBenchmarkTest extends MwDBaseUnitTestCase {

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

	private	$repetitionExecutionThreshold = 1;
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

		$this->benchmarkRunner = new BenchmarkRunner();
	}

	/**
	 * @test
	 */
	public function doBenchmark() {

		$dataset = $this->benchmarkRunner->getDefaultDataset();
		$datasetFixture = Title::newFromText( 'Lorem ipsum' );

		$this->benchmarkRunner->addMessage( "\n" . "Use $dataset on MW " . $this->benchmarkRunner->getMediaWikiVersion() . ', ' . $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( " |- repetitionExecutionThreshold: " . $this->repetitionExecutionThreshold );
		$this->benchmarkRunner->addMessage( " |- pageCopyThreshold: " . $this->pageCopyThreshold );
		$this->benchmarkRunner->addMessage( " |- showMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );
		$this->benchmarkRunner->addMessage( " |- reuseDatasets: " . var_export( $this->reuseDatasets, true ) );

		if ( !$this->reuseDatasets ) {
			$this->benchmarkRunner->addMessage( "\n" . 'Data preparation benchmarks' );
			$this->benchmarkRunner->doImportDataset( $dataset );
			$this->benchmarkRunner->copyPageContent( $datasetFixture, $this->pageCopyThreshold );
		}

		$this->assertTrue( $datasetFixture->exists() );

		$this->benchmarkRunner->addMessage( "\n" . 'JobQueue benchmarks' );

		$refreshJob = new RefreshJob( Title::newFromText( __METHOD__ ) );
		$refreshJob->insert();

		$this->createJobQueueBenchmarks( 'SMW\RefreshJob' );
		$this->createJobQueueBenchmarks( 'SMW\UpdateJob' );

		$this->benchmarkRunner->printMessages();
	}

	private function createJobQueueBenchmarks( $job ) {

		$jobQueueRunner = new JobQueueRunner( $job );

		$this->benchmarkRunner->getBenchmarker()->clear();
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->repetitionExecutionThreshold; $i++ ) {
			$start = microtime( true );
			$jobQueueRunner->run();
			$this->benchmarkRunner->getBenchmarker()->addBenchmarkPoint( microtime( true ) - $start );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = $this->benchmarkRunner->getBenchmarker()->getSum();
		$mean = $this->benchmarkRunner->getBenchmarker()->getMean();
		$norm = $this->benchmarkRunner->getBenchmarker()->getNormalizedValueBy( $job === 'SMW\RefreshJob' ? 1 : $this->pageCopyThreshold );

		$this->benchmarkRunner->addMessage( " |- $job $norm (n) $mean (mean) $sum (total) (sec)" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( " +-- $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

}
