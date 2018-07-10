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
class PageEditCopyBenchmarkRunner {

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
	private $editRepetitionCount = null;

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
	public function setEditRepetitionCount( $editRepetitionCount = null ) {
		$this->editRepetitionCount = $editRepetitionCount;
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

		if ( !isset( $case['edit'] ) || !is_array( $case['edit'] ) ) {
			throw new RuntimeException( 'Edit title is not available.' );
		}

		if ( !isset( $case['editRepetitionCount'] ) ) {
			throw new RuntimeException( 'editRepetitionCount is not available.' );
		}

		if ( !$this->canOverrideCount( $case ) ) {
			$this->editRepetitionCount = $case['editRepetitionCount'];
		}

		$editReports = [];

		foreach ( $case['edit'] as $title ) {

			$editTitle = Title::newFromText( $title );

			if ( !$editTitle->exists() ) {
				throw new RuntimeException( $title . ' is not available or readable for the edit process.' );
			}

			$editReports[$title] = $this->doEdit( $editTitle, $case );
		}

		$this->benchmarkReport = [
			'type'   => $case['type'],
			'source' => $case['name'],
			'import' => [
				'memory' => $importBenchmarkReport['memory'],
				'time'   => $importBenchmarkReport['time']
			],
			'editTask' => $editReports,
			'time' => microtime( true ) - $start
		];
	}

	private function doEdit( $editTitle, array $case ) {

		$copyText = $this->pageReader->getContentAsText( $editTitle );
		$this->benchmarker->clear();

		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $this->editRepetitionCount; $i++ ) {

			$start = microtime( true );

			$this->pageCreator->createPage(
				$editTitle,
				$copyText
			);

			$this->benchmarker->addBenchmarkPoint( microtime( true ) - $start );
		}

		return [
			'editRepetitionCount' => $this->editRepetitionCount,
			"memory" => memory_get_peak_usage( false ) - $memoryBefore,
			"time" => [
				'sum'  => $this->benchmarker->getSum(),
				'mean' => $this->benchmarker->getMean(),
				'sd'   => $this->benchmarker->getStandardDeviation(),
				'norm' => $this->benchmarker->getNormalizedValueBy( $this->editRepetitionCount )
			]
		];
	}

	private function canOverrideCount( $case ) {
		return isset( $case['canOverrideEditCount'] ) && $case['canOverrideEditCount'] && $this->editRepetitionCount !== null;
	}

}
