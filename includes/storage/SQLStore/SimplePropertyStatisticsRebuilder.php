<?php

namespace SMW\SQLStore;

use SMW\Store\PropertyStatisticsStore;
use SMW\Reporter\MessageReporter;
use SMW\Reporter\NullMessageReporter;

use SMW\Store;

use MWException;

/**
 * Simple implementation of PropertyStatisticsRebuilder.
 *
 * @since 1.9
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nischay Nahata
 */
class SimplePropertyStatisticsRebuilder implements \SMW\Store\PropertyStatisticsRebuilder {

	/** @var Store */
	protected $store = null;

	/**
	 * @since 1.9
	 *
	 * @var MessageReporter
	 */
	protected $reporter;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param MessageReporter|null $reporter
	 */
	public function __construct( Store $store, MessageReporter $reporter = null ) {
		$this->store = $store;
		$this->reporter = $reporter;

		if ( $this->reporter === null ) {
			$this->reporter = new NullMessageReporter();
		}
	}

	/**
	 * @see PropertyStatisticsRebuilder::rebuild
	 *
	 * @since 1.9
	 *
	 * @param PropertyStatisticsStore $propStatsStore
	 */
	public function rebuild( PropertyStatisticsStore $propStatsStore ) {
		$this->reportMessage( "Updating property statistics. This may take a while.\n" );

		$propStatsStore->deleteAll();

		$res = $this->store->getConnection( 'mw.db' )->select(
			\SMWSql3SmwIds::tableName,
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

			$propStatsStore->insertUsageCount( (int)$row->smw_id, $usageCount );
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
