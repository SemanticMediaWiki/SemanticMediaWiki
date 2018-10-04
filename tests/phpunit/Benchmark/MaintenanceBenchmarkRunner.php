<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\Tests\Utils\Runners\RunnerFactory;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MaintenanceBenchmarkRunner implements BenchmarkReporter {

	/**
	 * @var RunnerFactory
	 */
	private $runnerFactory;

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
	 * @param RunnerFactory $runnerFactory
	 * @param Benchmarker $benchmarker
	 */
	public function __construct( RunnerFactory $runnerFactory, Benchmarker $benchmarker ) {
		$this->runnerFactory = $runnerFactory;
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

		if ( !isset( $case['script'] ) ) {
			throw new RuntimeException( 'Script name is not available.' );
		}

		if ( !isset( $case['repetitionCount'] ) ) {
			throw new RuntimeException( 'No repetitionCount is available.' );
		}

		if ( !isset( $case['options'] ) ) {
			throw new RuntimeException( 'No options are available.' );
		}

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			$case['script']
		);

		$maintenanceRunner->setQuiet();
		$maintenanceRunner->setOptions( $case['options'] );

		$this->doRunMaintenance( $maintenanceRunner, $case );
	}

	private function doRunMaintenance( $maintenanceRunner, array $case ) {

		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $case['repetitionCount']; $i++ ) {

			$start = microtime( true );

			$maintenanceRunner->run();

			$this->benchmarker->addBenchmarkPoint(
				microtime( true ) - $start
			);
		}

		$this->benchmarkReport = [
			'type'   => $case['type'],
			'case'   => $case['script'],
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
