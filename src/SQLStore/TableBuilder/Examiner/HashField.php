<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;
use SMW\Maintenance\PopulateHashField;

/**
 * @license GNU GPL v2+
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
	public function __construct( SQLStore $store, PopulateHashField $populateHashField = null ) {
		$this->store = $store;
		$this->populateHashField = $populateHashField;
	}

	/**
	 * @since 3.1
	 *
	 * @return integer
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
			$this->messageReporter->reportMessage( "   ... missing $count rows ...\n"  );
			$this->messageReporter->reportMessage( "   ... skipping the `smw_hash` field population ...\n"  );

			$this->populateHashField->setComplete( false );
		} elseif ( $count != 0 ) {
			$this->populateHashField->populate( $rows );
		} else {
			$this->populateHashField->setComplete( true );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
