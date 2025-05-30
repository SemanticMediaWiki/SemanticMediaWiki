<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Maintenance\populateHashField;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashField {

	use MessageReporterAwareTrait;

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var PopulateHashField
	 */
	private $populateHashField;

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 * @param PopulateHashField|null $populateHashField
	 */
	public function __construct( SQLStore $store, ?PopulateHashField $populateHashField = null ) {
		$this->store = $store;
		$this->populateHashField = $populateHashField;
	}

	/**
	 * @since 3.1
	 *
	 * @return int
	 */
	public static function threshold() {
		return PopulateHashField::COUNT_SCRIPT_EXECUTION_THRESHOLD;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $opts
	 */
	public function check( array $opts = [] ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage( "Checking smw_hash field consistency ...\n" );
		require_once $GLOBALS['smwgMaintenanceDir'] . "/populateHashField.php";

		if ( $this->populateHashField === null ) {
			$this->populateHashField = new PopulateHashField();
		}

		$this->populateHashField->setStore( $this->store );
		$this->populateHashField->setMessageReporter( $this->messageReporter );

		$rows = $this->populateHashField->fetchRows();
		$count = 0;

		if ( $rows !== null ) {
			$count = $rows->numRows();
		}

		if ( $count > self::threshold() ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( "... found missing rows ...", "(rows) $count", 3 )
			);

			$this->messageReporter->reportMessage( "   ... skipping the `smw_hash` field population ...\n" );

			$this->populateHashField->setComplete( false );
		} elseif ( $count != 0 ) {
			$this->populateHashField->populate( $rows );
		} else {
			$this->populateHashField->setComplete( true );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
