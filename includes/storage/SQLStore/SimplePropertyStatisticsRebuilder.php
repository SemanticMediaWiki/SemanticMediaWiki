<?php

namespace SMW\SQLStore;
use MWException;
use SMW\MessageReporter;
use SMW\Store\PropertyStatisticsStore;

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

	/**
	 * @since 1.9
	 *
	 * @var MessageReporter
	 */
	protected $reporter;

	/**
	 * Constructor.
	 *
	 * @since 1.9
	 *
	 * @param MessageReporter $reporter
	 */
	public function __construct( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * @see PropertyStatisticsRebuilder::rebuild
	 *
	 * @since 1.9
	 *
	 * @param PropertyStatisticsStore $propStatsStore
	 * @param \DatabaseBase $dbw
	 */
	public function rebuild( PropertyStatisticsStore $propStatsStore, \DatabaseBase $dbw ) {
		$this->reporter->reportMessage( "Updating property statistics. This may take a while.\n" );

		$propStatsStore->deleteAll();

		$res = $dbw->select(
			\SMWSql3SmwIds::tableName,
			array( 'smw_id', 'smw_title' ),
			array( 'smw_namespace' => SMW_NS_PROPERTY  ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$this->reporter->reportMessage( '.' );

			$usageCount = 0;
			foreach ( \SMWSQLStore3::getPropertyTables() as $propertyTable ) {

				if ( $propertyTable->isFixedPropertyTable() && $propertyTable->getFixedProperty() !== $row->smw_title ) {
					// This table cannot store values for this property
					continue;
				}

				$propRow = $dbw->selectRow(
					$propertyTable->getName(),
					'Count(*) as count',
					$propertyTable->isFixedPropertyTable() ? array() : array( 'p_id' => $row->smw_id ),
					__METHOD__
				);

				$usageCount += $propRow->count;
			}

			$propStatsStore->insertUsageCount( (int)$row->smw_id, $usageCount );
		}

		$propCount = $res->numRows();
		$dbw->freeResult( $res );
		$this->reporter->reportMessage( "\nUpdated statistics for $propCount Properties.\n" );
	}

}
