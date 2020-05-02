<?php

namespace SMW\Maintenance;

use Exception;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\TitleFactory;
use SMW\MediaWiki\TitleLookup;
use SMW\ApplicationFactory;
use SMW\Utils\CliMsgFormatter;
use SMW\Options;
use SMW\Store;
use SMWQueryProcessor;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DistinctEntityDataRebuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	/**
	 * @var ExceptionFileLogger
	 */
	private $exceptionFileLogger;

	/**
	 * @var array
	 */
	private $filters = [];

	/**
	 * @var integer
	 */
	private $rebuildCount = 0;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( Store $store, TitleFactory $titleFactory ) {
		$this->store = $store;
		$this->titleFactory = $titleFactory;
		$this->reporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since 2.1
	 *
	 * @param MessageReporter $reporter
	 */
	public function setOptions( Options $options ) {
		$this->options = $options;
	}

	/**
	 * @since 2.1
	 *
	 * @param MessageReporter $reporter
	 */
	public function setMessageReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * @since 3.0
	 *
	 * @param ExceptionFileLogger $exceptionFileLogger
	 */
	public function setExceptionFileLogger( ExceptionFileLogger $exceptionFileLogger ) {
		$this->exceptionFileLogger = $exceptionFileLogger;
	}

	/**
	 * @since 2.4
	 *
	 * @return int
	 */
	public function getRebuildCount() {
		return $this->rebuildCount;
	}

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function doRebuild() {

		$type = ( $this->options->has( 'redirects' ) ? 'redirect' : '' ) .
		( $this->options->has( 'categories' ) ? 'category' : '' ) .
		( $this->options->has( 'namespace' ) ? $this->options->get( 'namespace' ) : '' ) .
		( $this->options->has( 'query' ) ? 'query (' . $this->options->get( 'query' ) .')' : '' ) .
		( $this->options->has( 'p' ) ? 'property' : '' );

		$pages = [];
		$this->findFilters();

		if ( $this->options->has( 'page' ) ) {
			$pages = explode( '|', $this->options->get( 'page' ) );
		}

		$pages = $this->normalize(
			[
				$this->getPagesFromQuery(),
				$pages,
				$this->getPagesFromFilters(),
				$this->getRedirectPages()
			]
		);

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			$cliMsgFormatter->section( "Rebuild ($type)", 3, '-', true ) . "\n"
		);

		$total = count( $pages );
		$this->reportMessage( "Find and rebuild $type pages ...\n" );

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... selected pages ...", $total, 3 )
		);

		$jobFactory = ApplicationFactory::getInstance()->newJobFactory();

		foreach ( $pages as $key => $page ) {

			$this->rebuildCount++;
			$progress = $cliMsgFormatter->progressCompact( $this->rebuildCount, $total );

			if ( !$this->options->has( 'v' ) ) {
				$this->reportMessage(
					$cliMsgFormatter->twoColsOverride( '   ... updating ', $progress )
				);
			} else {
				$this->reportMessage(
					sprintf( "%-25s%s\n", "   ... $progress", $key ),
					$this->options->has( 'v' )
				);
			}

			$this->doUpdate( $jobFactory, $page );
		}

		if ( $pages !== [] && !$this->options->has( 'v' ) ) {
			$this->reportMessage( "\n" );
		}

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	private function doUpdate( $jobFactory, $page ) {

		$updatejob = $jobFactory->newUpdateJob(
			$page,
			[
				UpdateJob::FORCED_UPDATE => true,
				'shallowUpdate' => $this->options->has( 'shallow-update' )
			]
		);

		if ( !$this->options->has( 'ignore-exceptions' ) ) {
			return $updatejob->run();
		}

		try {
			$updatejob->run();
		} catch ( Exception $e ) {
			$this->exceptionFileLogger->recordException( $page->getPrefixedDBkey(), $e );
		}
	}

	private function findFilters() {
		$this->filters = [];

		if ( $this->options->has( 'categories' ) ) {
			$this->filters[] = NS_CATEGORY;
		}

		if ( $this->options->has( 'namespace' ) ) {
			$this->filters[] = constant( $this->options->get( 'namespace' ) );
		}

		if ( $this->options->has( 'p' ) ) {
			$this->filters[] = SMW_NS_PROPERTY;
		}
	}

	private function hasFilters() {
		return $this->filters !== [];
	}

	private function getPagesFromQuery() {

		if ( !$this->options->has( 'query' ) ) {
			return [];
		}

		$queryString = $this->options->get( 'query' );

		// get number of pages and fix query limit
		$query = SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( [ 'format' => 'count' ] )
		);

		$result = $this->store->getQueryResult( $query );

		// get pages and add them to the pages explicitly listed in the 'page' parameter
		$query = SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( [] )
		);

		$query->setUnboundLimit( $result instanceof \SMWQueryResult ? $result->getCountValue() : $result );

		return $this->store->getQueryResult( $query )->getResults();
	}

	private function getPagesFromFilters() {

		$pages = [];

		if ( !$this->hasFilters() ) {
			return $pages;
		}

		$titleLookup = new TitleLookup( $this->store->getConnection( 'mw.db' ) );

		foreach ( $this->filters as $namespace ) {
			$pages = array_merge( $pages, $titleLookup->setNamespace( $namespace )->selectAll() );
		}

		return $pages;
	}

	private function getRedirectPages() {

		if ( !$this->options->has( 'redirects' ) ) {
			return [];
		}

		$titleLookup = new TitleLookup(
			$this->store->getConnection( 'mw.db' )
		);

		return $titleLookup->getRedirectPages();
	}

	private function normalize( $list ) {

		$titleCache = [];
		$p = [];

		foreach ( $list as $pages ) {
			foreach ( $pages as $key => $page ) {

				if ( $page instanceof DIWikiPage ) {
					$page = $page->getTitle();
				}

				if ( !$page instanceof Title ) {
					$page = $this->titleFactory->newFromText( $page );
				}

				$id = $page->getPrefixedDBkey();

				if ( !isset( $p[$id] ) ) {
					$p[$id] = $page;
				}
			}
		}

		return $p;
	}

	private function reportMessage( $message, $output = true ) {
		if ( $output ) {
			$this->reporter->reportMessage( $message );
		}
	}

}
