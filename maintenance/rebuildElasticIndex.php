<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Elastic\ElasticFactory;
use SMW\Setup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv('MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RebuildElasticIndex extends \Maintenance {

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
	 * @see Maintenance::__construct
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$this->mDescription = 'Rebuild the Elasticsearch index from property tables (content is not explicitly parsed!)';
		$this->addOption( 's', 'Start with a selected document no.', false, true );
		$this->addOption( 'e', 'End with a selected document no. (requires a start ID)', false, true );
		$this->addOption( 'page', 'Set of pages (Foo|Bar|...)', false, true );
		$this->addOption( 'update-settings', 'Update settings and mappings for listed indices', false, false );
		$this->addOption( 'force-refresh', 'Forces a refresh of listed indices', false, false );
		$this->addOption( 'delete-all', 'Delete listed indices without rebuilding the data', false, false );
		$this->addOption( 'skip-fileindex', 'Skipping any file ingest actions', false, false );
		$this->addOption( 'run-fileindex', 'Only run file ingest actions', false, false );

		$this->addOption( 'debug', 'Sets global variables to support debug ouput while running the script', false );
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );

		parent::__construct();
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

		if ( !Setup::isEnabled() ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		if ( !Setup::isValid( true ) ) {
			$this->reportMessage( "\nYou need to run `update.php` or `setupStore.php` first before continuing\nwith any maintenance tasks!\n" );
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

		$this->jobQueue = $applicationFactory->getJobQueue();
		$this->store = $applicationFactory->getStore( 'SMW\SQLStore\SQLStore' );
		$elasticFactory = $applicationFactory->create( 'ElasticFactory' );

		$this->rebuilder = $elasticFactory->newRebuilder(
			$this->store
		);

		$this->rebuilder->setMessageReporter(
			$maintenanceFactory->newMessageReporter( [ $this, 'reportMessage' ] )
		);

		if ( !$this->rebuilder->ping() ) {
			return $this->reportMessage(
				"\n" . 'Elasticsearch endpoint(s) are not available!' . "\n"
			);
		}

		$this->reportMessage(
			"\nThe script rebuilds the index from available property tables. Any\n" .
			"change of the index rules (e.g. altered stopwords, new stemmer etc.)\n" .
			"or a newly added (or altered) table requires to run this script again\n" .
			"to ensure that the index complies with the rules set forth by the SQL\n" .
			"back-end or the Elasticsearch field mapping.\n"
		);

		if ( $this->otherActivities() ) {
			return true;
		}

		$this->showAbort();

		$this->reportMessage(
			"\nIf for some reason the rebuild process is aborted, please make sure\n" .
			"to run `--update-settings` so that default settings can be recovered\n".
			"and set to a normal working mode.\n"
		);

		$this->rebuild();

		if ( $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( "\n" . $maintenanceHelper->getFormattedRuntimeValues() . "\n" );
		}

		$maintenanceHelper->reset();

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

	private function otherActivities() {

		if ( $this->hasOption( 'update-settings' ) ) {
			$this->reportMessage(
				"\n" . 'Settings and mappings ...'
			);

			$message = $this->rebuilder->setDefaults() ? '   ... done.' : '   ... failed (due to missing index).';
			$this->reportMessage( "\n$message\n" );

			return true;
		}

		if ( $this->hasOption( 'force-refresh' ) ) {
			$this->reportMessage(
				"\n" . 'Forcing refresh of known indices ...'
			);

			$message = $this->rebuilder->refresh() ? '   ... done.' : '   ... failed (due to missing index).';
			$this->reportMessage( "\n$message\n" );

			return true;
		}

		if ( $this->hasOption( 'delete-all' ) ) {
			$this->reportMessage(
				"\n" . 'Deleting all indices ...'
			);

			$this->rebuilder->deleteAndSetupIndices();
			$this->reportMessage( "\n   ... done.\n" );

			return true;
		}

		return false;
	}

	private function showAbort() {

		$showAbort = !$this->hasOption( 'quick' ) && !$this->hasOption( 's' ) && !$this->hasOption( 'page' ) && !$this->hasOption( 'run-fileindex' );

		if ( !$showAbort ) {
			return;
		}

		$this->reportMessage(
			"\nThe rebuild will use a rollover approach which means that while the\n" .
			"new index is created, the old index is still available and allows\n" .
			"queries to work even though the rebuild is ongoing. Once completed,\n" .
			"a \"rollover\" will switch the indices at which point the old indices\n" .
			"are being removed.\n"
		);

		$this->reportMessage(
			"\nIt should be noted that the replication is paused for the duration\n" .
			"of the rebuild to allow changes to pages and annotations to be\n" .
			"processed after the re-index has been completed therefore running\n".
			"the job scheduler is obligatory.\n"
		);

		$this->reportMessage( "\n" . 'Abort the rebuild with control-c in the next five seconds ...  ' );
		swfCountDown( 5 );
	}

	private function rebuild() {

		$this->reportMessage( "\nRebuilding indices ..." );
		$isSelective = $this->hasOption( 's' ) || $this->hasOption( 'page' );

		if ( !$this->hasOption( 's' ) && !$this->hasOption( 'page' ) && !$this->hasOption( 'run-fileindex' ) ) {
			$this->reportMessage( "\n" . '   ... creating required indices and aliases ...' );
			$this->rebuilder->createIndices();
		} else {
			if ( !$this->rebuilder->hasIndices() ) {
				$this->reportMessage( "\n" . '   ... creating required indices and aliases ...' );
				$this->rebuilder->createIndices();
			}
		}

		$this->rebuilder->prepare();

		list( $res, $last ) = $this->rebuilder->select(
			$this->store,
			$this->select_conditions()
		);

		if ( $isSelective ) {
			$last = $res->numRows();
		}

		if ( $res->numRows() > 0 ) {
			$this->reportMessage( "\n" );
		} else {
			$this->reportMessage( "\n" . '   ... no documents to process ...' );
		}

		$this->rebuilder->set( 'skip-fileindex', $this->getOption( 'skip-fileindex' ) );
		$i = 0;

		foreach ( $res as $row ) {
			$i++;
			$this->rebuild_row( $i, $row, $last, $isSelective );
		}

		$this->rebuilder->setDefaults();
		$this->rebuilder->refresh();

		$this->reportMessage( "\n" . '   ... done.' . "\n" );

		if ( ( $count = $this->jobQueue->getQueueSize( 'smw.elasticIndexerRecovery' ) ) > 0 ) {
			$this->reportMessage( "\n" . "Job queue ..." );
			$this->reportMessage( "\n" . "   ... smw.elasticIndexerRecovery has $count unprocessed jobs ..." );
			$this->reportMessage( "\n" . '   ... done.' . "\n" );
		}
	}

	private function rebuild_row( $i, $row, $last, $isSelective ) {

		$i = $isSelective ? $i : $row->smw_id;
		$key = $isSelective ? '(count)' : 'no.';

		$this->reportMessage(
			"\r". sprintf( "%-50s%s", "   ... updating document $key", sprintf( "%4.0f%% (%s/%s)", ( $i / $last ) * 100, $i, $last ) )
		);

		if ( $row->smw_iw === SMW_SQL3_SMWDELETEIW || $row->smw_iw === SMW_SQL3_SMWREDIIW ) {
			return $this->rebuilder->delete( $row->smw_id );
		}

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

		if ( $this->hasOption( 's' ) ) {
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
		}

		return $conditions;
	}

}

$maintClass = 'SMW\Maintenance\RebuildElasticIndex';
require_once( RUN_MAINTENANCE_IF_MAIN );
