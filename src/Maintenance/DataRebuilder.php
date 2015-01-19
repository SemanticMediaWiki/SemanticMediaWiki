<?php

namespace SMW\Maintenance;

use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\TitleLookup;

use SMW\Reporter\MessageReporter;
use SMW\Reporter\NullMessageReporter;

use SMW\Settings;
use SMW\Store;
use SMW\DIWikiPage;

use SMWQueryProcessor;
use Title;
use LinkCache;

/**
 * Is part of the `rebuildData.php` maintenance script to rebuild existing data
 * for the store
 *
 * @note This is an internal class and should not be used outside of smw-core
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class DataRebuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	private $rebuildCount = 0;

	private $delay = false;
	private $pages = false;
	private $canWriteToIdFile = false;
	private $start = 1;
	private $end = false;
	private $filters = false;
	private $fullDelete = false;
	private $verbose = false;
	private $useIds = false;
	private $startIdFile = false;
	private $query = false;

	/**
	 * @since 1.9.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
		$this->reporter = new NullMessageReporter();
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
	 * @since 1.9.2
	 *
	 * @param array $parameters
	 */
	public function setParameters( array $options ) {

		if ( isset( $options['server'] ) ) {
			$GLOBALS['wgServer'] = $options['server'];
		}

		if ( array_key_exists( 'd', $options ) ) {
			$this->delay = intval( $options['d'] ) * 1000; // convert milliseconds to microseconds
		}

		if ( isset( $options['page'] ) ) {
			$this->pages = explode( '|', $options['page'] );
		}

		if ( array_key_exists( 's', $options ) ) {
			$this->start = max( 1, intval( $options['s'] ) );
		} elseif ( array_key_exists( 'startidfile', $options ) ) {

			$this->canWriteToIdFile = $this->idFileIsWritable( $options['startidfile'] );
			$this->startIdFile = $options['startidfile'];

			if ( is_readable( $options['startidfile'] ) ) {
				$this->start = max( 1, intval( file_get_contents( $options['startidfile'] ) ) );
			}
		}

		// Note: this might reasonably be larger than the page count
		if ( array_key_exists( 'e', $options ) ) {
			$this->end = intval( $options['e'] );
		} elseif ( array_key_exists( 'n', $options ) ) {
			$this->end = $this->start + intval( $options['n'] );
		}

		$this->useIds = array_key_exists( 's', $options ) || array_key_exists( 'e', $options );

		$this->verbose = array_key_exists( 'v', $options );

		$this->filters = $this->describeFiltersFromOptions( $options );

		if ( array_key_exists( 'f', $options ) ) {
			$this->fullDelete = true;
		}

		if ( array_key_exists( 'query', $options ) ) {
			$this->query = $options['query'];
		}
	}

	/**
	 * @since 1.9.2
	 *
	 * @return boolean
	 */
	public function rebuild() {

		$this->reportMessage( "\nSelected storage " . get_class( $this->store ) . " for update!\n\n" );

		if ( $this->fullDelete ) {
			$this->performFullDelete();
		}

		if ( $this->pages || $this->query || $this->filters ) {
			return $this->rebuildSelectedPages();
		}

		return $this->rebuildAll();
	}

	/**
	 * @since 1.9.2
	 *
	 * @return int
	 */
	public function getRebuildCount() {
		return $this->rebuildCount;
	}

	protected function rebuildSelectedPages() {

		$this->reportMessage( "Refreshing specified pages!\n" . ( $this->verbose ? "\n" : '' ) );

		$pages = $this->query ? $this->getPagesFromQuery() : array();
		$pages = $this->pages ? array_merge( (array)$this->pages, $pages ) : $pages;
		$pages = $this->filters ? array_merge( $pages, $this->getPagesFromFilters() ) : $pages;
		$numPages = count( $pages );

		$titleCache = array();

		foreach ( $pages as $page ) {

			$title = $this->makeTitleOf( $page );

			if ( $title !== null && !isset( $titleCache[ $title->getPrefixedDBkey() ] ) ) {

				$this->rebuildCount++;
				$percentage = round( $this->rebuildCount / $numPages * 100 );

				$this->reportMessage( "($this->rebuildCount/$numPages $percentage%) Processing page " . $title->getPrefixedDBkey() . " ...\n", $this->verbose );

				$updatejob = new UpdateJob( $title );
				$updatejob->run();

				$this->doPrintDotProgressIndicator( $this->verbose );
				$titleCache[ $title->getPrefixedDBkey() ] = true;
			}
		}

		$this->reportMessage( "\n$this->rebuildCount pages refreshed.\n" );

		return true;
	}

	protected function rebuildAll() {

		$linkCache = LinkCache::singleton();

		$this->reportMessage( "Refreshing all semantic data in the database!\n---\n" .
			" Some versions of PHP suffer from memory leaks in long-running \n" .
			" scripts. If your machine gets very slow after many pages \n" .
			" (typically more than 1000) were refreshed, please abort with\n" .
			" CTRL-C and resume this script at the last processed page id\n" .
			" using the parameter -s (use -v to display page ids during \n" .
			" refresh). Continue this until all pages were refreshed.\n---\n"
		);

		$this->reportMessage( "Processing all IDs from $this->start to " . ( $this->end ? "$this->end" : 'last ID' ) . " ...\n" );

		$id = $this->start;

		while ( ( ( !$this->end ) || ( $id <= $this->end ) ) && ( $id > 0 ) ) {

			$this->rebuildCount++;

			$this->reportMessage( "($this->rebuildCount) Processing ID " . $id . " ...\n", $this->verbose );

			$this->store->refreshData( $id, 1, false, false );

			if ( $this->delay !== false ) {
				usleep( $this->delay );
			}

			if ( $this->rebuildCount % 100 === 0 ) { // every 100 pages only
				$linkCache->clear(); // avoid memory leaks
			}

			$this->doPrintDotProgressIndicator( $this->verbose );
		}

		$this->writeIdToFile( $id );
		$this->reportMessage( "\n$this->rebuildCount IDs refreshed.\n" );

		return true;
	}

	protected function performFullDelete() {

		$this->reportMessage( "\n Deleting all stored data completely and rebuilding it again later!\n" .
			" Semantic data in the wiki might be incomplete for some time while this operation runs.\n\n" .
			" NOTE: It is usually necessary to run this script ONE MORE TIME after this operation,\n" .
			" since some properties' types are not stored yet in the first run.\n" .
			" The first run can normally use the parameter -p to refresh only properties.\n\n"
		);

		if ( $this->useIds ) {
			$this->reportMessage( " WARNING: -s or -e are used, so some pages will not be refreshed at all!\n" .
				" Data for those pages will only be available again when they have been\n" .
				" refreshed as well!\n\n"
			);
		}

		$obLevel = ob_get_level();

		$this->reportMessage( ' Abort with control-c in the next five seconds ...  ' );
		wfCountDown( 6 );

		$this->store->drop( $this->verbose );
		wfRunHooks( 'smwDropTables' );
		wfRunHooks( 'SMW::Store::dropTables', array( $this->verbose ) );

		$this->store->setupStore( $this->verbose );

		// Be sure to have some buffer, otherwise some PHPs complain
		while ( ob_get_level() > $obLevel ) {
			ob_end_flush();
		}

		$this->reportMessage( "\nAll storage structures have been deleted and recreated.\n\n" );

		return true;
	}

	protected function idFileIsWritable( $startIdFile ) {

		if ( !is_writable( file_exists( $startIdFile ) ? $startIdFile : dirname( $startIdFile ) ) ) {
			die( "Cannot use a startidfile that we can't write to.\n" );
		}

		return true;
	}

	protected function writeIdToFile( $id ) {
		if ( $this->canWriteToIdFile ) {
			file_put_contents( $this->startIdFile, "$id" );
		}
	}

	protected function describeFiltersFromOptions( $options ) {

		$filtersarray = array();

		if ( array_key_exists( 'c', $options ) ) {
			$filtersarray[] = NS_CATEGORY;
		}

		if ( array_key_exists( 'p', $options ) ) {
			$filtersarray[] = SMW_NS_PROPERTY;
		}

		if ( array_key_exists( 't', $options ) ) {
			$filtersarray[] = SMW_NS_TYPE;
		}

		return $filtersarray !== array() ? $filtersarray : false;
	}

	protected function getPagesFromQuery() {

		// get number of pages and fix query limit
		$query = SMWQueryProcessor::createQuery(
			$this->query,
			SMWQueryProcessor::getProcessedParams( array( 'format' => 'count' ) )
		);

		$result = $this->store->getQueryResult( $query );

		// get pages and add them to the pages explicitly listed in the 'page' parameter
		$query = SMWQueryProcessor::createQuery(
			$this->query,
			SMWQueryProcessor::getProcessedParams( array() )
		);

		$query->setUnboundLimit( $result instanceof \SMWQueryResult ? $result->getCountValue() : $result );

		return $this->store->getQueryResult( $query )->getResults();
	}

	protected function getPagesFromFilters() {

		$pages = array();

		$titleLookup = new TitleLookup( $this->store->getConnection( 'mw.db' ) );

		foreach ( $this->filters as $namespace ) {
			$pages = array_merge( $pages, $titleLookup->setNamespace( $namespace )->selectAll() );
		}

		return $pages;
	}

	protected function makeTitleOf( $page ) {

		if ( $page instanceof DIWikiPage ) {
			return $page->getTitle();
		}

		if ( $page instanceof Title ) {
			return $page;
		}

		return Title::newFromText( $page );
	}

	protected function reportMessage( $message, $output = true ) {
		if ( $output ) {
			$this->reporter->reportMessage( $message );
		}
	}

	private function doPrintDotProgressIndicator( $verbose ) {

		if ( ( $this->rebuildCount - 1 ) % 60 === 0 ) {
			$this->reportMessage( "\n", !$verbose );
		}

		$this->reportMessage( '.', !$verbose );
	}

}
