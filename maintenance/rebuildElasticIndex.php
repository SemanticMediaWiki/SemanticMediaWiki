<?php

namespace SMW\Maintenance;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\ElasticStore;
use SMW\Setup;
use SMW\SetupFile;
use SMW\Utils\CliMsgFormatter;

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class rebuildElasticIndex extends \Maintenance {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Rebuilder
	 */
	private $rebuilder;

	/**
	 * @var JobQueue
	 */
	private $jobQueue;

	/**
	 * @var AutoRecovery
	 */
	private $autoRecovery;

	/**
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @see Maintenance::__construct
	 *
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Rebuild the Elasticsearch index from property tables (content is not explicitly parsed!)' );
		$this->addOption( 's', 'Start with a selected document no.', false, true );
		$this->addOption( 'e', 'End with a selected document no. (requires a start ID)', false, true );
		$this->addOption( 'page', 'Set of pages (Foo|Bar|...)', false, true );
		$this->addOption( 'update-settings', 'Update settings and mappings for listed indices', false, false );
		$this->addOption( 'force-refresh', 'Forces a refresh of listed indices', false, false );
		$this->addOption( 'delete-all', 'Delete listed indices without rebuilding the data', false, false );
		$this->addOption( 'skip-fileindex', 'Skipping any file ingest actions', false, false );
		$this->addOption( 'run-fileindex', 'Only run file ingest actions', false, false );
		$this->addOption( 'auto-recovery', 'Allows to restart from a canceled (or aborted) index run', false, false );
		$this->addOption( 'only-update', 'Run an update without switching indices and a rollover (short cut for -s 1)', false, false );

		$this->addOption( 'debug', 'Sets global variables to support debug ouput while running the script', false );
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		if ( $this->canExecute() !== true ) {
			exit;
		}

		// If available, set a callback to listen to a possible user termination
		// and try to recover the index settings.
		if ( function_exists( 'pcntl_signal_dispatch' ) ) {
			pcntl_signal( SIGTERM, [ $this, 'handleTermSignal' ], false );
			pcntl_signal_dispatch();
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		if ( $this->hasOption( 'debug' ) ) {
			$maintenanceHelper->setGlobalToValue( 'wgShowExceptionDetails', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowSQLErrors', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowDBErrorBacktrace', true );
		} else {
			$maintenanceHelper->setGlobalToValue( 'wgDebugLogFile', '' );
			$maintenanceHelper->setGlobalToValue( 'wgDebugLogGroups', [] );
		}

		$this->autoRecovery = $maintenanceFactory->newAutoRecovery( 'rebuildElasticIndex.php' );

		$this->autoRecovery->enable(
			$this->hasOption( 'auto-recovery' )
		);

		$this->jobQueue = $applicationFactory->getJobQueue();
		$this->store = $applicationFactory->getStore( 'SMW\SQLStore\SQLStore' );
		$elasticFactory = $applicationFactory->create( 'ElasticFactory' );
		$messageReporter = $maintenanceFactory->newMessageReporter( [ $this, 'reportMessage' ] );

		$this->rebuilder = $elasticFactory->newRebuilder(
			$this->store
		);

		$this->rebuilder->setMessageReporter(
			$messageReporter
		);

		if ( !$this->rebuilder->ping() ) {
			return $this->reportMessage(
				"\n" . 'Elasticsearch endpoint(s) are not available!' . "\n"
			);
		}

		if ( $this->hasOption( 'only-update' ) ) {
			$this->mOptions['s'] = 1;
		}

		$this->cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->head()
		);

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'About' )
		);

		$text = [
			"The script rebuilds the index from available property tables. Changes",
			"to the index rules (e.g. altered stopwords, new stemmer etc.) or a",
			"newly added (or altered) table requires to run this script again",
			"to ensure that the index complies with the rules set forth by the SQL",
			"back-end or the Elasticsearch field mapping."
		];

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( $this->otherActivities() ) {
			return true;
		}

		$this->showAbort();

		$text = [
			"If for some reason the rebuild process is aborted, please make sure",
			"to run `--update-settings` so that default settings can be recovered",
			"and set to the default replication mode."
		];

		$this->reportMessage(
			$this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->rebuild();

		if ( $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( $this->cliMsgFormatter->section( 'Runtime report' ) );

			$this->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues()
			);
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildElasticIndexLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];

			$maintenanceLogger->logFromArray( $log );
		}

		$maintenanceHelper->reset();
		$setupFile = new SetupFile();
		$setupFile->removeIncompleteTask( ElasticStore::REBUILD_INDEX_RUN_INCOMPLETE );

		$this->autoRecovery->set( 'ar_id', false );

		return true;
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 3.0
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
	}

	protected function handleTermSignal( $signal ) {
		$this->reportMessage( "\n" . '   ... rebuild was terminated, start recovery process ...' );
		$this->rebuilder->setDefaults();
		$this->rebuilder->refresh();
		$this->reportMessage( "\n" . '   ... done.' . "\n" );

		pcntl_signal( SIGTERM, SIG_DFL );
		exit( 1 );
	}

	private function canExecute() {
		if ( !Setup::isEnabled() ) {
			return $this->reportMessage(
				"\nYou need to have SMW enabled in order to run the maintenance script!\n"
			);
		}

		if ( !Setup::isValid( true ) ) {
			return $this->reportMessage(
				"\nYou need to run `update.php` or `setupStore.php` first before continuing\n" .
				"with this maintenance task!\n"
			);
		}

		return true;
	}

	private function otherActivities() {
		if ( $this->hasOption( 'update-settings' ) ) {

			$this->reportMessage(
				$this->cliMsgFormatter->section( 'Other activities' )
			);

			$this->reportMessage( "\n" . 'Settings and mappings ...' );
			$this->rebuilder->setDefaults();
			$this->reportMessage( "   ... done.\n" );

			return true;
		}

		if ( $this->hasOption( 'force-refresh' ) ) {

			$this->reportMessage(
				$this->cliMsgFormatter->section( 'Other activities' )
			);

			$this->reportMessage(
				"\n" . 'Forcing refresh of known indices ...' . "\n"
			);

			$this->rebuilder->refresh();
			$this->reportMessage( "   ... done.\n" );

			return true;
		}

		if ( $this->hasOption( 'delete-all' ) ) {

			$this->reportMessage(
				$this->cliMsgFormatter->section( 'Other activities' )
			);

			$this->reportMessage(
				"\n" . 'Deleting all indices ...'
			);

			$this->rebuilder->deleteAndSetupIndices();
			$this->reportMessage( "   ... done.\n" );

			return true;
		}

		return false;
	}

	private function showAbort() {
		$showAbort = !$this->hasOption( 'quick' ) && !$this->hasOption( 's' ) && !$this->hasOption( 'page' ) && !$this->hasOption( 'run-fileindex' );

		if ( $this->hasOption( 'auto-recovery' ) && $this->autoRecovery->has( 'ar_id' ) ) {
			$showAbort = false;
		}

		if ( !$showAbort ) {
			$this->reportMessage( "\n" );
			return;
		}

		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Notice' )
		);

		$text = [
			"The rebuild will use a rollover approach which means that while the new",
			"index is created, the old index is still available and allows queries",
			"to work even though the rebuild is ongoing. Once completed, a \"rollover\"",
			"will switch the indices at which point the old indices are being",
			"removed.",
			"\n\n",
			"It should be noted that the replication is paused for the duration",
			"of the rebuild to allow changes to pages and annotations to be",
			"processed after the re-index has been completed therefore running",
			"the job scheduler is obligatory."
		];

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( $this->hasOption( 'quiet' ) ) {
			return;
		}

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->countDown( 'Abort the rebuild with CTRL-C in ...', 5 )
		);
	}

	private function rebuild() {
		$this->reportMessage(
			$this->cliMsgFormatter->section( 'Indices rebuild' )
		);

		if ( $this->autoRecovery->has( 'ar_id' ) ) {

			$this->reportMessage(
				"\n" . $this->cliMsgFormatter->oneCol( 'Auto-recovery mode ...' )
			);

			$this->reportMessage(
				$this->cliMsgFormatter->twoCols( '... ID (document):', $this->autoRecovery->get( 'ar_id' ), 3 )
			);
		} elseif ( $this->hasOption( 's' ) || $this->hasOption( 'page' ) ) {
			//
		} else {
			$this->autoRecovery->set( 'ar_id', false );
		}

		$this->reportMessage( "\nRebuilding indices ...\n" );

		if (
			!$this->hasOption( 's' ) &&
			!$this->hasOption( 'page' ) &&
			!$this->hasOption( 'run-fileindex' ) &&
			!$this->hasOption( 'auto-recovery' ) ) {

			$this->reportMessage(
				$this->cliMsgFormatter->firstCol( '   ... creating required indices and aliases ...' )
			);

			$this->rebuilder->createIndices();

			$this->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		} elseif ( !$this->rebuilder->hasIndices() ) {

			$this->reportMessage(
				$this->cliMsgFormatter->firstCol( '   ... creating required indices and aliases ...' )
			);

			$this->rebuilder->createIndices();

			$this->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->rebuilder->prepare();
		$this->rebuilder->set( 'skip-fileindex', $this->getOption( 'skip-fileindex' ) );

		list( $res, $last ) = $this->rebuilder->select(
			$this->store,
			$this->select_conditions()
		);

		$count = $res->numRows();
		$i = 0;

		if ( $count == 0 ) {
			$this->reportMessage( '   ... no documents to process ...' );
		} else {
			$this->reportMessage(
				$this->cliMsgFormatter->twoCols( '... selected entities ...', "$count (rows)", 3 )
			);
		}

		foreach ( $res as $row ) {
			$this->rebuildFromRow( ++$i, $count, $row, $last );
		}

		// This is to match the last output to signal to the user that 100% were
		// matched even in the case where last wasn't a "real" entity and would
		// count towards the last. We don't want to confuse a user with some
		// unbalanced progress display.
		if ( $i > 0 ) {
			$progress = $this->cliMsgFormatter->progressCompact( $i, $count, $last, $last );

			$this->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( "   ... updating document (current/last) ...", $progress )
			);
		}

		$this->reportMessage( "\n   ... done.\n" );
		$this->reportMessage( "\n" . 'Settings and mappings ...' );

		$this->rebuilder->setDefaults();
		$this->rebuilder->refresh();

		$this->reportMessage( '   ... done.' . "\n" );

		if ( ( $count = $this->jobQueue->getQueueSize( 'smw.elasticIndexerRecovery' ) ) > 0 ) {
			$this->reportMessage(
				$this->cliMsgFormatter->section( 'Job queue' )
			);

			$this->reportMessage( "\n" );

			$this->reportMessage(
				$this->cliMsgFormatter->twoCols( "`smw.elasticIndexerRecovery` jobs:", "(Unprocessed) $count" )
			);
		}
	}

	private function rebuildFromRow( $i, $count, $row, $last ) {
		$progress = $this->cliMsgFormatter->progressCompact( $i, $count, $row->smw_id, $last );

		$this->reportMessage(
			$this->cliMsgFormatter->twoColsOverride( "   ... updating document (current/last) ...", $progress )
		);

		if ( $row->smw_iw === SMW_SQL3_SMWDELETEIW || $row->smw_iw === SMW_SQL3_SMWREDIIW ) {
			return $this->rebuilder->delete( $row->smw_id );
		}

		$this->autoRecovery->set( 'ar_id', (int)$row->smw_id );

		$dataItem = $this->store->getObjectIds()->getDataItemById(
			$row->smw_id
		);

		if ( $dataItem === null ) {
			return;
		}

		$semanticData = $this->store->getSemanticData( $dataItem );
		$semanticData->setExtensionData( 'revision_id', $row->smw_rev );

		$this->rebuilder->rebuild(
			$row->smw_id,
			$semanticData
		);
	}

	private function select_conditions() {
		$connection = $this->store->getConnection( 'mw.db' );

		$conditions = [];
		$conditions[] = "smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED );
		$conditions[] = "smw_subobject=''";
		$conditions[] = "smw_proptable_hash IS NOT NULL";

		if ( $this->hasOption( 'auto-recovery' ) && $this->autoRecovery->has( 'ar_id' ) ) {
			$conditions[] = 'smw_id >= ' . $connection->addQuotes( $this->autoRecovery->get( 'ar_id' ) );
		} elseif ( $this->hasOption( 's' ) ) {
			$conditions[] = 'smw_id >= ' . $connection->addQuotes( $this->getOption( 's' ) );

			if ( $this->hasOption( 'e' ) ) {
				$conditions[] = 'smw_id <= ' . $connection->addQuotes( $this->getOption( 'e' ) );
			}
		}

		if ( $this->hasOption( 'run-fileindex' ) ) {
			$conditions[] = 'smw_namespace=' . $connection->addQuotes( NS_FILE );
		}

		if ( $this->hasOption( 'page' ) ) {
			$pages = explode( '|', $this->getOption( 'page' ) );

			foreach ( $pages as $page ) {
				$title = \Title::newFromText( $page );

				if ( $title === null ) {
					continue;
				}

				$op = '=';
				$text = $title->getDBKey();

				// Match something like --page="Lorem*"
				if ( strpos( $title->getDBKey(), '*' ) !== false ) {
					$op = ' LIKE ';
					$text = str_replace( '*', '%', $text );
				}

				$cond = [
					"smw_title$op" . $connection->addQuotes( $text ),
					'smw_namespace=' . $connection->addQuotes( $title->getNamespace() )
				];

				$conditions[] = implode( ' AND ', $cond );
			}
		} elseif( !$this->hasOption( 's' ) || $this->getOption( 's' ) < 2 ) {
			// Make sure we always replicate properties whether they have a
			// `smw_proptable_hash` or not (which hints to predefined properties
			// without an actual page)
			$cond = [
				'smw_namespace=' . $connection->addQuotes( SMW_NS_PROPERTY )
			];

			$conditions = [ implode( ' AND ', $conditions ) . ' OR (' . implode( ' ', $cond ) . ')' ];
		}

		return $conditions;
	}

}

$maintClass = rebuildElasticIndex::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
