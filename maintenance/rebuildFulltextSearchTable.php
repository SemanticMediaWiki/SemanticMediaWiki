<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\ApplicationFactory;
use SMWDataItem as DataItem;
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
 * @since 2.5
 *
 * @author mwjames
 */
class rebuildFulltextSearchTable extends \Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Rebuild the fulltext search index (only works with SQLStore)';
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
		$this->addOption( 'optimize', 'Run possible table optimization (support depends on the SQL back-end) ', false );
		$this->addOption( 'v', 'Show additional (verbose) information about the progress', false );
		$this->addOption( 'quick', 'Suppress abort operation', false );
	}

	/**
	 * @since 3.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 2.5
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

		$cliMsgFormatter = new CliMsgFormatter();

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new CallbackMessageReporter( [ $this, 'reportMessage' ] );
		}

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"The script rebuilds the search index from property tables that",
			"support a fulltext search. Any change of the index rules (altered",
			"stopwords, new stemmer etc.) and/or a newly added or altered table",
			"requires to run this script again to ensure that the index complies",
			"with the rules set forth by the SQL back-end or Sanitizer."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		// Only the SQLStore is supported
		$searchTableRebuilder = $fulltextSearchTableFactory->newSearchTableRebuilder(
			$applicationFactory->getStore( '\SMW\SQLStore\SQLStore' )
		);

		$textSanitizer = $fulltextSearchTableFactory->newTextSanitizer();

		$searchTableRebuilder->reportVerbose(
			$this->hasOption( 'v' )
		);

		$searchTableRebuilder->requestOptimization(
			$this->hasOption( 'optimize' )
		);

		if ( !$searchTableRebuilder->canRebuild() ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->section( 'Notice' )
			);

			return $this->messageReporter->reportMessage( "\n" . "Full-text search indexing is not enabled or supported." ."\n" );
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Setting(s)' )
		);

		$this->reportConfiguration(
			$searchTableRebuilder,
			$textSanitizer
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Rebuild', 3 , '-', true )
		);

		$text = [
			"The entire index table is going to be purged first and it may",
			"take a moment before the rebuild is completed due to varying",
			"table contents."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		if ( !$this->hasOption( 'quick' ) ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->countDown( 'Abort the rebuild with CTRL-C in ...', 5 )
			);
		}

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		$searchTableRebuilder->setMessageReporter( $this->messageReporter );
		$result = $searchTableRebuilder->rebuild();

		if ( $this->hasOption( 'report-runtime' ) ) {
			$this->messageReporter->reportMessage( $cliMsgFormatter->section( 'Runtime report' ) );

			$this->messageReporter->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues()
			);
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildFulltextSearchTableLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];

			$maintenanceLogger->logFromArray( $log );
		}

		$maintenanceHelper->reset();
		return $result;
	}

	private function reportConfiguration( $searchTableRebuilder, $textSanitizer ) {

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage( "\n" );

		foreach ( $textSanitizer->getVersions() as $key => $value ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( "- $key", $value )
			);
		}

		$searchTable = $searchTableRebuilder->getSearchTable();
		$indexableDataTypes = [];

		$dataTypes = [
			DataItem::TYPE_BLOB => 'BLOB',
			DataItem::TYPE_URI  => 'URI',
			DataItem::TYPE_WIKIPAGE => 'WIKIPAGE'
		];

		foreach ( $dataTypes as $key => $value ) {
			if ( $searchTable->isValidByType( $key ) ) {
				$indexableDataTypes[] = $value;
			}
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "- DataTypes (indexable)", implode( ', ', $indexableDataTypes ) )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Exempted propertie(s)', 3, '-', true )
		);

		$text = [
			implode( ', ', $searchTable->getPropertyExemptionList() )
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);
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

$maintClass = rebuildFulltextSearchTable::class;
require_once ( RUN_MAINTENANCE_IF_MAIN );
