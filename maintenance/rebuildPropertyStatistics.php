<?php

namespace SMW\Maintenance;

use SMW\SQLStore\PropertyStatisticsTable;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\ApplicationFactory;

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

		parent::__construct();
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

		$store = $applicationFactory->getStore();

		$statsTable = new PropertyStatisticsTable(
			$store->getConnection( 'mw.db' ),
			\SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		// Need to instantiate an extra object here since we cannot make this class itself
		// into a MessageReporter since the maintenance script does not load the interface in time.
		$reporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$statisticsRebuilder = $maintenanceFactory->newPropertyStatisticsRebuilder(
			$store,
			$statsTable
		);

		$statisticsRebuilder->setMessageReporter( $reporter );
		$statisticsRebuilder->rebuild();
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
