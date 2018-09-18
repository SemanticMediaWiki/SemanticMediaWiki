<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\Tests\Utils\Runners\XmlImportRunner;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PageImportBenchmarkRunner implements BenchmarkReporter {

	/**
	 * @var XmlImportRunner
	 */
	private $xmlImportRunner;

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
	 * @param XmlImportRunner $xmlImportRunner
	 * @param Benchmarker $benchmarker
	 */
	public function __construct( XmlImportRunner $xmlImportRunner, Benchmarker $benchmarker ) {
		$this->xmlImportRunner = $xmlImportRunner;
		$this->benchmarker = $benchmarker;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $testCaseLocation
	 */
	public function setTestCaseLocation( $testCaseLocation ) {
		$this->testCaseLocation = $testCaseLocation;
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

		if ( !isset( $case['importFrom'] ) ) {
			throw new RuntimeException( 'No import file is available.' );
		}

		$file = $this->testCaseLocation . $case['importFrom'];

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( $file  . ' as import file is not available.' );
		}

		$ext = pathinfo( $file, PATHINFO_EXTENSION );

		switch ( $ext ) {
			case 'xml':
				return $this->doXmlImport( $file, $case );
				break;
			default:
				# code...
				break;
		}
	}

	private function doXmlImport( $file, array $case ) {

		$this->xmlImportRunner->setFile( $file );
		$this->xmlImportRunner->setVerbose( true );

		$memoryBefore = memory_get_peak_usage( false );

		if ( !$this->xmlImportRunner->run() ) {
			$this->xmlImportRunner->reportFailedImport();
		}

		$this->benchmarkReport = [
			'type'   => $case['type'],
			'source' => $case['name'],
			'memory' => memory_get_peak_usage( false ) - $memoryBefore,
			'time'   => [
				'sum' => $this->xmlImportRunner->getElapsedImportTimeInSeconds()
			]
		];
	}

}
