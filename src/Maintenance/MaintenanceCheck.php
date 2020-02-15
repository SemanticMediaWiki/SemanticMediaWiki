<?php

namespace SMW\Maintenance;

use SMW\Setup;
use SMW\Utils\CliMsgFormatter;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class MaintenanceCheck {

	/**
	 * @var string
	 */
	private $message = '';

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function canExecute() : bool {

		$cliMsgFormatter = new CliMsgFormatter();
		$this->message = '';

		if ( !Setup::isEnabled() ) {
			$text = [
				"\x1b[91mYou need to have Semantic MediaWiki enabled in order",
				"to run the maintenance script!\x1b[0m"
			];

			$this->message .= "\n" . $cliMsgFormatter->head();
			$this->message .= $cliMsgFormatter->section( 'Extension notice', 3 );
			$this->message .= "\n" . $cliMsgFormatter->wordwrap( $text ) . "\n";
		}

		if ( !Setup::isValid( true ) ) {
			$text = [
				"It seems that the setup of Semantic MediaWiki wasn't finalized or a",
				"new upgrade key is required.\n\n",
				"\x1b[91mYou need to run `update.php` or `setupStore.php` first before you can",
				"continue with this maintenance script!\x1b[0m"
			];

			$this->message .= "\n" . $cliMsgFormatter->head();
			$this->message .= $cliMsgFormatter->section( 'Compatibility notice', 3 );
			$this->message .= "\n" . $cliMsgFormatter->wordwrap( $text ) . "\n";
		}

		return $this->message === '';
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getMessage() : string {
		return $this->message;
	}

}
