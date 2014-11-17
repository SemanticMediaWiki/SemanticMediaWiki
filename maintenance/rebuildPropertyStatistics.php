<?php

namespace SMW\Maintenance;

use SMW\SQLStore\SimplePropertyStatisticsRebuilder;
use SMW\SQLStore\PropertyStatisticsTable;
use SMW\Reporter\ObservableMessageReporter;
use SMW\StoreFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the property usage statistics.
 *
 * TODO: make this work with all stores (Right now it only works with SQLStore3)
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
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

		$store = StoreFactory::getStore();

		$statsTable = new PropertyStatisticsTable(
			$store->getConnection( 'mw.db' ),
			\SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		// Need to instantiate an extra object here since we cannot make this class itself
		// into a MessageReporter since the maintenance script does not load the interface in time.
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$statisticsRebuilder = new SimplePropertyStatisticsRebuilder( $store, $reporter );
		$statisticsRebuilder->rebuild( $statsTable );
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
require_once( RUN_MAINTENANCE_IF_MAIN );
