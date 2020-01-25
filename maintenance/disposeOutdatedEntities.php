<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\CallbackMessageReporter;
use SMW\ApplicationFactory;
use SMW\Setup;
use SMW\Utils\CliMsgFormatter;
use SMW\Maintenance\DataRebuilder\OutdatedDisposer;
use Title;

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
class DisposeOutdatedEntities extends \Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.2
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Dispose of outdated entities." );
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
		$title = Title::newFromText( __METHOD__ );

		$outdatedDisposer = new OutdatedDisposer(
			$applicationFactory->newJobFactory()->newEntityIdDisposerJob( $title ),
			$applicationFactory->getIteratorFactory()
		);

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
			"This script will remove outdated entities and query link entries from",
			"tables that hold a reference to the ID marked as outdated",
			"in Semantic MediaWiki."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Outdated entitie(s)' ) . "\n"
		);

		$outdatedDisposer->setMessageReporter( $this->messageReporter );
		$outdatedDisposer->run();

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

$maintClass = DisposeOutdatedEntities::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
