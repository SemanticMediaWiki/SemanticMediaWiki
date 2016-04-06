<?php

namespace SMW\Tests\Benchmark;

use SMW\DIProperty;
use SMW\Query\PrintRequest as PrintRequest;
use SMW\Tests\MwDBaseUnitTestCase;
use SMWPropertyValue as PropertyValue;
use SMWQuery as Query;
use SMWQueryParser as QueryParser;
use SMWQueryResult as QueryResult;
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
abstract class QueryEngineBenchmark extends MwDBaseUnitTestCase {

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

	/**
	 * @var QueryParser
	 */
	private $queryParser;

	private	$queryLimit = 1000;
	private	$queryOffset = 0;
	private	$pageCopyThreshold = 50;
	private $repetitionExecutionThreshold = 5;
	private $showMemoryUsage = false;
	private $reuseDatasets = true;

	private $benchmarkSummaryContainer = array(
		'(t) count'     => array(),
		'(t) instance'  => array(),
		'(t) serialize' => array(),
		'(n) instance'  => array(),
		'(n) serialize' => array(),
	);

	protected function setUp() {
		parent::setUp();

		// Variable set using phpunit.xml
		if ( isset( $GLOBALS['benchmarkQueryRepetitionExecutionThreshold'] ) ) {
			$this->repetitionExecutionThreshold = $GLOBALS['benchmarkQueryRepetitionExecutionThreshold'];
		}

		if ( isset( $GLOBALS['benchmarkPageCopyThreshold'] ) ) {
			$this->pageCopyThreshold = $GLOBALS['benchmarkPageCopyThreshold'];
		}

		if ( isset( $GLOBALS['benchmarkShowMemoryUsage'] ) ) {
			$this->showMemoryUsage = (bool)$GLOBALS['benchmarkShowMemoryUsage'];
		}

		if ( isset( $GLOBALS['benchmarkQueryLimit'] ) ) {
			$this->queryLimit = $GLOBALS['benchmarkQueryLimit'];
		}

		if ( isset( $GLOBALS['benchmarkQueryOffset'] ) ) {
			$this->queryOffset = $GLOBALS['benchmarkQueryOffset'];
		}

		if ( isset( $GLOBALS['benchmarkReuseDatasets'] ) ) {
			$this->reuseDatasets = $GLOBALS['benchmarkReuseDatasets'];
		}

		$this->queryParser = new QueryParser();
		$this->benchmarkRunner = new BenchmarkRunner();
	}

	/**
	 * @return array
	 */
	abstract public function getQuerySetProvider();

