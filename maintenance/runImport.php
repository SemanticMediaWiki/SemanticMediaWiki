<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\CallbackMessageReporter;
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
 * @since 3.2
 *
 * @author mwjames
 */
class RunImport extends \Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.2
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Populate and import selected auto-discovered content." );
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
	 * @since 3.2
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

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();
		$importerServiceFactory = $applicationFactory->create( 'ImporterServiceFactory' );

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new CallbackMessageReporter( [ $this, 'reportMessage' ] );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script runs an import of auto-discovered content from directories",
			"listed in `smwgImportFileDirs`."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Import tasks' ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->oneCol( 'Running auto-discovery from ...' )
		);

		foreach ( $settings->get( 'smwgImportFileDirs' ) as $key => $dir ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( '... ' . str_replace( [ '\\', '//', '/' ], DIRECTORY_SEPARATOR, $dir ), "($key)", 3 )
			);
		}

		$contentIterator = $importerServiceFactory->newJsonContentIterator(
			$settings->get( 'smwgImportFileDirs' )
		);

		$importer = $importerServiceFactory->newImporter(
			$contentIterator
		);

		$importer->setReqVersion(
			$settings->get( 'smwgImportReqVersion' )
		);

		$importer->setMessageReporter( $this->messageReporter );
		$importer->runImport();

		return true;
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

$maintClass = RunImport::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
