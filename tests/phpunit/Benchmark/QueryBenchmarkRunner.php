<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\DIProperty;
use SMW\Query\PrintRequest as PrintRequest;
use SMW\Store;
use SMWPropertyValue as PropertyValue;
use SMWQuery as Query;
use SMWQueryParser as QueryParser;
use Title;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryBenchmarkRunner implements BenchmarkReporter {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var QueryParser
	 */
	private $queryParser;

	/**
	 * @var Benchmarker
	 */
	private $benchmarker;

	/**
	 * @var array
	 */
	private $benchmarkReport = [];

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param QueryParser $queryParser
	 * @param Benchmarker $benchmarker
	 */
	public function __construct( Store $store, QueryParser $queryParser, Benchmarker $benchmarker ) {
		$this->store = $store;
		$this->queryParser = $queryParser;
		$this->benchmarker = $benchmarker;
	}

	/**
	 * @since 2.5
	 *
	 * @param array
	 */
	public function getBenchmarkReport() {
		return $this->benchmarkReport;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $case
	 */
	public function run( array $case ) {

		$this->benchmarkReport = [];
		$this->benchmarker->clear();

		if ( !isset( $case['query'] ) && !is_array( $case['query']  ) ) {
			throw new RuntimeException( 'Query specification is not available.' );
		}

		if ( !isset( $case['repetitionCount'] ) ) {
			throw new RuntimeException( 'repetitionCount is not available.' );
		}

		$start = microtime( true );
		$queryReports = [];

		$queryReports['count'] = $this->doQuery(
			$case, $this->createQuery( $case, Query::MODE_COUNT )
		);

		$queryReports['instance'] = $this->doQuery(
			$case, $this->createQuery( $case, Query::MODE_INSTANCES )
		);

		$this->benchmarkReport = [
			'type'  => $case['type'],
			'note'  => $case['query']['condition'] . ( isset( $case['note'] ) ? ' (' . $case['note'] . ')' : '' ),
			'query' => $queryReports,
			'time'  => microtime( true ) - $start
		];
	}

	private function doQuery( array $case, $query ) {

		$this->benchmarker->clear();

		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $case['repetitionCount']; $i++ ) {

			$start = microtime( true );

			$queryResult = $this->store->getQueryResult( $query );

			$this->benchmarker->addBenchmarkPoint( microtime( true ) - $start );
		}

		$count = $query->querymode === Query::MODE_COUNT ? $queryResult->getCountValue() : $queryResult->getCount();
		$columnCount = $queryResult->getColumnCount();

		return [
			'rowCount' => $count,
			'columnCount' => $columnCount,
			'repetitionCount' => $case['repetitionCount'],
			"memory" => memory_get_peak_usage( false ) - $memoryBefore,
			"time" => [
				'sum'  => $this->benchmarker->getSum(),
				'mean' => $this->benchmarker->getMean(),
				'sd'   => $this->benchmarker->getStandardDeviation(),
				'norm' => $this->benchmarker->getNormalizedValueBy( $case['repetitionCount'] )
			]
		];
	}

	private function createQuery( array $case, $mode, array $printouts = [] ) {

		$description = $this->queryParser->getQueryDescription(
			$case['query']['condition']
		);

		foreach ( $case['query']['printouts'] as $printout ) {
			$property = DIProperty::newFromUserLabel( $printout );

			$propertyValue = new PropertyValue( '__pro' );
			$propertyValue->setDataItem( $property );

			$description->addPrintRequest(
				new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
			);
		}

		$query = new Query(
			$description
		);

		$query->setUnboundlimit(
			isset( $case['query']['parameters']['limit'] ) ? $case['query']['parameters']['limit'] : 500
		);

		$query->setOffset(
			isset( $case['query']['parameters']['offset'] ) ? $case['query']['parameters']['offset'] : 0
		);

		$query->setQueryMode( $mode );

		return $query;
	}

}
