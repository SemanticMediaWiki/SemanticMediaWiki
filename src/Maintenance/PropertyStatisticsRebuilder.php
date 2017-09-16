<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Store;
use SMW\Store\PropertyStatisticsStore;

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
		$this->reportMessage( "\nProperty statistics update\n" );


		$this->reportMessage( "\nDeleting table content ..." );
		$this->propertyStatisticsStore->deleteAll();
		$this->reportMessage( "\n   ... done.\n" );

		$connection = $this->store->getConnection( 'mw.db' );

		$this->reportMessage( "\nSelecting properties ..." );

		$res = $connection->select(
			\SMWSql3SmwIds::TABLE_NAME,
			array( 'smw_id', 'smw_title' ),
			array(
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_subobject' => ''
			),
			__METHOD__
		);

		$propCount = $res->numRows();
		$this->reportMessage( "\n   ... $propCount properties selected ..." );
		$this->reportMessage( "\n   ... done.\n" );

		$this->reportMessage( "\nProcessing (this may take a while) ...\n" );
		$i = 0;

		foreach ( $res as $row ) {
			$this->reportMessage( $this->progress( $propCount, $i++ ) );

			$this->propertyStatisticsStore->insertUsageCount(
				(int)$row->smw_id,
				$this->getCountFormRow( $row )
			);
		}

		$connection->freeResult( $res );

		$this->reportMessage( "\n\nCompleted.\n" );
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

	private function progress( $propCount, $i ) {

		if ( $i % 60 === 0 ) {
			if ( $i < 1 ) {
				return "\n";
			}

			return ' ' . round( ( $i / $propCount ) * 100 ) . ' %' . "\n";
		}

		return '.';
	}

	protected function reportMessage( $message ) {
		$this->messageReporter->reportMessage( $message );
	}

}
