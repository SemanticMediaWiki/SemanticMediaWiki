<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder;
use SMW\SetupFile;
use SMW\Maintenance\updateEntityCountMap as UpdateEntityCountMap;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CountMapField {

	use MessageReporterAwareTrait;

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @since 3.2
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param SetupFile $setupFile
	 */
	public function setSetupFile( SetupFile $setupFile ) {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $log
	 */
	public function check( array $log = [] ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( "Checking smw_countmap field consistency ..." )
		);

		$connection = $this->store->getConnection( DB_PRIMARY );
		$tableName = $connection->tableName( SQLStore::ID_AUXILIARY_TABLE );

		if (
			isset( $log[$tableName]['smw_countmap'] ) &&
			$log[$tableName]['smw_countmap'] === TableBuilder::PROC_FIELD_NEW ) {

			$this->messageReporter->reportMessage(
				"\n   ... adding incomplete task for `smw_countmap` conversion ...\n"
			);

			$this->setupFile->addIncompleteTask( UpdateEntityCountMap::COUNTMAP_INCOMPLETE );
		} else {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
