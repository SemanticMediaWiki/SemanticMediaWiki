<?php

namespace SMW\Maintenance;

use SMW\Store\PropertyStatisticsStore;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Store;

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
	private $reporter;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 */
	public function __construct( Store $store, PropertyStatisticsStore $propertyStatisticsStore ) {
		$this->store = $store;
		$this->propertyStatisticsStore = $propertyStatisticsStore;
		$this->reporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since  2.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->reporter = $messageReporter;
	}

	/**
	 * @since 1.9
	 */
	public function rebuild() {
		$this->reportMessage( "Updating property statistics. This may take a while.\n" );

		$this->propertyStatisticsStore->deleteAll();

		$res = $this->store->getConnection( 'mw.db' )->select(
			\SMWSql3SmwIds::TABLE_NAME,
			array( 'smw_id', 'smw_title' ),
			array( 'smw_namespace' => SMW_NS_PROPERTY  ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$this->reportMessage( '.' );

			$usageCount = 0;
			foreach ( $this->store->getPropertyTables() as $propertyTable ) {

				if ( $propertyTable->isFixedPropertyTable() && $propertyTable->getFixedProperty() !== $row->smw_title ) {
					// This table cannot store values for this property
					continue;
				}

				$usageCount += $this->getPropertyTableRowCount( $propertyTable, $row->smw_id );
			}

			$this->propertyStatisticsStore->insertUsageCount( (int)$row->smw_id, $usageCount );
		}

		$propCount = $res->numRows();
		$this->store->getConnection( 'mw.db' )->freeResult( $res );
		$this->reportMessage( "\nUpdated statistics for $propCount Properties.\n" );
	}

	protected function getPropertyTableRowCount( $propertyTable, $id ) {

		$condition = $propertyTable->isFixedPropertyTable() ? array() : array( 'p_id' => $id );

		$row = $this->store->getConnection( 'mw.db' )->selectRow(
			$propertyTable->getName(),
			'Count(*) as count',
			$condition,
			__METHOD__
		);

		return $row->count;
	}

	protected function reportMessage( $message ) {
		$this->reporter->reportMessage( $message );
	}

}
