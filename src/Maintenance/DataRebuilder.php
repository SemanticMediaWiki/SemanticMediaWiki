<?php

namespace SMW\Maintenance;

use LinkCache;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\TitleCreator;
use SMW\Options;
use SMW\Store;
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

	/**
	 * @var DistinctEntityDataRebuilder
	 */
	private $distinctEntityDataRebuilder;

	/**
	 * @var ExceptionFileLogger
	 */
	private $exceptionFileLogger;

	/**
	 * @var array
	 */
	private $exceptionLog = array();

	/**
	 * @var integer
	 */
	private $rebuildCount = 0;

	private $delay = false;
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
		$this->distinctEntityDataRebuilder = new DistinctEntityDataRebuilder( $store, $titleCreator );
		$this->exceptionFileLogger = new ExceptionFileLogger( 'rebuilddata' );
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
		$this->exceptionFileLogger->setOptions( $options );

		$this->setFiltersFromOptions( $options );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return boolean
	 */
	public function rebuild() {

		$storeName = get_class( $this->store );

		if ( strpos( $storeName, "\\") !== false ) {
			$storeName = explode("\\", $storeName );
			$storeName = end( $storeName );
		}

		$this->reportMessage( "\nRunning for storage: " . $storeName . "\n\n" );

		if ( $this->options->has( 'f' ) ) {
			$this->performFullDelete();
		}

		if ( $this->options->has( 'page' ) || $this->options->has( 'query' ) || $this->hasFilters() || $this->options->has( 'redirects' ) ) {
			return $this->doRebuildDistinctEntities();
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

	private function doRebuildDistinctEntities() {

		$this->distinctEntityDataRebuilder->setOptions(
			$this->options
		);

		$this->distinctEntityDataRebuilder->setMessageReporter(
			$this->reporter
		);

		$this->distinctEntityDataRebuilder->doRebuild();

		$this->rebuildCount = $this->distinctEntityDataRebuilder->getRebuildCount();

		$this->exceptionFileLogger->doWriteExceptionLog(
			$this->distinctEntityDataRebuilder->getExceptionLog()
		);

		if ( $this->options->has( 'ignore-exceptions' ) && $this->exceptionFileLogger->getExceptionCounter() > 0 ) {
			$this->reportMessage( "\n" .
				$this->exceptionFileLogger->getExceptionCounter() . " exceptions were ignored! (See " .
				$this->exceptionFileLogger->getExceptionFile() . ").\n"
			);
		}

		return true;
	}

	private function doRebuildAll() {

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
			$this->options->set( 'p', true );
			$this->doRebuildDistinctEntities();
			$this->reportMessage( "\n" );
		}

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
		$id = $this->start;

		$this->reportMessage(
			" The progress displayed is an estimation and is self-adjusting \n" .
			" during the update process.\n---\n" );

		$this->reportMessage(
			"Processing all IDs from $this->start to " .
			( $this->end ? "$this->end" : $byIdDataRebuildDispatcher->getMaxId() ) . " ...\n"
		);

		$this->rebuildCount = 0;

		while ( ( ( !$this->end ) || ( $id <= $this->end ) ) && ( $id > 0 ) ) {

			$progress = '';

			$this->rebuildCount++;
			$this->exceptionLog = array();

			$this->doExecuteFor( $byIdDataRebuildDispatcher, $id );

			if ( $this->rebuildCount % 60 === 0 ) {
				$progress = round( ( $this->end - $this->start > 0 ? $this->rebuildCount / $total : $byIdDataRebuildDispatcher->getEstimatedProgress() ) * 100 ) . "%";
			}

			foreach ( $byIdDataRebuildDispatcher->getDispatchedEntities() as $value ) {

				$text = $this->getHumanReadableTextFrom( $id, $value );

				$this->reportMessage(
					sprintf( "%-16s%s\n", "($this->rebuildCount/$total)", "Finished processing ID " . $text ),
					$this->options->has( 'v' )
				);

				if ( $this->options->has( 'ignore-exceptions' ) && isset( $this->exceptionLog[$id] ) ) {
					$this->exceptionFileLogger->doWriteExceptionLog(
						array( $id . ' ' . $text => $this->exceptionLog[$id] )
					);
				}
			}

			$this->doPrintDotProgressIndicator( $this->verbose, $progress );
		}

		$this->writeIdToFile( $id );
		$this->reportMessage( "\n\n$this->rebuildCount IDs refreshed.\n" );

		if ( $this->options->has( 'ignore-exceptions' ) && $this->exceptionFileLogger->getExceptionCounter() > 0 ) {
			$this->reportMessage( "\n" .
				$this->exceptionFileLogger->getExceptionCounter() . " exceptions were ignored! (See " .
				$this->exceptionFileLogger->getExceptionFile() . ").\n"
			);
		}

		return true;
	}

	private function doExecuteFor( $byIdDataRebuildDispatcher, &$id ) {

		if ( !$this->options->has( 'ignore-exceptions' ) ) {
			$byIdDataRebuildDispatcher->dispatchRebuildFor( $id );
		} else {

			try {
				$byIdDataRebuildDispatcher->dispatchRebuildFor( $id );
			} catch ( \Exception $e ) {
				$this->exceptionLog[$id] = array(
					'msg'   => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				);
			}
		}

		if ( $this->delay !== false ) {
			usleep( $this->delay );
		}

		if ( $this->rebuildCount % 100 === 0 ) { // every 100 pages only
			LinkCache::singleton()->clear(); // avoid memory leaks
		}
	}

	private function getHumanReadableTextFrom( $id, array $entities ) {

		if ( !$this->options->has( 'v' ) ) {
			return '';
		}

		// Indicates whether this is a MW page (*) or SMW's object table
		$text = $id . ( isset( $entities['t'] ) ? '*' : '' );

		$entity = end( $entities );

		if ( $entity instanceof \Title ) {
			return $text . ' (' . $entity->getPrefixedDBKey() .')';
		}

		if ( $entity instanceof DIWikiPage ) {
			return $text . ' (' . $entity->getHash() .')';
		}

		return $text . ' (' . ( is_string( $entity ) && $entity !== '' ? $entity : 'N/A' ) . ')';
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
		\Hooks::run( 'smwDropTables' );
		\Hooks::run( 'SMW::Store::dropTables', array( $this->verbose ) );

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

		$this->reportMessage( "Removing table entries (marked for deletion).\n" );
		$matchesCount = count( $matches );

		foreach ( $matches as $id ) {
			$this->rebuildCount++;
			$this->doPrintDotProgressIndicator( false, round( $this->rebuildCount / $matchesCount * 100 ) . ' %' );
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

		if ( $options->has( 'categories' ) ) {
			$this->filters[] = NS_CATEGORY;
		}

		if ( $options->has( 'p' ) ) {
			$this->filters[] = SMW_NS_PROPERTY;
		}
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
