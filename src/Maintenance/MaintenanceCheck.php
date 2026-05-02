<?php

namespace SMW\Maintenance;

use SMW\Setup;
use SMW\Utils\CliMsgFormatter;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class MaintenanceCheck {

	private string $message = '';

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function canExecute(): bool {
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

		// `smwgIgnoreUpgradeKeyCheck` is the documented escape hatch for
		// running maintenance scripts when the schema is in an intermediate
		// state — notably when an upgrade was blocked by data that requires
		// a maintenance script (e.g. `populateHashField.php`) to fix first.
		// Read directly from `$GLOBALS` because this runs before `Setup::init`
		// has populated the Settings registry.
		if ( !( $GLOBALS['smwgIgnoreUpgradeKeyCheck'] ?? false ) && !$this->isSchemaValid() ) {
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
	 * Indirection to allow tests to assert behavior independent of
	 * `SetupFile::isGoodSchema`'s test-environment short-circuit.
	 */
	protected function isSchemaValid(): bool {
		return Setup::isValid( true );
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getMessage(): string {
		return $this->message;
	}

}
