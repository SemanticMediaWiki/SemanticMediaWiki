<?php

namespace SMW\Maintenance;

use LinkCache;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\TitleCreator;
use SMW\MediaWiki\TitleLookup;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Store;
use SMW\Options;
use SMWQueryProcessor;
use Title;

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

	private $rebuildCount = 0;

	private $delay = false;
	private $pages = false;
	private $canWriteToIdFile = false;
	private $start = 1;
	private $end = false;

	/**
	 * @var int[]
	 */
	private $filters = array();
	private $verbose = false;
	private $startIdFile = false;

	/**
	 * @since 1.9.2
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
	public function setMessageReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param Options $options
	 */
	public function setOptions( Options $options ) {
		$this->options = $options;

		if ( $options->has( 'server' ) ) {
			$GLOBALS['wgServer'] = $options->get( 'server' );
		}

		if ( $options->has( 'd' ) ) {
			$this->delay = intval( $options->get( 'd' ) ) * 1000; // convert milliseconds to microseconds
		}

		if ( $options->has( 'page' ) ) {
			$this->pages = explode( '|', $options->get( 'page' ) );
		}

		if ( $options->has( 's' ) ) {
			$this->start = max( 1, intval( $options->get( 's' ) ) );
		} elseif ( $options->has( 'startidfile' ) ) {

			$this->canWriteToIdFile = $this->idFileIsWritable( $options->get( 'startidfile' )  );
			$this->startIdFile = $options->get( 'startidfile' );

			if ( is_readable( $options->get( 'startidfile' ) ) ) {
				$this->start = max( 1, intval( file_get_contents( $options->get( 'startidfile' ) ) ) );
			}
		}

		// Note: this might reasonably be larger than the page count
		if ( $options->has( 'e' ) ) {
			$this->end = intval( $options->get( 'e' ) );
		} elseif ( $options->has( 'n' ) ) {
			$this->end = $this->start + intval( $options->get( 'n' ) );
		}

		$this->verbose = $options->has( 'v' );

		$this->setFiltersFromOptions( $options );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return boolean
	 */
	public function rebuild() {

		$this->reportMessage( "\nRunning for storage: " . get_class( $this->store ) . "\n\n" );

		if ( $this->options->has( 'f' ) ) {
			$this->performFullDelete();
		}

		if ( $this->pages || $this->options->has( 'query' ) || $this->hasFilters() ) {
			return $this->doRebuildPagesFor( "Refreshing selected pages!" );
		}

		return $this->doRebuildAll();
	}

	private function hasFilters() {
		return $this->filters !== array();
	}

	/**
	 * @since 1.9.2
	 *
	 * @return int
	 */
	public function getRebuildCount() {
		return $this->rebuildCount;
	}

	private function doRebuildPagesFor( $message ) {

		$this->reportMessage( $message  . "\n" );

		$pages = $this->getPagesFromQuery();
		$pages = $this->pages ? array_merge( (array)$this->pages, $pages ) : $pages;
		$pages = $this->hasFilters() ? array_merge( $pages, $this->getPagesFromFilters() ) : $pages;

		$this->normalizeBulkOfPages( $pages );
		$numPages = count( $pages );

		foreach ( $pages as $page ) {

			$this->rebuildCount++;
			$percentage = round( ( $this->rebuildCount / $numPages ) * 100 ) ."%";

			$this->reportMessage( "($this->rebuildCount/$numPages $percentage) Processing page " . $page->getPrefixedDBkey() . " ...\n", $this->verbose );

			$updatejob = new UpdateJob( $page, array(
				'pm' => $this->options->has( 'shallow-update' ) ? SMW_UJ_PM_CLASTMDATE : false
			) );

			$updatejob->run();
			$this->doPrintDotProgressIndicator( $this->verbose, $percentage );
		}

		$this->reportMessage( "\n\n$this->rebuildCount pages refreshed.\n" );

		return true;
	}

	private function doRebuildAll() {

		$linkCache = LinkCache::singleton();

		$byIdDataRebuildDispatcher = $this->store->refreshData(
			$this->start,
			1
		);

		$byIdDataRebuildDispatcher->setIterationLimit( 1 );

		$byIdDataRebuildDispatcher->setUpdateJobParseMode(
			$this->options->has( 'shallow-update' ) ? SMW_UJ_PM_CLASTMDATE : false
		);

		$byIdDataRebuildDispatcher->setUpdateJobToUseJobQueueScheduler( false );

		$this->deleteMarkedSubjects( $byIdDataRebuildDispatcher );

		if ( !$this->options->has( 'skip-properties' ) ) {
			$this->filters[] = SMW_NS_PROPERTY;
			$this->doRebuildPagesFor( "Rebuilding property pages." );
			$this->reportMessage( "\n" );
		}

		$this->rebuildCount = 0;
		$this->store->clear();

		$this->reportMessage( "Refreshing all semantic data in the database!\n---\n" .
			" Some versions of PHP suffer from memory leaks in long-running \n" .
			" scripts. If your machine gets very slow after many pages \n" .
			" (typically more than 1000) were refreshed, please abort with\n" .
			" CTRL-C and resume this script at the last processed page id\n" .
			" using the parameter -s (use -v to display page ids during \n" .
			" refresh). Continue this until all pages have been refreshed.\n---\n"
		);


		$total = $this->end && $this->end - $this->start > 0 ? $this->end - $this->start : $byIdDataRebuildDispatcher->getMaxId();

		$this->reportMessage(
			" The displayed progress is an estimation and is self-adjusting \n" .
			" during the update process.\n---\n" );

		$this->reportMessage( "Processing all IDs from $this->start to " . ( $this->end ? "$this->end" : $byIdDataRebuildDispatcher->getMaxId() ) . " ...\n" );

		$id = $this->start;

		while ( ( ( !$this->end ) || ( $id <= $this->end ) ) && ( $id > 0 ) ) {

			$this->rebuildCount++;
			$progress = '';

			$byIdDataRebuildDispatcher->dispatchRebuildFor( $id );

			if ( $this->rebuildCount % 60 === 0 ) {
				$progress = round( ( $this->end - $this->start > 0 ? $this->rebuildCount / $total : $byIdDataRebuildDispatcher->getEstimatedProgress() ) * 100 ) . "%";
			}

			$this->reportMessage( "($this->rebuildCount/$total) Processing ID " . $id . " ...\n", $this->verbose );

			if ( $this->delay !== false ) {
				usleep( $this->delay );
			}

			if ( $this->rebuildCount % 100 === 0 ) { // every 100 pages only
				$linkCache->clear(); // avoid memory leaks
			}

			$this->doPrintDotProgressIndicator( $this->verbose, $progress );
		}

		$this->writeIdToFile( $id );
		$this->reportMessage( "\n\n$this->rebuildCount IDs refreshed.\n" );

		return true;
	}

	private function performFullDelete() {

		$this->reportMessage( "Deleting all stored data completely and rebuilding it again later!\n---\n" .
			" Semantic data in the wiki might be incomplete for some time while this operation runs.\n\n" .
			" NOTE: It is usually necessary to run this script ONE MORE TIME after this operation,\n" .
			" since some properties' types are not stored yet in the first run.\n---\n"
		);

		if ( $this->options->has( 's' ) || $this->options->has( 'e' ) ) {
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

	private function deleteMarkedSubjects( $byIdDataRebuildDispatcher ) {

		$matches = array();

		$res = $this->store->getConnection( 'mw.db' )->select(
			\SMWSql3SmwIds::TABLE_NAME,
			array( 'smw_id' ),
			array( 'smw_iw' => SMW_SQL3_SMWDELETEIW ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$matches[] = $row->smw_id;
		}

		if ( $matches === array() ) {
			return null;
		}

		$this->reportMessage( "Removing marked for deletion entries.\n" );
		$matchesCount = count( $matches );

		foreach ( $matches as $id ) {
			$this->rebuildCount++;
			$this->doPrintDotProgressIndicator( $this->verbose, round( $this->rebuildCount / $matchesCount * 100 ) . ' %' );
			$byIdDataRebuildDispatcher->dispatchRebuildFor( $id );
		}

		$this->rebuildCount = 0;

		$this->reportMessage( "\n\n{$matchesCount} IDs removed.\n\n" );
	}

	private function idFileIsWritable( $startIdFile ) {

		if ( !is_writable( file_exists( $startIdFile ) ? $startIdFile : dirname( $startIdFile ) ) ) {
			die( "Cannot use a startidfile that we can't write to.\n" );
		}

		return true;
	}

	private function writeIdToFile( $id ) {
		if ( $this->canWriteToIdFile ) {
			file_put_contents( $this->startIdFile, "$id" );
		}
	}

	/**
	 * @param array $options
	 */
	private function setFiltersFromOptions( Options $options ) {
		$this->filters = array();

		if ( $options->has( 'c' ) ) {
			$this->filters[] = NS_CATEGORY;
		}

		if ( $options->has( 'p' ) ) {
			$this->filters[] = SMW_NS_PROPERTY;
		}
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

		$titleLookup = new TitleLookup( $this->store->getConnection( 'mw.db' ) );

		foreach ( $this->filters as $namespace ) {
			$pages = array_merge( $pages, $titleLookup->setNamespace( $namespace )->selectAll() );
		}

		return $pages;
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
