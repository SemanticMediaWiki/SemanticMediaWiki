<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\MediaWiki\JobFactory;
use SMW\Tests\Utils\Runners\JobQueueRunner;
use Title;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JobQueueBenchmarkRunner implements BenchmarkReporter {

	/**
	 * @var JobFactory
	 */
	private $jobFactory;

	/**
	 * @var JobQueueRunner
	 */
	private $jobQueueRunner;

	/**
	 * @var Benchmarker
	 */
	private $benchmarker;

	/**
	 * @var array
	 */
	private $benchmarkReport = [];

	/**
	 * @var string
	 */
	private $testCaseLocation;

	/**
	 * @since 2.5
	 *
	 * @param JobFactory $jobFactory
	 * @param JobQueueRunner $jobQueueRunner
	 * @param Benchmarker $benchmarker
	 */
	public function __construct( JobFactory $jobFactory, JobQueueRunner $jobQueueRunner, Benchmarker $benchmarker ) {
		$this->jobFactory = $jobFactory;
		$this->jobQueueRunner = $jobQueueRunner;
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

		if ( !isset( $case['job'] ) ) {
			throw new RuntimeException( 'No job name is available.' );
		}

		if ( !isset( $case['repetitionCount'] ) ) {
			throw new RuntimeException( 'No repetitionCount is available.' );
		}

		$job = $this->jobFactory->newByType(
			$case['job'],
			Title::newFromText( __METHOD__ . $case['job'] )
		);

		$job->insert();

		$this->doRunJob( $case );
	}

	private function doRunJob( array $case ) {

		$this->jobQueueRunner->setType( $case['job'] );
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $case['repetitionCount']; $i++ ) {

			$start = microtime( true );

			$this->jobQueueRunner->run();

			$this->benchmarker->addBenchmarkPoint(
				microtime( true ) - $start
			);
		}

		$this->benchmarkReport = [
			'type'   => $case['type'],
			'case'   => $case['job'],
			'repetitionCount' => $case['repetitionCount'],
			'memory' => memory_get_peak_usage( false ) - $memoryBefore,
			'time'      => [
				'sum'  => $this->benchmarker->getSum(),
				'mean' => $this->benchmarker->getMean(),
				'sd'   => $this->benchmarker->getStandardDeviation(),
				'norm' => $this->benchmarker->getNormalizedValueBy( $case['repetitionCount'] )
			]
		];
	}

}
