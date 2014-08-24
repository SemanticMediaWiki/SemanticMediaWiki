<?php

namespace SMW\Tests\Benchmark;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\Tests\Util\PageDeleter;
use SMW\Tests\Util\PageReader;
use SMW\Tests\Util\PageCreator;

use SMW\Tests\Util\XmlImportRunner;
use SMW\Tests\Util\JobQueueRunner;

use SMW\MediaWiki\Jobs\RefreshJob;

use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class BenchmarkRunner {

	/**
	 * @var array
	 */
	private $messages = array();

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
	 * @param string $xmlFileSource
	 */
	public function doImportXmlDatasetFixture( $xmlFileSource ) {

		$memoryBefore = memory_get_peak_usage( false );

		$importRunner = new XmlImportRunner( $xmlFileSource );
		$importRunner->setVerbose( true );

		if ( !$importRunner->run() ) {
			$importRunner->reportFailedImport();
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$this->addMessage(
			" |- " . $importRunner->getElapsedImportTimeInSeconds() . " (sec) elapsed XML import time"
		);

		if ( $this->showMemoryUsage ) {
			$this->addMessage( " +-- $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 * @param integer $pageCopyThreshold
	 * @param string $baseName
	 */
	public function copyPageContentFrom( Title $title, $pageCopyThreshold, $baseName = '' ) {

		$pageReader = new PageReader();
		$text = $pageReader->getContentAsText( $title );

		$pageCreator = new PageCreator();

		$start = microtime( true );

		if ( $baseName === '' ) {
			$baseName = 'CopyOf' . $title->getText();
		}

		$memoryBefore = memory_get_peak_usage( false );

		for ( $i = 0; $i < $pageCopyThreshold; $i++ ) {
			$pageCreator->createPage( Title::newFromText( $baseName .'-' . $i ), $text );
		}

		$memoryAfter = memory_get_peak_usage( false );
		$memoryDiff  = $memoryAfter - $memoryBefore;

		$sum  = round( microtime( true ) - $start, 7 );
		$mean = $sum / $pageCopyThreshold;

		$this->addMessage(
			" |- $mean (mean) $sum (total) (sec) for $i content copies of page '{$title->getText()}'"
		);

		if ( $this->showMemoryUsage ) {
			$this->addMessage( " +-- $memoryBefore (before) $memoryAfter (after) $memoryDiff (diff)" );
		}
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
			print( $message . "\n" );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getQueryEngine() {
		return "{$GLOBALS['smwgDefaultStore']}" . ( strpos( $GLOBALS['smwgDefaultStore'], 'SQL' ) ? '' : ' [ ' . $GLOBALS['smwgSparqlDatabaseConnector'] . ' ] ' );
	}

}
