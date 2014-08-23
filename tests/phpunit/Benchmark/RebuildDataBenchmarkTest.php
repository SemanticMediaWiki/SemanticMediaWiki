<?php

namespace SMW\Tests\Benchmark;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\MaintenanceRunner;

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
class RebuildDataBenchmarkTest extends MwDBaseUnitTestCase {

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

		$this->fullDelete = true;

		$this->benchmarkRunner = new BenchmarkRunner();
	}

	/**
	 * @test
	 */
	public function doBenchmark() {

		$dataset = 'GenericLoremIpsumDataset.v1.xml';
		$datasetFixture = Title::newFromText( 'Lorem ipsum' );

		$this->benchmarkRunner->addMessage( "\n" . "Use $dataset on MW " . $GLOBALS['wgVersion'] . ', ' . $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( " |- repetitionExecutionThreshold: " . $this->repetitionExecutionThreshold );
		$this->benchmarkRunner->addMessage( " |- pageCopyThreshold: " . $this->pageCopyThreshold );
		$this->benchmarkRunner->addMessage( " |- showMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );
		$this->benchmarkRunner->addMessage( " |- reuseDatasets: " . var_export( $this->reuseDatasets, true ) );
		$this->benchmarkRunner->addMessage( " |- fullDelete: " . var_export( $this->fullDelete, true ) );

		if ( !$datasetFixture->exists() || !$this->reuseDatasets ) {
			$this->benchmarkRunner->addMessage( "\n" . 'Data preparation benchmarks' );
			$this->benchmarkRunner->doImportXmlDatasetFixture( __DIR__ . '/'. 'Fixtures' . '/' . $dataset );
			$this->benchmarkRunner->copyPageContentFrom( $datasetFixture, $this->pageCopyThreshold );
		}

		$this->assertTrue( $datasetFixture->exists() );

		$this->benchmarkRunner->addMessage( "\n" . 'RebuildData benchmarks' );
		$this->createRebuildDataBenchmarks();

		$this->benchmarkRunner->printMessages();
	}

	private function createRebuildDataBenchmarks() {

		$maintenanceRunner = new MaintenanceRunner( 'SMW\Maintenance\RebuildData' );
		$maintenanceRunner->setQuiet();
		$maintenanceRunner->setOptions( array( 'f' => $this->fullDelete ) );

		$repetitionTimeContainer = array();
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->repetitionExecutionThreshold; $i++ ) {
			$start = microtime( true );
			$maintenanceRunner->run();
			$repetitionTimeContainer[] = round( microtime( true ) - $start, 7 );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = array_sum( $repetitionTimeContainer );
		$mean = $sum / $this->repetitionExecutionThreshold;

		$this->benchmarkRunner->addMessage( " |- $mean (mean) $sum (total) (sec)" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( " +-- $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

}
