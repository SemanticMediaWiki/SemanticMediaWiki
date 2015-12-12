<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterFactory;
use SMW\ApplicationFactory;
use SMW\StoreFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class RebuildSearchFieldContent extends \Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( "\n" .
			"The script updates the search index field for tables that are known\n".
			"to support RegEx searches via the SQLStore. \n"
		);
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

		$this->reportMessage(
			"\nThe script updates the index field for tables that support regex\n" .
			"value searches. Any change of the index rules (altered stopword\n".
			"list, new stemmer etc.) and/or a newly added or altered table\n".
			"requires to run this script (or `rebuildData.php`) to ensure that\n" .
			"the index complies to the rules set forth by the `SearchField`\n".
			"class.\n\n" .
			"Depending on the size of the tables selected, it may take a moment\n".
			"before the update is completed.\n---\n"
		);

		$reporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$searchFieldContentRebuilder = $maintenanceFactory->newSearchFieldContentRebuilder(
			StoreFactory::getStore( 'SMW\SQLStore\SQLStore' )
		);

		$searchFieldContentRebuilder->setMessageReporter( $reporter );
		$searchFieldContentRebuilder->rebuild();
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 2.4
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\RebuildSearchFieldContent';
require_once ( RUN_MAINTENANCE_IF_MAIN );
