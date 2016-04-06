<?php

namespace SMW\Tests\Benchmark;

use RuntimeException;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageReader;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class BenchmarkRunner {

	/**
	 * @var Benchmarker
	 */
	private $benchmarker = null;

	/**
	 * @var array
	 */
	private $messages = array();

	/**
	 * @var boolean
	 */
	private $showMemoryUsage = false;

	/**
	 * @since 2.1
	 */
	public function __construct( $showMemoryUsage = false ) {
		$this->showMemoryUsage = $showMemoryUsage;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getDefaultDataset() {
		return 'BaseLoremIpsumDataset.v1.xml';
	}

	/**
	 * @since 2.1
	 *
	 * @param string $dataset
	 * @param string $location
	 */
	public function doImportDataset( $dataset, $location = '' ) {

		if ( $dataset === '' ) {
			throw new RuntimeException( 'Missing a dataset declaration' );
		}

		if ( $location === '' ) {
			$location = __DIR__ . '/'. 'Fixtures' . '/';
		}

		$memoryBefore = memory_get_peak_usage( false );

		$importRunner = UtilityFactory::getInstance()->newRunnerFactory()->newXmlImportRunner( $location . $dataset );
		$importRunner->setVerbose( true );

		if ( !$importRunner->run() ) {
			$importRunner->reportFailedImport();
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$this->addMessage(
			"- XML Import: " . $importRunner->getElapsedImportTimeInSeconds() ." (sec)"
		);

		if ( $this->showMemoryUsage ) {
			$this->addMessage( "+- Memory: $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 * @param integer $pageCopyThreshold
	 */
	public function copyPageContent( Title $title, $pageCopyThreshold ) {
		$this->createPageContentFrom( $title, $pageCopyThreshold, false );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 * @param integer $pageEditThreshold
	 */
	public function editPageContent( Title $title, $pageEditThreshold ) {
		$this->createPageContentFrom( $title, $pageEditThreshold, true );
	}

	/**
	 * @since 2.1
	 *
	 * @return Benchmarker
	 */
	public function getBenchmarker() {

		if ( $this->benchmarker === null ) {
			$this->benchmarker = new Benchmarker();
		}

		return $this->benchmarker;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $message
	 */
	public function addMessage( $message ) {
		$this->messages[] = $message;
	}

	/**
	 * @since 2.1
	 */
	public function printMessages() {
		foreach ( $this->messages as $message ) {
			print ( $message . "\n" );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getMediaWikiVersion() {
		return $GLOBALS['wgVersion'];
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getQueryEngine() {
		return "{$GLOBALS['smwgDefaultStore']}" . ( strpos( $GLOBALS['smwgDefaultStore'], 'SQL' ) ? '' : ' [ ' . $GLOBALS['smwgSparqlDatabaseConnector'] . ' ] ' );
	}

	private function createPageContentFrom( Title $title, $pageCopyThreshold, $useSamePage = false ) {

		$this->getBenchmarker()->clear();

		$pageReader = new PageReader();
		$text = $pageReader->getContentAsText( $title );

		$pageCreator = new PageCreator();

		$baseName = $useSamePage ? 'CopyOf' . $title->getText() : $title->getText();
		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $pageCopyThreshold; $i++ ) {
			$start = microtime( true );
			$pageCreator->createPage( Title::newFromText( $baseName . ( $useSamePage ? '' : '-' . $i ) ), $text );
			$this->getBenchmarker()->addBenchmarkPoint( microtime( true ) - $start );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = $this->getBenchmarker()->getSum();
		$mean = $this->getBenchmarker()->getMean();
		$sd   = $this->getBenchmarker()->getStandardDeviation();
		$norm = $this->getBenchmarker()->getNormalizedValueBy( $pageCopyThreshold );

		$this->addMessage(
			"- '{$title->getText()}': $norm (n) $mean (mean) $sum (total) $sd (sd) (sec)"
		);

		if ( $this->showMemoryUsage ) {
			$this->addMessage( "+- Memory: $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

}
