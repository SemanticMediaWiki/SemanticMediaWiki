<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Elastic\ElasticFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv(
'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

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

		if ( !defined( 'SMW_VERSION' ) ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
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
		$elasticFactory = new ElasticFactory();

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
			"change of the index rules (altered stopwords, new stemmer etc.) and\n" .
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
			"and set for a normal working mode.\n"
		);

		$this->doRebuild();

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

	private function otherActivities() {

		if ( $this->hasOption( 'update-settings' ) ) {
			$this->reportMessage(
				"\n" . 'Updating index settings and mappings ...'
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

			$this->rebuilder->deleteIndices();
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
		wfCountDown( 5 );
	}

	private function doRebuild() {

		$this->reportMessage( "\nRebuilding documents ..." );

		if ( !$this->hasOption( 's' ) && !$this->hasOption( 'page' ) && !$this->hasOption( 'run-fileindex' ) ) {
			$this->reportMessage( "\n" . '   ... creating required indices and aliases ...' );
			$this->rebuilder->createIndices();
		}

		$this->rebuilder->prepare();

		list( $res, $last ) = $this->rebuilder->select(
			$this->store,
			$this->buildConditions()
		);

		if ( $res->numRows() > 0 ) {
			$this->reportMessage( "\n" );
		} else {
			$this->reportMessage( "\n" . '   ... no documents to process ...' );
		}

		$this->rebuilder->set( 'skip-fileindex', $this->getOption( 'skip-fileindex' ) );

		foreach ( $res as $row ) {
			$this->rebuildByRow( $row, $last );
		}

		if ( ( $index = $this->rebuilder->rollover() ) ) {
			$this->reportMessage(
				"\n" . sprintf( "   ... starting rollover from %s to %s index ...", $index[1], $index[0] )
			);
		}

		$this->reportMessage( "\n" . '   ... updating index settings and mappings ...' );
		$this->rebuilder->setDefaults();

		$this->reportMessage( "\n" . '   ... refreshing indices ...' );
		$this->rebuilder->refresh();

		$this->reportMessage( "\n" . '   ... done.' . "\n" );

		if ( ( $count = $this->jobQueue->getQueueSize( 'smw.elasticIndexerRecovery' ) ) > 0 ) {
			$this->reportMessage( "\n" . "The smw.elasticIndexerRecovery queue has $count unprocessed jobs." . "\n" );
		}
	}

	private function rebuildByRow( $row, $last ) {

		$i = $row->smw_id;

		$this->reportMessage(
			"\r". sprintf( "%-47s%s", "   ... updating document no.", sprintf( "%4.0f%% (%s/%s)", ( $i / $last ) * 100, $i, $last ) )
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

		$this->rebuilder->rebuild(
			$row->smw_id,
			$this->store->getSemanticData( $dataItem )
		);
	}

	private function buildConditions() {

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

				$cond = [
					'smw_title=' . $connection->addQuotes( $title->getDBKey() ),
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
