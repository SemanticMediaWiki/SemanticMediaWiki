<?php

namespace SMW\Maintenance;

use Exception;
use LinkCache;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\ApplicationFactory;
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
	 * @var integer
	 */
	private $rebuildCount = 0;

	/**
	 * @var integer
	 */
	private $exceptionCount = 0;

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

			$this->canWriteToIdFile = $this->is_writable( $options->get( 'startidfile' )  );
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
			return $this->callDistinctEntityRebuilder();
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

	/**
	 * @since 3.0
	 *
	 * @return int
	 */
	public function getExceptionCount() {
		return $this->exceptionCount;
	}

	private function callDistinctEntityRebuilder() {

		$this->distinctEntityDataRebuilder->setOptions(
			$this->options
		);

		$this->distinctEntityDataRebuilder->setMessageReporter(
			$this->reporter
		);

		$this->distinctEntityDataRebuilder->setExceptionFileLogger(
			$this->exceptionFileLogger
		);

		$this->distinctEntityDataRebuilder->doRebuild();

		$this->rebuildCount = $this->distinctEntityDataRebuilder->getRebuildCount();

		if ( $this->options->has( 'ignore-exceptions' ) && $this->exceptionFileLogger->getExceptionCount() > 0 ) {
			$count = $this->exceptionFileLogger->getExceptionCount();
			$this->exceptionFileLogger->doWrite();

			$file = '...' . substr( $this->exceptionFileLogger->getExceptionFile(), -50 );

			$this->reportMessage( "\nException log ..." );
			$this->reportMessage( "\n   ... counted $count exceptions (see $file)"	);
			$this->reportMessage( "\n   ... done.\n" );
			$this->exceptionCount += $count;
		}

		return true;
	}

	private function doRebuildAll() {

		$entityRebuildDispatcher = $this->store->refreshData(
			$this->start,
			1
		);

		$entityRebuildDispatcher->setDispatchRangeLimit( 1 );

		$entityRebuildDispatcher->setUpdateJobParseMode(
			$this->options->has( 'shallow-update' ) ? SMW_UJ_PM_CLASTMDATE : false
		);

		$entityRebuildDispatcher->useJobQueueScheduler( false );

		// Only expect the disposal action?
		if ( $this->dispose_outdated() ) {
			return true;
		}

		$this->reportMessage( "\n" );

		if ( !$this->options->has( 'skip-properties' ) ) {
			$this->options->set( 'p', true );
			$this->callDistinctEntityRebuilder();
			$this->reportMessage( "\n" );
		}

		$this->store->clear();

		$this->reportMessage(
			"Refreshing semantic data ...\n"
		);

		$this->reportMessage(
			"\nLong-running scripts may cause memory leaks, if a deteriorating\n" .
			"rebuild process is detected (after many pages, typically more\n".
			"than 10000), please abort with CTRL-C and resume this script\n" .
			"at the last processed ID using the parameter -s. Continue this\n" .
			"until all pages have been refreshed.\n"
		);

		$total = $this->end && $this->end - $this->start > 0 ? $this->end - $this->start : $entityRebuildDispatcher->getMaxId();
		$id = $this->start;

		$this->reportMessage(
			"\nThe progress displayed is an estimation and is self-adjusting \n" .
			"during the update process.\n" );

		$this->reportMessage(
			"\nProcessing IDs from $this->start to " .
			( $this->end ? "$this->end" : $entityRebuildDispatcher->getMaxId() ) . " ...\n"
		);

		$this->rebuildCount = 0;
		$progress = 0;
		$estimatedProgress = 0;

		while ( ( ( !$this->end ) || ( $id <= $this->end ) ) && ( $id > 0 ) ) {

			$current_id = $id;

			// Changes the ID to next target!
			$this->doUpdate( $entityRebuildDispatcher, $id );

			if ( $this->rebuildCount % 60 === 0 ) {
				$estimatedProgress = $entityRebuildDispatcher->getEstimatedProgress();
			}

			$progress = round( ( $this->end - $this->start > 0 ? $this->rebuildCount / $total : $estimatedProgress ) * 100 );

			foreach ( $entityRebuildDispatcher->getDispatchedEntities() as $value ) {

				$text = $this->getHumanReadableTextFrom( $current_id, $value );

				$this->reportMessage(
					sprintf( "%-16s%s\n", "   ... updating", sprintf( "%-10s%s", $text[0], $text[1] ) ),
					$this->options->has( 'v' )
				);
			}

			if ( !$this->options->has( 'v' ) && $id > 0 ) {
				$this->reportMessage(
					"\r". sprintf( "%-50s%s", "   ... updating document no.", sprintf( "%s (%1.0f%%)", $id, min( 100, $progress ) ) )
				);
			}
		}

		$this->write_to_file( $id );

		$this->reportMessage( "\n   ... $this->rebuildCount IDs refreshed ..." );
		$this->reportMessage( "\n   ... done.\n" );

		if ( $this->options->has( 'ignore-exceptions' ) && $this->exceptionFileLogger->getExceptionCount() > 0 ) {
			$this->exceptionCount += $this->exceptionFileLogger->getExceptionCount();
			$this->exceptionFileLogger->doWrite();

			$file = '...' . substr( $this->exceptionFileLogger->getExceptionFile(), -50 );

			$this->reportMessage( "\nException log ..." );
			$this->reportMessage( "\n   ... counted $this->exceptionCount exceptions (see $file)"	);
			$this->reportMessage( "\n   ... done.\n" );
		}

		return true;
	}

	private function doUpdate( $entityRebuildDispatcher, &$id ) {

		if ( !$this->options->has( 'ignore-exceptions' ) ) {
			$entityRebuildDispatcher->rebuild( $id );
		} else {

			try {
				$entityRebuildDispatcher->rebuild( $id );
			} catch ( Exception $e ) {
				$this->exceptionFileLogger->recordException( $id, $e );
			}
		}

		if ( $this->delay !== false ) {
			usleep( $this->delay );
		}

		if ( $this->rebuildCount % 100 === 0 ) { // every 100 pages only
			LinkCache::singleton()->clear(); // avoid memory leaks
		}

		$this->rebuildCount++;
	}

	private function getHumanReadableTextFrom( $id, array $entities ) {

		if ( !$this->options->has( 'v' ) ) {
			return [ '', ''];
		}

		// Indicates whether this is a MW page (*) or SMW's object table
		$text = $id . ( isset( $entities['t'] ) ? '*' : ' ' );

		$entity = end( $entities );

		if ( $entity instanceof \Title ) {
			return [ $text, '[' . $entity->getPrefixedDBKey() .']' ];
		}

		if ( $entity instanceof DIWikiPage ) {
			return [ $text, '[' . $entity->getHash() .']' ];
		}

		return [ $text, '[' . ( is_string( $entity ) && $entity !== '' ? $entity : 'N/A' ) . ']' ];
	}

	private function performFullDelete() {

		$this->reportMessage(
			"Deleting all stored data completely and rebuilding it again later!\n\n" .
			"Semantic data in the wiki might be incomplete for some time while\n".
			"this operation runs.\n\n" .
			"NOTE: It is usually necessary to run this script ONE MORE TIME\n".
			"after this operation,since some properties' types are not stored\n" .
			"yet in the first run.\n\n"
		);

		if ( $this->options->has( 's' ) || $this->options->has( 'e' ) ) {
			$this->reportMessage(
				"WARNING: -s or -e are used, so some pages will not be refreshed at all!\n" .
				"Data for those pages will only be available again when they have been\n" .
				"refreshed as well!\n\n"
			);
		}

		$obLevel = ob_get_level();

		$this->reportMessage( 'Abort with control-c in the next five seconds ...  ' );
		swfCountDown( 6 );

		$this->reportMessage( "\nDeleting all data ..." );

		$this->reportMessage( "\n   ... dropping tables ..." );
		$this->store->drop( $this->verbose );

		$this->reportMessage( "\n   ... creating tables ..." );
		$this->store->setupStore( $this->verbose );

		$this->reportMessage( "\n   ... done.\n" );

		// Be sure to have some buffer, otherwise some PHPs complain
		while ( ob_get_level() > $obLevel ) {
			ob_end_flush();
		}

		$this->reportMessage( "\nAll storage structures have been deleted and recreated.\n\n" );

		return true;
	}

	private function dispose_outdated() {

		$applicationFactory = ApplicationFactory::getInstance();
		$entityIdDisposerJob = $applicationFactory->newJobFactory()->newEntityIdDisposerJob(
			Title::newFromText( __METHOD__ )
		);

		$outdatedEntitiesResultIterator = $entityIdDisposerJob->newOutdatedEntitiesResultIterator();
		$matchesCount = $outdatedEntitiesResultIterator->count();
		$counter = 0;

		$this->reportMessage( "Removing outdated entities ..." );

		if ( $matchesCount > 0 ) {
			$this->reportMessage( "\n" );

			$chunkedIterator = $applicationFactory->getIteratorFactory()->newChunkedIterator(
				$outdatedEntitiesResultIterator,
				200
			);

			foreach ( $chunkedIterator as $chunk ) {
				foreach ( $chunk as $row ) {
					$counter++;
					$msg = sprintf( "%s (%1.0f%%)", $row->smw_id, round( $counter / $matchesCount * 100 ) );

					$this->reportMessage(
						"\r". sprintf( "%-50s%s", "   ... cleaning up document no.", $msg )
					);

					$entityIdDisposerJob->dispose( $row );
				}
			}

			$this->reportMessage( "\n   ... {$matchesCount} IDs removed ..." );
		}

		$this->reportMessage( "\n   ... done.\n" );

		return $this->options->has( 'dispose-outdated' );
	}

	private function is_writable( $startIdFile ) {

		if ( !is_writable( file_exists( $startIdFile ) ? $startIdFile : dirname( $startIdFile ) ) ) {
			die( "Cannot use a startidfile that we can't write to.\n" );
		}

		return true;
	}

	private function write_to_file( $id ) {
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

}
