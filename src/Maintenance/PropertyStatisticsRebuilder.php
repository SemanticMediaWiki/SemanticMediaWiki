<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;

/**
 * Simple class for rebuilding property usage statistics.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nischay Nahata
 */
class PropertyStatisticsRebuilder {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var PropertyStatisticsStore
	 */
	private $propertyStatisticsStore;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 */
	public function __construct( Store $store, PropertyStatisticsStore $propertyStatisticsStore ) {
		$this->store = $store;
		$this->propertyStatisticsStore = $propertyStatisticsStore;
		$this->messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since  2.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 1.9
	 */
	public function rebuild() {

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"Rebuilding property usage statistics by reading all registered tables",
			"and accumulate property data from stored records.",
			"\n\n",
			"Depending on the size and amount of referenced properties in registered",
			"tables, the rebuild may take a moment."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"

		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Statistics rebuild' )
		);

		$table = SQLStore::PROPERTY_STATISTICS_TABLE;

		$this->reportMessage( "\nRebulding the `$table` statistics table ...\n" );

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... deleting all rows ...", 3 )
		);

		$this->propertyStatisticsStore->deleteAll();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$connection = $this->store->getConnection( 'mw.db' );

		$res = $connection->select(
			SQLStore::ID_TABLE,
			[ 'smw_id', 'smw_title' ],
			[
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_subobject' => ''
			],
			__METHOD__
		);

		$propCount = $res->numRows();

		$this->reportMessage(
			$cliMsgFormatter->twoCols( '... properties count:', "$propCount (rows)", 3 )
		);

		$i = 0;

		foreach ( $res as $row ) {

			$i++;

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride( '... updating ...', $cliMsgFormatter->progressCompact( $i, $propCount ), 3 )
			);

			$this->propertyStatisticsStore->insertUsageCount(
				(int)$row->smw_id,
				$this->getCountFormRow( $row )
			);
		}

		$connection->freeResult( $res );

		$this->reportMessage( "\n   ... done.\n" );
	}

	private function getCountFormRow( $row ) {

		$usageCount = 0;
		$nullCount = 0;

		foreach ( $this->store->getPropertyTables() as $propertyTable ) {

			if ( $propertyTable->isFixedPropertyTable() && $propertyTable->getFixedProperty() !== $row->smw_title ) {
				// This table cannot store values for this property
				continue;
			}

			list( $uCount, $nCount ) = $this->getPropertyTableRowCount(
				$propertyTable,
				$row->smw_id
			);

			$usageCount += $uCount;
			$nullCount += $nCount;
		}

		return [ $usageCount, $nullCount ];
	}

	private function getPropertyTableRowCount( $propertyTable, $pid ) {

		$condition = [];
		$connection = $this->store->getConnection( 'mw.db' );

		if ( !$propertyTable->isFixedPropertyTable() ) {
			$condition = [ 'p_id' => $pid ];
		}

		$tableFields = $this->store->getDataItemHandlerForDIType( $propertyTable->getDiType() )->getTableFields();
		$tableName = $propertyTable->getName();

		$usageCount = 0;
		$nullCount = 0;

		// Select all (incl. NULL since for example blob table can have a null
		// for when only the hash field is used, substract NULL in a second step)
		$row = $connection->selectRow(
			$tableName,
			'Count(*) as count',
			$condition,
			__METHOD__
		);

		if ( $row !== false ) {
			$usageCount = $row->count;
		}

		// Select only those that match NULL for all fields
		foreach ( $tableFields as $field => $type ) {
			$condition[] = "$field IS NULL";
		}

		$nRow = $connection->selectRow(
			$tableName,
			'Count(*) as count',
			$condition,
			__METHOD__
		);

		if ( $nRow !== false ) {
			$nullCount = $nRow->count;
		}

		if ( $usageCount > 0 ) {
			$usageCount = $usageCount - $nullCount;
		}

		return [ $usageCount, $nullCount ];
	}

	protected function reportMessage( $message ) {
		$this->messageReporter->reportMessage( $message );
	}

}
