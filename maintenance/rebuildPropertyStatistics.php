<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\Setup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the property usage statistics.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RebuildPropertyStatistics extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the property usage statistics (only works with SQLStore3 for now)';
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );

		parent::__construct();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 1.9
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
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

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		$statisticsRebuilder = $maintenanceFactory->newPropertyStatisticsRebuilder(
			$applicationFactory->getStore( 'SMW\SQLStore\SQLStore' ),
			[ $this, 'reportMessage' ]
		);

		$statisticsRebuilder->rebuild();

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildPropertyStatisticsLogger' );
			$maintenanceLogger->log( $maintenanceHelper->getFormattedRuntimeValues() );
		}
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

$maintClass = 'SMW\Maintenance\RebuildPropertyStatistics';
require_once ( RUN_MAINTENANCE_IF_MAIN );
