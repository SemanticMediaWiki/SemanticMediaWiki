<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\DataItems\Property;
use SMW\DataValues\PropertyValue;
use SMW\Query\Parser as QueryParser;
use SMW\Query\PrintRequest;
use SMW\Query\Query;
use SMW\Store;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QueryBenchmarkRunner implements BenchmarkReporter {

	/**
	 * @var array
	 */
	private $benchmarkReport = [];

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly Store $store,
		private readonly QueryParser $queryParser,
		private readonly Benchmarker $benchmarker,
	) {
	}

	/**
	 * @since 2.5
	 */
	public function getBenchmarkReport() {
		return $this->benchmarkReport;
	}

	/**
	 * @since 2.5
	 */
	public function run( array $case ) {
		$this->benchmarkReport = [];
		$this->benchmarker->clear();

		if ( !isset( $case['query'] ) && !is_array( $case['query'] ) ) {
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
			$property = Property::newFromUserLabel( $printout );

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
