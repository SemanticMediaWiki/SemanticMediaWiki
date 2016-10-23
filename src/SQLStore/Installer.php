<?php

namespace SMW\SQLStore;

use SMW\CompatibilityMode;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 * @author mwjames
 */
class Installer implements MessageReporter {

	/**
	 * @var TableSchemaManager
	 */
	private $tableSchemaManager;

	/**
	 * @since 2.5
	 *
	 * @param TableSchemaManager $tableSchemaManager
	 */
	public function __construct( TableSchemaManager $tableSchemaManager ) {
		$this->tableSchemaManager = $tableSchemaManager;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $verbose
	 */
	public function install( $verbose = true ) {

		// If for some reason the enableSemantics was not yet enabled
		// still allow to run the tables create in order for the
		// setup to be completed
		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::enableTemporaryCliUpdateMode();
		}

		$this->tableSchemaManager->setMessageReporter( $this->newMessageReporter( $verbose ) );
		$this->tableSchemaManager->create();

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $verbose
	 */
	public function uninstall( $verbose = true ) {

		$this->tableSchemaManager->setMessageReporter( $this->newMessageReporter( $verbose ) );
		$this->tableSchemaManager->drop();

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		// ob_get_level be sure to have some buffer, otherwise some PHPs complain
		if ( ob_get_level() == 0 ) {
			ob_start();
		}

		print $message;
		ob_flush();
		flush();
	}

	private function newMessageReporter( $verbose = true ) {

		if ( !$verbose ) {
			return MessageReporterFactory::getInstance()->newNullMessageReporter();
		}

		$messageReporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$messageReporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		return $messageReporter;
	}

}
