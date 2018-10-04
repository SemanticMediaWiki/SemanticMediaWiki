<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageReader;
use Title;

/**
 * @group semantic-mediawiki-benchmark
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PageContentCopyBenchmarkRunner {

	/**
	 * @var PageImportBenchmarkRunner
	 */
	private $pageImportBenchmarkRunner;

	/**
	 * @var Benchmarker
	 */
	private $benchmarker;

	/**
	 * @var PageCreator
	 */
	private $pageCreator;

	/**
	 * @var PageReader
	 */
	private $pageReader;

	/**
	 * @var array
	 */
	private $benchmarkReport = [];

	/**
	 * @var integer|count
	 */
	private $copyCount = null;

	/**
	 * @since 2.5
	 *
	 * @param PageImportBenchmarkRunner $pageImportBenchmarkRunner
	 * @param Benchmarker $benchmarker
	 * @param PageCreator $pageCreator
	 * @param PageReader $pageReader
	 */
	public function __construct( PageImportBenchmarkRunner $pageImportBenchmarkRunner, Benchmarker $benchmarker, PageCreator $pageCreator, PageReader $pageReader ) {
		$this->pageImportBenchmarkRunner = $pageImportBenchmarkRunner;
		$this->benchmarker = $benchmarker;
		$this->pageCreator = $pageCreator;
		$this->pageReader = $pageReader;
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
	 * @param integer|null $copyCount
	 */
	public function setCopyCount( $copyCount = null ) {
		$this->copyCount = $copyCount;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $case
	 */
	public function run( array $case ) {

		$this->benchmarkReport = [];
		$this->benchmarker->clear();
		$start = microtime( true );

		$this->pageImportBenchmarkRunner->run( $case );
		$importBenchmarkReport = $this->pageImportBenchmarkRunner->getBenchmarkReport();

		if ( !isset( $case['copyFrom'] ) ) {
			throw new RuntimeException( 'Copy title is not available.' );
		}

		if ( !isset( $case['copyCount'] ) ) {
			throw new RuntimeException( 'Copy count is not available.' );
		}

		$copyFrom = Title::newFromText( $case['copyFrom'] );

		if ( !$copyFrom->exists() ) {
			throw new RuntimeException( $case['copyFrom'] . ' is not available or readable for the copy process.' );
		}

		if ( !$this->canOverrideCount( $case ) ) {
			$this->copyCount = $case['copyCount'];
		}

		$copyMemory = $this->doCopy( $copyFrom, $case );

		$this->benchmarkReport = [
			'type'   => $case['type'],
			'source' => $case['name'],
			'import' => [
				'memory' => $importBenchmarkReport['memory'],
				'time'   => $importBenchmarkReport['time']
			],
			'copy' => [
				'copyFrom'  => $case['copyFrom'],
				'copyCount' => $this->copyCount,
				'memory'    => $copyMemory,
				'time'      => [
					'sum'  => $this->benchmarker->getSum(),
					'mean' => $this->benchmarker->getMean(),
					'sd'   => $this->benchmarker->getStandardDeviation(),
					'norm' => $this->benchmarker->getNormalizedValueBy( $this->copyCount )
				]
			],
			'time' => microtime( true ) - $start
		];
	}

	private function doCopy( $copyFrom, array $case ) {

		$copyText = $this->pageReader->getContentAsText( $copyFrom );
		$copyName = 'BenchmarkCopy-' . $copyFrom->getText();

		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->copyCount; $i++ ) {

			$start = microtime( true );

			$this->pageCreator->createPage(
				Title::newFromText( $copyName . '-' . $i ),
				$copyText
			);

			$this->benchmarker->addBenchmarkPoint( microtime( true ) - $start );
		}

		return memory_get_peak_usage( false ) - $memoryBefore;
	}

	private function canOverrideCount( $case ) {
		return isset( $case['canOverrideCopyCount'] ) && $case['canOverrideCopyCount'] && $this->copyCount !== null;
	}
}
