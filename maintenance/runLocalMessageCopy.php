<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\CallbackMessageReporter;
use SMW\ApplicationFactory;
use SMW\Setup;
use SMW\Utils\CliMsgFormatter;
use SMW\Maintenance\MaintenanceCheck;
use SMW\Localizer\CopyLocalMessages;

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
class runLocalMessageCopy extends \Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var CopyLocalMessages
	 */
	private $copyLocalMessages;

	/**
	 * @since 3.2
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Run copy of local message from and to the i18n translation system." );
		$this->addOption( 'file', 'Defines the local message file', false );
		$this->addOption( 'copy-canonicalmessages', 'Copies local messages to the canonical message (en.json) file.', false );
		$this->addOption( 'copy-translatedmessages', 'Copies translated message to the local message file.', false );
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

		if ( $this->messageReporter !== null ) {
			return $this->messageReporter->reportMessage( $message );
		}

		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( ( $maintenanceCheck = new MaintenanceCheck() )->canExecute() === false ) {
			exit ( $maintenanceCheck->getMessage() );
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$cliMsgFormatter = new CliMsgFormatter();

		$localMessageProvider = $maintenanceFactory->newLocalMessageProvider(
			'/local/maintenance.i18n.json'
		);

		$localMessageProvider->loadMessages();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$file = $this->hasOption( 'file' ) ? $this->getOption( 'file' ) : '';

		$text = [
			$localMessageProvider->msg( [ 'smw-maintenance-runlocalmessagecopy-about', $file ] ),
			"\n\n",
			$cliMsgFormatter->yellow(
				$localMessageProvider->msg( 'smw-maintenance-runlocalmessagecopy-usage-notice' )
			)
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Copy' ) . "\n"
		);

		if ( $file === '' ) {
			return $this->reportMessage( "No file defined!\n" );
		}

		$this->copyLocalMessages = new CopyLocalMessages(
			'/local/' . $this->getOption( 'file' )
		);

		$this->reportMessage( "Reading files ...\n" );

		if ( $this->hasOption( 'copy-canonicalmessages' ) ) {
			$this->copyCanonicalMessages();
		}

		if ( $this->hasOption( 'copy-translatedmessages' ) ) {
			$this->copyTranslatedMessages();
		}

		$this->reportMessage( "   ... done.\n" );
	}

	private function copyCanonicalMessages() {

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... copy `$file` messages to canonical `en.json` ...", 3 )
		);

		$res = $this->copyLocalMessages->copyCanonicalMessages();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... copied messages ...", $res['messages_count'], 3 )
		);
	}

	private function copyTranslatedMessages() {

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... copy i18n messages to the `$file`", 3 )
		);

		$res = $this->copyLocalMessages->copyTranslatedMessages();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... files read ...", $res['files_count'], 3 )
		);

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... copied messages ...", $res['messages_count'], 3 )
		);
	}

}

$maintClass = runLocalMessageCopy::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
