<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\TitleCreator;
use SMW\MediaWiki\TitleLookup;
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
	 * @var TitleCreator
	 */
	private $titleCreator;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	/**
	 * @var array
	 */
	private $exceptionLog = array();

	/**
	 * @var integer
	 */
	private $rebuildCount = 0;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param TitleCreator $titleCreator
	 */
	public function __construct( Store $store, TitleCreator $titleCreator ) {
		$this->store = $store;
		$this->titleCreator = $titleCreator;
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
	 * @return array
	 */
	public function getExceptionLog() {
		return $this->exceptionLog;
	}

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function doRebuild() {

		$this->reportMessage(
			"Refreshing selected pages"  .
			( $this->options->has( 'redirects' ) ? ' (redirects)' : '' ) .
			( $this->options->has( 'categories' ) ? ' (categories)' : '' ) .
			( $this->options->has( 'query' ) ? ' (queries)' : '' ) .
			( $this->options->has( 'p' ) ? ' (properties)' : '' ) .
			".\n"
		);

		$pages = array();
		$this->setNamespaceFiltersFromOptions();

		if ( $this->options->has( 'page' ) ) {
			$pages = explode( '|', $this->options->get( 'page' ) );
		}

		$pages = array_merge( $this->getPagesFromQuery(), $pages, $this->getPagesFromFilters(), $this->getRedirectPages() );

		$this->normalizeBulkOfPages( $pages );
		$numPages = count( $pages );

		foreach ( $pages as $page ) {

			$this->rebuildCount++;
			$percentage = round( ( $this->rebuildCount / $numPages ) * 100 ) ."%";

			$this->reportMessage(
				sprintf( "%-16s%s\n", "($this->rebuildCount/$numPages $percentage)", "Page " . $page->getPrefixedDBkey() ),
				$this->options->has( 'v' )
			);

			$this->doExecuteUpdateJobFor(
				$page
			);

			$this->doPrintDotProgressIndicator(
				$this->options->has( 'v' ),
				$percentage
			);
		}

		$this->reportMessage( "\n\n$this->rebuildCount pages refreshed.\n" );

		return true;
	}

	private function doExecuteUpdateJobFor( $page ) {

		$updatejob = new UpdateJob( $page, array(
			'pm' => $this->options->has( 'shallow-update' ) ? SMW_UJ_PM_CLASTMDATE : false
		) );

		if ( !$this->options->has( 'ignore-exceptions' ) ) {
			return $updatejob->run();
		}

		try {
			$updatejob->run();
		} catch ( \Exception $e ) {
			$this->exceptionLog[$page->getPrefixedDBkey()] = array(
				'msg'   => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			);
		}
	}

	private function setNamespaceFiltersFromOptions() {
		$this->filters = array();

		if ( $this->options->has( 'categories' ) ) {
			$this->filters[] = NS_CATEGORY;
		}

		if ( $this->options->has( 'p' ) ) {
			$this->filters[] = SMW_NS_PROPERTY;
		}
	}

	private function hasFilters() {
		return $this->filters !== array();
	}

	private function getPagesFromQuery() {

		if ( !$this->options->has( 'query' ) ) {
			return array();
		}

		$queryString = $this->options->get( 'query' );

		// get number of pages and fix query limit
		$query = SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( array( 'format' => 'count' ) )
		);

		$result = $this->store->getQueryResult( $query );

		// get pages and add them to the pages explicitly listed in the 'page' parameter
		$query = SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( array() )
		);

		$query->setUnboundLimit( $result instanceof \SMWQueryResult ? $result->getCountValue() : $result );

		return $this->store->getQueryResult( $query )->getResults();
	}

	private function getPagesFromFilters() {

		$pages = array();

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
			return array();
		}

		$titleLookup = new TitleLookup(
			$this->store->getConnection( 'mw.db' )
		);

		return $titleLookup->getRedirectPages();
	}

	private function normalizeBulkOfPages( &$pages ) {

		$titleCache = array();

		foreach ( $pages as $key => &$page ) {

			if ( $page instanceof DIWikiPage ) {
				$page = $page->getTitle();
			}

			if ( !$page instanceof Title ) {
				$page = $this->titleCreator->createFromText( $page );
			}

			// Filter out pages with fragments (subobjects)
			if ( isset( $titleCache[$page->getPrefixedDBkey()] ) ) {
				unset( $pages[$key] );
			} else{
				$titleCache[$page->getPrefixedDBkey()] = true;
			}
		}

		unset( $titleCache );
	}

	private function reportMessage( $message, $output = true ) {
		if ( $output ) {
			$this->reporter->reportMessage( $message );
		}
	}

	private function doPrintDotProgressIndicator( $verbose, $progress ) {

		if ( ( $this->rebuildCount - 1 ) % 60 === 0 ) {
			$this->reportMessage( "\n", !$verbose );
		}

		$this->reportMessage( '.', !$verbose );

		if ( $this->rebuildCount % 60 === 0 ) {
			$this->reportMessage( " $progress", !$verbose );
		}
	}

}
