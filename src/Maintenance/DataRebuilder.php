<?php

namespace SMW\Maintenance;

use Exception;
use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\TitleFactory;
use SMW\Maintenance\DataRebuilder\OutdatedDisposer;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\Utils\CliMsgFormatter;
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

	const AUTO_RECOVERY_ID = 'ar_id';
	const AUTO_RECOVERY_LAST_START = 'ar_last_start';

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
	 * @var AutoRecovery
	 */
	private $autoRecovery;

	/**
	 * @var DistinctEntityDataRebuilder
	 */
	private $distinctEntityDataRebuilder;

	/**
	 * @var ExceptionFileLogger
	 */
	private $exceptionFileLogger;

	/**
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

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
	private $filters = [];
	private $verbose = false;
	private $startIdFile = false;
	private Rebuilder $entityRebuildDispatcher;

	/**
	 * @since 1.9.2
	 *
	 * @param Store $store
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( Store $store, TitleFactory $titleFactory ) {
		$this->store = $store;
		$this->titleFactory = $titleFactory;
		$this->reporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
		$this->distinctEntityDataRebuilder = new DistinctEntityDataRebuilder( $store, $titleFactory );
		$this->exceptionFileLogger = new ExceptionFileLogger( 'rebuilddata' );
		$this->cliMsgFormatter = new CliMsgFormatter();
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
	 * @since 3.1
	 *
	 * @param AutoRecovery $autoRecovery
	 */
	public function setAutoRecovery( AutoRecovery $autoRecovery ) {
		$this->autoRecovery = $autoRecovery;
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

			$this->canWriteToIdFile = $this->is_writable( $options->get( 'startidfile' ) );
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

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Notice' )
		);

		$text = [
			'Long-running scripts may cause memory leaks, if a deteriorating rebuild',
			'process is detected (after many pages, typically more than 10000),',
			'please abort with CTRL-C and resume this script at the last processed',
			'ID using the parameter -s. Continue this until all pages have been',
			'refreshed.',
			"\n\n",
			'Using the --auto-recovery option should help with unexpected events or',
			'disruptions (CTRL-C, exceptions etc.) during the rebuild process to',
			'recover and restart from the last successful processed ID.',
			"\n\n",
			'Any progress displayed is an estimation and is self-adjusting',
			'during the maintenance process.'
		];

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( $this->options->has( 'f' ) ) {
			$this->performFullDelete();
		}

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Data rebuild' )
		);

		$this->reportMessage( "\n" );

		$this->reportMessage(
			$this->cliMsgFormatter->twoCols( 'Storage engine:', $GLOBALS['smwgDefaultStore'] )
		);

		if (
			$this->options->has( 'page' ) ||
			$this->options->has( 'query' ) ||
			$this->hasFilters() ||
			$this->options->has( 'redirects' ) ) {
			return $this->rebuildFromSelection();
		}

		return $this->rebuildAll();
	}

	private function hasFilters() {
		return $this->filters !== [];
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

	private function rebuildFromSelection( $params = [] ) {

		if ( $params !== [] ) {
			foreach ( $params as $key => $value ) {
				$this->options->set( $key, $value );
			}
		}

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

			$path_parts = pathinfo(
				str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $this->exceptionFileLogger->getExceptionFile() )
			);

			$this->reportMessage( "\nException log ..." );
			$this->reportMessage( "\n   ... counted $count exceptions" );
			$this->reportMessage( "\n   ... written to ... " . $path_parts['basename'] );
			$this->reportMessage( "\n   ... done.\n" );

			$this->exceptionCount += $count;
		}

		return true;
	}

	private function rebuildAll() {

		$this->entityRebuildDispatcher = $this->store->refreshData(
			$this->start,
			1
		);

		$this->entityRebuildDispatcher->setDispatchRangeLimit( 1 );

		$this->entityRebuildDispatcher->setOptions(
			[
				'shallow-update' => $this->options->safeGet( 'shallow-update', false ),
				'force-update' => $this->options->safeGet( 'force-update', false ),
				'revision-mode' => $this->options->safeGet( 'revision-mode', false ),
				'use-job' => false
			]
		);

		// By default we expect the disposal action to take place whenever the
		// script is run
		$this->runOutdatedDisposer();

		// Only expected the disposal action?
		if ( $this->options->has( 'dispose-outdated' ) ) {
			return true;
		}

		if ( !$this->options->has( 'skip-properties' ) ) {
			$this->rebuildFromSelection( [ 'p' => true ] );
		}

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Rebuild (data)', 3, '-', true ) . "\n"
		);

		if ( $this->autoRecovery !== null && $this->autoRecovery->has( self::AUTO_RECOVERY_ID ) ) {
			$this->start = $this->autoRecovery->get( self::AUTO_RECOVERY_ID );

			$this->reportMessage( "Detecting an incomplete rebuild run ...\n" );

			if ( ( $last_start = $this->autoRecovery->get( self::AUTO_RECOVERY_LAST_START ) ) ) {
				$this->reportMessage(
					$this->cliMsgFormatter->twoCols( '   ... rebuild record from', $last_start )
				);
			}

			$this->reportMessage(
				$this->cliMsgFormatter->twoCols( '   ... starting with ID', $this->start )
			);

			$this->reportMessage( "\n" );
		}

		$this->store->clear();
		$maxId = $this->entityRebuildDispatcher->getMaxId();

		if ( $this->start > 1 && $this->end === false ) {
			$this->end = $maxId;
		}

		if ( $this->end && $this->end - $this->start > 0 ) {
			$total = $this->end - $this->start;
		} else {
			$total = $maxId;
		}

		$id = $this->start;

		$this->reportMessage(
			"Rebuilding semantic data ..."
		);

		$this->reportMessage(
			"\n   ... selecting $this->start to " .
			( $this->end ? "$this->end" : $maxId ) . " IDs ...\n"
		);

		$this->rebuildCount = $this->start;
		$progress = 0;
		$estimatedProgress = 0;
		$skipped_update = 0;
		$current_id = 0;
		$max = ( $this->end ? "$this->end" : $maxId );

		while (( ( !$this->end ) || ( $id <= $this->end ) ) && ( $id > 0 )) {

			if ( $this->autoRecovery !== null ) {
				$this->autoRecovery->set( self::AUTO_RECOVERY_ID, (int)$id );
			}

			$current_id = $id;

			// Changes the ID to next target!
			$this->doUpdateById( $id );

			// Refresh progressively
			if ( $this->rebuildCount % round( log10( $this->rebuildCount ) * 100, 0 ) === 0 ) {
				$estimatedProgress = $this->entityRebuildDispatcher->getEstimatedProgress();
				$max = $this->end ? "$this->end" : $this->entityRebuildDispatcher->getMaxId();
			}

			$progress = $this->cliMsgFormatter->progressCompact( $current_id, $max );

			foreach ( $this->entityRebuildDispatcher->getDispatchedEntities() as $value ) {

				if ( isset( $value['skipped'] ) ) {
					$skipped_update++;
					continue;
				}

				$text = $this->getHumanReadableTextFrom( $current_id, $value );

				$this->reportMessage(
					sprintf( "%-16s%s\n", "   ... updating", sprintf( "%-10s%s", $text[0], $text[1] ) ),
					$this->options->has( 'v' )
				);
			}

			if ( !$this->options->has( 'v' ) && $id > 0 ) {
				$this->reportMessage(
					$this->cliMsgFormatter->twoColsOverride( '   ... updating ...', $progress )
				);
			}
		}

		if ( !$this->options->has( 'v' ) ) {
			$progress = $this->cliMsgFormatter->progressCompact( $current_id, $max );

			$this->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( '   ... updating ...', $progress )
			);

			$this->reportMessage( "\n" );
		}

		if ( $this->autoRecovery !== null ) {
			$this->autoRecovery->set( self::AUTO_RECOVERY_ID, false );
			$this->autoRecovery->set( self::AUTO_RECOVERY_LAST_START, false );
		}

		$this->write_to_file( $id );

		$this->reportMessage(
			$this->cliMsgFormatter->twoCols( '   ... refreshed (IDs)', sprintf( "%s", ( $this->rebuildCount - $this->start ) ) )
		);

		$this->reportMessage(
			$this->cliMsgFormatter->twoCols( '   ... skipped (IDs)', sprintf( "%s", $skipped_update ) )
		);

		$this->reportMessage( "   ... done.\n" );

		if ( $this->options->has( 'ignore-exceptions' ) && $this->exceptionFileLogger->getExceptionCount() > 0 ) {
			$this->reportMessage(
				$this->cliMsgFormatter->section( 'Exception log' )
			);

			$this->exceptionCount += $this->exceptionFileLogger->getExceptionCount();
			$this->exceptionFileLogger->doWrite();

			$path_parts = pathinfo(
				str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $this->exceptionFileLogger->getExceptionFile() )
			);

			$this->reportMessage( "\nReport exceptions ..." );
			$this->reportMessage( "\n   ... counted $this->exceptionCount exceptions" );
			$this->reportMessage( "\n   ... written to ... " . $path_parts['basename'] );
			$this->reportMessage( "\n   ... done.\n" );
		}

		return true;
	}

	private function doUpdateById( &$id ) {

		if ( !$this->options->has( 'ignore-exceptions' ) ) {
			$this->entityRebuildDispatcher->rebuild( $id );
		} else {

			try {
				$this->entityRebuildDispatcher->rebuild( $id );
			} catch ( Exception $e ) {
				$this->exceptionFileLogger->recordException( $id, $e );
			}
		}

		if ( $this->delay !== false ) {
			usleep( $this->delay );
		}

		if ( $this->rebuildCount % 100 === 0 ) { // every 100 pages only
			MediaWikiServices::getInstance()->getLinkCache()->clear(); // avoid memory leaks
		}

		$this->rebuildCount++;
	}

	private function getHumanReadableTextFrom( $id, array $entities ) {

		if ( !$this->options->has( 'v' ) ) {
			return [ '', '' ];
		}

		// Indicates whether this is a MW page (*) or SMW's object table
		$text = $id;

		$prefix = isset( $entities['t'] ) ? 'T:' : 'S:';
		$entity = end( $entities );

		if ( $entity instanceof \Title ) {
			return [ $text, "[$prefix " . $entity->getPrefixedDBKey() . ']' ];
		}

		if ( $entity instanceof DIWikiPage ) {
			return [ $text, "[$prefix " . $entity->getHash() . ']' ];
		}

		return [ $text, "[$prefix " . ( is_string( $entity ) && $entity !== '' ? $entity : 'N/A' ) . ']' ];
	}

	private function performFullDelete() {

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Delete data' )
		);

		$text = [
			'Deleting all stored data completely and rebuilding it again later!',
			"\n\n",
			'Semantic data in the wiki might be incomplete for some time while',
			'this operation runs.',
			"\n\n",
			'NOTE: It is usually necessary to run this script ONE MORE TIME',
			'after this operation, given that some properties and types are not',
			'yet stored with the first run.',
		];

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( $this->options->has( 's' ) || $this->options->has( 'e' ) ) {
			$text = [
				"WARNING: -s or -e are used, so some pages will not be refreshed at all!",
				"Data for those pages will only be available again when they have been",
				"refreshed as well!"
			];

			$this->reportMessage(
				"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
			);
		}

		$this->reportMessage( "\n" );

		$obLevel = ob_get_level();

		if ( !$this->options->has( 'quiet' ) ) {
			$this->reportMessage(
				$this->cliMsgFormatter->countDown( 'Abort with control-c in the next five seconds ...  ', 6 )
			);
		}

		$this->reportMessage( "\nDeleting all data ...\n" );

		$this->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... dropping tables ...' )
		);

		$this->store->drop( $this->verbose );

		$this->reportMessage(
			$this->cliMsgFormatter->secondCol( '✓' )
		);

		$this->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... creating tables ...' )
		);

		$this->store->setupStore( $this->verbose );

		$this->reportMessage(
			$this->cliMsgFormatter->secondCol( '✓' )
		);

		$this->reportMessage( "   ... done.\n" );

		// Be sure to have some buffer, otherwise some PHPs complain
		while ( ob_get_level() > $obLevel ) {
			ob_end_flush();
		}

		$this->reportMessage( "\nAll storage structures have been deleted and recreated.\n" );

		return true;
	}

	private function runOutdatedDisposer() {

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Disposal (outdated)', 3, '-', true ) . "\n"
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$title = Title::newFromText( __METHOD__ );

		$outdatedDisposer = new OutdatedDisposer(
			$applicationFactory->newJobFactory()->newEntityIdDisposerJob( $title ),
			$applicationFactory->getIteratorFactory()
		);

		$outdatedDisposer->setMessageReporter( $this->reporter );
		$outdatedDisposer->run();
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
		$this->filters = [];

		if ( $options->has( 'categories' ) ) {
			$this->filters[] = NS_CATEGORY;
		}

		if ( $options->has( 'namespace' ) ) {
			$this->filters[] = constant( $options->get( 'namespace' ) );
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
