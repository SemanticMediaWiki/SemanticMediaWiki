<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\Setup;
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
class removeDuplicateEntities extends \Maintenance {

	/**
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Remove duplicate entities without active references.';
		$this->addOption( 's', 'ID starting point', false, true );
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( $this->canExecute() !== true ) {
			exit;
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"The script will only dispose of those duplicate entities that have no active",
			"references."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Duplicate disposal' )
		);

		$this->reportMessage( "\nPrepare tables ...\n" );

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		$duplicateEntitiesDisposer = $maintenanceFactory->newDuplicateEntitiesDisposer(
			$applicationFactory->getStore( 'SMW\SQLStore\SQLStore' ),
			[ $this, 'reportMessage' ]
		);

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... fetching from table ...", 3 )
		);

		$duplicateEntityRecords = $duplicateEntitiesDisposer->findDuplicates();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage( "   ... done.\n" );

		$duplicateEntitiesDisposer->verifyAndDispose( $duplicateEntityRecords );

		if ( $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( $cliMsgFormatter->section( 'Runtime report' ) );

			$this->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues()
			);
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

}

$maintClass = removeDuplicateEntities::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
