<?php

namespace SMW\SQLStore;

use Onoi\MessageReporter\NullMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use SMW\SQLStore\TableBuilder\Table;
use SMWDataItem as DataItem;
use SMW\DIProperty;
use SMWSql3SmwIds;
use SMW\Exception\PredefinedPropertyLabelMismatchException;

/**
 * @private
 *
 * Allows to execute SQLStore or table specific examination tasks that are
 * expected to be part of the installation or removal routine.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableIntegrityExaminer implements MessageReporterAware {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var array
	 */
	private $predefinedProperties = array();

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->messageReporter = new NullMessageReporter();
		$this->predefinedProperties = SMWSql3SmwIds::$special_ids;
	}

	/**
	 * @see MessageReporterAware::setMessageReporter
	 *
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $predefinedProperties
	 */
	public function setPredefinedProperties( array $predefinedProperties ) {
		$this->predefinedProperties = $predefinedProperties;
	}

	/**
	 * @since 2.5
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function checkOnPostCreation( TableBuilder $tableBuilder ) {

		$connection = $this->store->getConnection( DB_MASTER );

		$this->doCheckPredefinedPropertyIndices(
			$connection
		);

		// Call out for RDBMS specific implementations
		$tableBuilder->checkOn( TableBuilder::POST_CREATION );
	}

	/**
	 * @since 2.5
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function checkOnPostDestruction( TableBuilder $tableBuilder ) {

		$connection = $this->store->getConnection( DB_MASTER );

		// Find orphaned tables that have not been removed but were produced and
		// handled by SMW
		foreach ( $connection->listTables() as $table ) {
			if ( strpos( $table, TableBuilder::TABLE_PREFIX ) !== false ) {

				// Remove any MW specific prefix at this point which will be
				// handled by the DB class (abcsmw_foo -> smw_foo)
				$tableBuilder->drop( new Table( strstr( $table, TableBuilder::TABLE_PREFIX ) ) );
			}
		}

		// Call out for RDBMS specific implementations
		$tableBuilder->checkOn( TableBuilder::POST_DESTRUCTION );
	}

	/**
	 * Create some initial DB entries for important built-in properties. Having
	 * the DB contents predefined allows us to safe DB calls when certain data
	 * is needed. At the same time, the entries in the DB make sure that DB-based
	 * functions work as with all other properties.
	 */
	private function doCheckPredefinedPropertyIndices( $connection ) {

		$this->messageReporter->reportMessage( "Checking predefined properties ...\n" );
		$this->doCheckPredefinedPropertyBorder( $connection );

		// now write actual properties; do that each time, it is cheap enough
		// and we can update sortkeys by current language
		$this->messageReporter->reportMessage( "   ... writing properties ...\n" );

		foreach ( $this->predefinedProperties as $prop => $id ) {

			try{
				$property = new DIProperty( $prop );
			} catch ( PredefinedPropertyLabelMismatchException $e ) {
				$property = null;
				$this->messageReporter->reportMessage( "   ... skipping {$prop} due to invalid registration ...\n" );
			}

			if ( $property === null ) {
				continue;
			}

			$connection->replace(
				SQLStore::ID_TABLE,
				array( 'smw_id' ),
				array(
					'smw_id' => $id,
					'smw_title' => $property->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_iw' => $this->store->getObjectIds()->getPropertyInterwiki( $property ),
					'smw_subobject' => '',
					'smw_sortkey' => $property->getCanonicalLabel()
				),
				__METHOD__
			);

			$row = $connection->selectRow(
				SQLStore::PROPERTY_STATISTICS_TABLE,
				array(
					'p_id'
				),
				array( 'p_id' => $id ),
				__METHOD__
			);

			// Entry is available therefore don't try to override the count value
			if ( $row !== false ) {
				continue;
			}

			$connection->insert(
				SQLStore::PROPERTY_STATISTICS_TABLE,
				array(
					'p_id' => $id,
					'usage_count' => 0
				),
				__METHOD__
			);
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function doCheckPredefinedPropertyBorder( $connection ) {

		// Check if we already have this structure
		$expectedIdUpperbound = SQLStore::FIXED_PROPERTY_ID_UPPERBOUND;

		$currentIdUpperbound = $connection->selectRow(
			SQLStore::ID_TABLE,
			'smw_id',
			'smw_iw=' . $connection->addQuotes( SMW_SQL3_SMWBORDERIW )
		);

		if ( $currentIdUpperbound !== false && $currentIdUpperbound->smw_id == $expectedIdUpperbound ) {
			return $this->messageReporter->reportMessage( "   ... space for internal properties already allocated.\n" );
		}

		// Legacy bound
		$currentIdUpperbound = $currentIdUpperbound === false ? 50 : $currentIdUpperbound->smw_id;

		$this->messageReporter->reportMessage( "   ... allocating space for internal properties ...\n" );
		$this->store->getObjectIds()->moveSMWPageID( $expectedIdUpperbound );

		$connection->insert(
			SQLStore::ID_TABLE,
			array(
				'smw_id' => $expectedIdUpperbound,
				'smw_title' => '',
				'smw_namespace' => 0,
				'smw_iw' => SMW_SQL3_SMWBORDERIW,
				'smw_subobject' => '',
				'smw_sortkey' => ''
			),
			__METHOD__
		);

		if ( $currentIdUpperbound == $expectedIdUpperbound ) {
			return $this->messageReporter->reportMessage( "   ... done.\n" );
		}

		$this->messageReporter->reportMessage( "   ... moving from $currentIdUpperbound to $expectedIdUpperbound" );

		// make way for built-in ids
		for ( $i = $currentIdUpperbound; $i < $expectedIdUpperbound; $i++ ) {
			$this->store->getObjectIds()->moveSMWPageID( $i );
		}

		$this->messageReporter->reportMessage( "\n   ... done.\n" );
	}

}
