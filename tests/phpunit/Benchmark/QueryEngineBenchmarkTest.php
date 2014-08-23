<?php

namespace SMW\Tests\Benchmark;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\DIProperty;
use SMWPropertyValue as PropertyValue;
use SMWPrintRequest as PrintRequest;

use SMWQueryParser as QueryParser;
use SMWQuery as Query;
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
class QueryEngineBenchmarkTest extends MwDBaseUnitTestCase {

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
		'count'     => array(),
		'instance'  => array(),
		'serialize' => array(),
		'instance (n)'  => array(),
		'serialize (n)' => array(),
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
		$this->benchmarkRunner->addMessage( " |- queryLimit: " . $this->queryLimit );
		$this->benchmarkRunner->addMessage( " |- queryOffset: " . $this->queryOffset );

		if ( !$datasetFixture->exists() || !$this->reuseDatasets ) {
			$this->benchmarkRunner->addMessage( "\n" . 'Data preparation benchmarks' );
			$this->benchmarkRunner->doImportXmlDatasetFixture( __DIR__ . '/'. 'Fixtures' . '/' . $dataset );
			$this->benchmarkRunner->copyPageContentFrom( $datasetFixture, $this->pageCopyThreshold );
		}

		$this->assertTrue( $datasetFixture->exists() );

		$this->benchmarkRunner->addMessage( "\n" . 'Query result benchmarks (C = count, I = instance, S = serialization)' );
		$this->createQueryBenchmarks();

		$this->benchmarkRunner->printMessages();
	}

	private function createQueryBenchmarks() {

		// $queryCondition, $printouts, $comments
		$querySets = array(
			array( '[[:+]]', array(), '' ),
			array( '[[Category: Lorem ipsum]]', array(), '' ),
			array( '[[Category: Lorem ipsum]] AND [[Property:+]]', array(), '' ),
			array( '[[Has Url::+]]', array( 'Has Url' ), '(includes subobjects)' ),
			array( '[[Has quantity::+]]', array( 'Has quantity' ), '(includes subobjects)' ),
			array( '[[Has Url::+]][[Category: Lorem ipsum]]', array( 'Has Url' ), '(does not include subobjects)' ),
			array( '[[Has number::1111]] AND [[Has quantity::25 sqmi]]', array( 'Has number', 'Has quantity' ), '(only subobjects)' ),
			array( '[[Has number::1111]] OR [[Has quantity::25 sqmi]]', array( 'Has number', 'Has quantity' ), '(only subobjects)' ),
			array( '[[Has date::1 Jan 2014]]', array( 'Has date' ), '(does not include subobjects)' ),
			array( '[[Has text::~Lorem ipsum dolor*]]', array( 'Has text' ), '(does not include subobjects)' ),
		);

		foreach ( $querySets as $setNumber => $querySet ) {
			$this->createCombinedQuerySetBenchmark( $setNumber, $querySet[0], $querySet[1], $querySet[2] );
		}

		$setCount = count( $querySets );

		$this->benchmarkRunner->addMessage( "\n" . "Benchmark summary (mean for query sets of $setCount, n = normalized over the result count)" );

		foreach ( $this->benchmarkSummaryContainer as $key => $container ) {
			$value = round( array_sum( $container ) / $setCount, 7 );
			$this->benchmarkRunner->addMessage( " |- $value $key" );
		}
	}

	private function createCombinedQuerySetBenchmark( $setNumber, $queryCondition, $printouts = array(), $comments = '' ) {

		$this->benchmarkRunner->addMessage( "($setNumber) " . $queryCondition . ' ' . $comments );

		$query = $this->createQuery( $queryCondition, Query::MODE_COUNT );
		$this->benchmarkQueryResultSerialization( $this->benchmarkQueryExecution( $query ) );

		$query = $this->createQuery( $queryCondition, Query::MODE_INSTANCES, $printouts );
		$this->benchmarkQueryResultSerialization( $this->benchmarkQueryExecution( $query ) );
	}

	private function benchmarkQueryExecution( Query $query ) {

		$repetitionTimeContainer = array();
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->repetitionExecutionThreshold; $i++ ) {
			$start = microtime( true );
			$queryResult = $this->getStore()->getQueryResult( $query );
			$repetitionTimeContainer[] = round( microtime( true ) - $start, 7 );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = array_sum( $repetitionTimeContainer );
		$mean = $sum / $this->repetitionExecutionThreshold;

		if ( $query->querymode === Query::MODE_COUNT ) {
			$count = $queryResult instanceof QueryResult ? $queryResult->getCountValue() : $queryResult;
			$columnCount = 0;
			$mode  = 'C';
			$this->benchmarkSummaryContainer['count'][] = $mean;
		} else {
			$count = $queryResult->getCount();
			$columnCount = $queryResult->getColumnCount();
			$mode  = 'I';
			$this->benchmarkSummaryContainer['instance'][] = $mean;
			$this->benchmarkSummaryContainer['instance (n)'][] = $mean / $count;
		}

		$this->benchmarkRunner->addMessage( " $mode- $mean (mean) $sum (total) (sec) resultCount: $count columnCount: $columnCount" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( " +-- $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}

		return $queryResult;
	}

	private function benchmarkQueryResultSerialization( $queryResult ) {

		if ( !$queryResult instanceof QueryResult || $queryResult->getCount() == 0 ) {
			$this->benchmarkRunner->addMessage( " S-- no serialization" );
			return;
		}

		$repetitionTimeContainer = array();
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->repetitionExecutionThreshold; $i++ ) {
			$start = microtime( true );
			$queryResult->toArray();
			$repetitionTimeContainer[] = round( microtime( true ) - $start, 7 );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = array_sum( $repetitionTimeContainer );
		$mean = $sum / $this->repetitionExecutionThreshold;

		$this->benchmarkSummaryContainer['serialize'][] = $mean;
		$this->benchmarkSummaryContainer['serialize (n)'][] = $mean / $queryResult->getCount();

		$this->benchmarkRunner->addMessage( " S-- $mean (mean) $sum (total) (sec)" );

		if ( $this->showMemoryUsage ) {
			$this->benchmarkRunner->addMessage( " +--- $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
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
