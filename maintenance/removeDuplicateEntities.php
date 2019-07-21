<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\Setup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv(
'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RemoveDuplicateEntities extends \Maintenance {

	/**
	 * @since 3.0
	 */
	public function __construct() {
		$this->mDescription = 'Remove duplicate entities without active references.';
		$this->addOption( 's', 'ID starting point', false, true );
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );

		parent::__construct();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 3.0
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have SMW enabled in order to run the maintenance script!\n" );
			exit;
		}

		if ( !Setup::isValid() ) {
			$this->reportMessage( "\nYou need to run `update.php` or `setupStore.php` first before continuing\nwith any maintenance tasks!\n" );
			exit;
		}

		$this->reportMessage(
			"\nThe script will only dispose of those duplicate entities that have no active\n" .
			"references.\n"
		);

		$this->reportMessage( "\nQuering registered tables ...\n" );

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		$duplicateEntitiesDisposer = $maintenanceFactory->newDuplicateEntitiesDisposer(
			$applicationFactory->getStore( 'SMW\SQLStore\SQLStore' ),
			[ $this, 'reportMessage' ]
		);

		$duplicateEntityRecords = $duplicateEntitiesDisposer->findDuplicates();
		$duplicateEntitiesDisposer->verifyAndDispose( $duplicateEntityRecords );

		if ( $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( "\n" . "Runtime report ..." . "\n" );
			$this->reportMessage( $maintenanceHelper->getFormattedRuntimeValues( '   ...' ) . "\n" );
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RemoveDuplicateEntitiesLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];

			$maintenanceLogger->logFromArray( $log );
		}

		return true;
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\RemoveDuplicateEntities';
require_once( RUN_MAINTENANCE_IF_MAIN );
