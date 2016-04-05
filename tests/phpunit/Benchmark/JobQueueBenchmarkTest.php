<?php

namespace SMW\Tests\Benchmark;

use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
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
	protected $destroyDatabaseTablesAfterRun = false;

	/**
	 * @var BenchmarkRunner
	 */
	private $benchmarkRunner = null;
	private $runnerFactory;

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
		$this->runnerFactory = UtilityFactory::getInstance()->newRunnerFactory();
	}

	/**
	 * @test
	 */
	public function doBenchmark() {

		$dataset = $this->benchmarkRunner->getDefaultDataset();
		$datasetFixture = Title::newFromText( 'Lorem ipsum' );


		$this->benchmarkRunner->addMessage( "\n" . '==========================================================================================' );
		$this->benchmarkRunner->addMessage( 'JobQueue benchmarks' );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );
		$this->benchmarkRunner->addMessage( "- Dataset: " . $dataset );
		$this->benchmarkRunner->addMessage( "- MediaWiki: " . $this->benchmarkRunner->getMediaWikiVersion() );
		$this->benchmarkRunner->addMessage( "- Store: " .  $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( "- ShowMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );
		$this->benchmarkRunner->addMessage( "- ReuseDatasets: " . var_export( $this->reuseDatasets, true ) );
		$this->benchmarkRunner->addMessage( "- PageCopyThreshold: " . $this->pageCopyThreshold );
		$this->benchmarkRunner->addMessage( "- RepetitionExecutionThreshold: " . $this->repetitionExecutionThreshold );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );

		if ( !$this->reuseDatasets ) {
			$this->benchmarkRunner->addMessage( "\n" . 'Data preparation benchmarks' );
			$this->benchmarkRunner->doImportDataset( $dataset );
			$this->benchmarkRunner->copyPageContent( $datasetFixture, $this->pageCopyThreshold );
		}

		$this->assertTrue( $datasetFixture->exists() );

		$refreshJob = new RefreshJob( Title::newFromText( __METHOD__ ) );
		$refreshJob->insert();

		$this->createJobQueueBenchmarks( 'SMW\RefreshJob' );
		$this->createJobQueueBenchmarks( 'SMW\UpdateJob' );
		$this->benchmarkRunner->addMessage( '==========================================================================================' );

		$this->benchmarkRunner->printMessages();
	}

	private function createJobQueueBenchmarks( $job ) {

		$jobQueueRunner = $this->runnerFactory->newJobQueueRunner( $job );

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

		$this->benchmarkRunner->addMessage( "- $job: $norm (n) $mean (mean) $sum (total) (sec)" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( "+- Memory: $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

}