	/**
	 * @test
	 */
	public function doBenchmark() {

		$dataset = $this->benchmarkRunner->getDefaultDataset();
		$datasetFixture = Title::newFromText( 'Lorem ipsum' );

		$this->benchmarkRunner->addMessage( "\n" . '==========================================================================================' );
		$this->benchmarkRunner->addMessage( 'Query result benchmarks (C = count, I = instance, S = serialization)' );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );
		$this->benchmarkRunner->addMessage( "- Dataset: " . $dataset );
		$this->benchmarkRunner->addMessage( "- MediaWiki: " . $this->benchmarkRunner->getMediaWikiVersion() );
		$this->benchmarkRunner->addMessage( "- Store: " .  $this->benchmarkRunner->getQueryEngine() );
		$this->benchmarkRunner->addMessage( "- ShowMemoryUsage: " . var_export( $this->showMemoryUsage, true ) );
		$this->benchmarkRunner->addMessage( "- ReuseDatasets: " . var_export( $this->reuseDatasets, true ) );
		$this->benchmarkRunner->addMessage( "- QueryLimit: " . $this->queryLimit );
		$this->benchmarkRunner->addMessage( "- QueryOffset: " . $this->queryOffset );
		$this->benchmarkRunner->addMessage( "- PageCopyThreshold: " . $this->pageCopyThreshold );
		$this->benchmarkRunner->addMessage( "- RepetitionExecutionThreshold: " . $this->repetitionExecutionThreshold );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );

		if ( !$this->reuseDatasets ) {
			$this->benchmarkRunner->addMessage( "\n" . 'Data preparation benchmarks' );
			$this->benchmarkRunner->doImportDataset( $dataset );
			$this->benchmarkRunner->copyPageContent( $datasetFixture, $this->pageCopyThreshold );
		}

		$this->assertTrue( $datasetFixture->exists() );

		$this->createQueryBenchmarks( $this->getQuerySetProvider() );
		$this->benchmarkRunner->addMessage( '==========================================================================================' );

		$this->benchmarkRunner->printMessages();
	}

	private function createQueryBenchmarks( array $querySets ) {

		foreach ( $querySets as $setNumber => $querySet ) {
			$this->createCombinedQuerySetBenchmark( $setNumber, $querySet[0], $querySet[1], $querySet[2] );
		}

		$setCount = count( $querySets );

		$this->benchmarkRunner->addMessage( "Benchmark summary (for $setCount query sets, t = total, n = normalized, sd = standard deviation)" );
		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );

		foreach ( $this->benchmarkSummaryContainer as $key => $container ) {

			$this->benchmarkRunner->getBenchmarker()->clear()->addBenchmarkPoints( $container );

			$mean = $this->benchmarkRunner->getBenchmarker()->getMean();
			$sd   = $this->benchmarkRunner->getBenchmarker()->getStandardDeviation();

			$this->benchmarkRunner->addMessage( "- $mean $key $sd (sd)" );
		}
	}

	private function createCombinedQuerySetBenchmark( $setNumber, $queryCondition, $printouts = array(), $comments = '' ) {

		$this->benchmarkRunner->addMessage( "- ($setNumber): " . $queryCondition . ' ' . $comments );

		$query = $this->createQuery( $queryCondition, Query::MODE_COUNT );
		$this->benchmarkQueryResultSerialization( $this->benchmarkQueryExecution( $query ) );

		$query = $this->createQuery( $queryCondition, Query::MODE_INSTANCES, $printouts );
		$this->benchmarkQueryResultSerialization( $this->benchmarkQueryExecution( $query ) );

		$this->benchmarkRunner->addMessage( '------------------------------------------------------------------------------------------' );
	}

	private function benchmarkQueryExecution( Query $query ) {

		$this->benchmarkRunner->getBenchmarker()->clear()->roundBy( 7 );
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->repetitionExecutionThreshold; $i++ ) {
			$start = microtime( true );
			$queryResult = $this->getStore()->getQueryResult( $query );
			$this->benchmarkRunner->getBenchmarker()->addBenchmarkPoint( microtime( true ) - $start );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = $this->benchmarkRunner->getBenchmarker()->getSum();
		$mean = $this->benchmarkRunner->getBenchmarker()->getMean();
		$sd   = $this->benchmarkRunner->getBenchmarker()->getStandardDeviation();

		if ( $query->querymode === Query::MODE_COUNT ) {
			$count = $queryResult instanceof QueryResult ? $queryResult->getCountValue() : $queryResult;
			$columnCount = 0;
			$mode  = 'C';
			$norm = $this->benchmarkRunner->getBenchmarker()->getNormalizedValueBy( $count );
			$this->benchmarkSummaryContainer['(t) count'][] = $mean;
		} else {
			$count = $queryResult->getCount();
			$columnCount = $queryResult->getColumnCount();
			$mode  = 'I';
			$norm = $this->benchmarkRunner->getBenchmarker()->getNormalizedValueBy( $count );
			$this->benchmarkSummaryContainer['(t) instance'][] = $mean;
			$this->benchmarkSummaryContainer['(n) instance'][] = $norm;
		}

		$this->benchmarkRunner->addMessage( "- $mode: $mean (mean) $sum (total) $sd (sd) (sec) resultCount: $count columnCount: $columnCount" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( "+- Memory: $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}

		return $queryResult;
	}

	private function benchmarkQueryResultSerialization( $queryResult ) {

		if ( !$queryResult instanceof QueryResult || $queryResult->getCount() == 0 ) {
			$this->benchmarkRunner->addMessage( "- S: no serialization" );
			return;
		}

		$this->benchmarkRunner->getBenchmarker()->clear();
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->repetitionExecutionThreshold; $i++ ) {
			$start = microtime( true );
			$queryResult->toArray();
			$this->benchmarkRunner->getBenchmarker()->addBenchmarkPoint( microtime( true ) - $start );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = $this->benchmarkRunner->getBenchmarker()->getSum();
		$mean = $this->benchmarkRunner->getBenchmarker()->getMean();
		$sd   = $this->benchmarkRunner->getBenchmarker()->getStandardDeviation();
		$norm = $this->benchmarkRunner->getBenchmarker()->getNormalizedValueBy( $queryResult->getCount() );

		$this->benchmarkSummaryContainer['(t) serialize'][] = $mean;
		$this->benchmarkSummaryContainer['(n) serialize'][] = $norm;

		$this->benchmarkRunner->addMessage( "- S: $mean (mean) $sum (total) $sd (sd) (sec)" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( "+- Memory: $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

	private function createQuery( $queryString, $mode, array $printouts = array() ) {

		$description = $this->queryParser->getQueryDescription( $queryString );

		foreach ( $printouts as $printout ) {
			$property = DIProperty::newFromUserLabel( $printout );

			$propertyValue = new PropertyValue( '__pro' );
			$propertyValue->setDataItem( $property );

			$description->addPrintRequest(
				new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
			);
		}

		$query = new Query(
			$description,
			false,
			false
		);

		$query->setUnboundlimit( $this->queryLimit );
		$query->setOffset( $this->queryOffset );
		$query->querymode = $mode;

		return $query;
	}

}
