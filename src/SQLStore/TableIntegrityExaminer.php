<?php

namespace SMW\SQLStore;

use Onoi\MessageReporter\NullMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use SMWDataItem as DataItem;
use SMW\DIProperty;
use SMWSql3SmwIds;

/**
 * @private
 *
 *
 * Allows to execute SQLStore or table specific tasks that are expected to be part
 * of the installation or removal routine.
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
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->messageReporter = new NullMessageReporter();
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
	 * @param TableBuilder $tableBuilder
	 */
	public function checkOnPostCreation( TableBuilder $tableBuilder ) {

		$this->doCheckInternalPropertyIndices(
			$this->store->getConnection( DB_MASTER )
		);

		$tableBuilder->checkOn( TableBuilder::POST_CREATION );
	}

	/**
	 * Create some initial DB entries for important built-in properties. Having the DB contents predefined
	 * allows us to safe DB calls when certain data is needed. At the same time, the entries in the DB
	 * make sure that DB-based functions work as with all other properties.
	 */
	private function doCheckInternalPropertyIndices( $connection ) {

		$this->messageReporter->reportMessage( "\nSetting up internal property indices ...\n" );
		$this->doCheckPredefinedPropertyBorder( $connection );

		// now write actual properties; do that each time, it is cheap enough and we can update sortkeys by current language
		$this->messageReporter->reportMessage( "   ... writing entries for internal properties ...\n" );

		foreach ( SMWSql3SmwIds::$special_ids as $prop => $id ) {
			$property = new DIProperty( $prop );
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
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function doCheckPredefinedPropertyBorder( $connection ) {

		// Check if we already have this structure
		$expectedID = SQLStore::FIXED_PROPERTY_ID_UPPERBOUND;

		$currentID = $connection->selectRow(
			SQLStore::ID_TABLE,
			'smw_id',
			'smw_iw=' . $connection->addQuotes( SMW_SQL3_SMWBORDERIW )
		);

		if ( $currentID !== false && $currentID->smw_id == $expectedID ) {
			return $this->messageReporter->reportMessage( "   ... space for internal properties already allocated.\n" );
		}

		// Legacy bound
		$currentID = $currentID === false ? 50 : $currentID->smw_id;

		$this->messageReporter->reportMessage( "   ... allocating space for internal properties ...\n" );
		$this->store->getObjectIds()->moveSMWPageID( $expectedID );

		$connection->insert(
			SQLStore::ID_TABLE,
			array(
				'smw_id' => $expectedID,
				'smw_title' => '',
				'smw_namespace' => 0,
				'smw_iw' => SMW_SQL3_SMWBORDERIW,
				'smw_subobject' => '',
				'smw_sortkey' => ''
			),
			__METHOD__
		);

		$this->messageReporter->reportMessage( "   ... moving from $currentID to $expectedID " );

		// make way for built-in ids
		for ( $i = $currentID; $i < $expectedID; $i++ ) {
			$this->store->getObjectIds()->moveSMWPageID( $i );
			$this->messageReporter->reportMessage( '.' );
		}

		$this->messageReporter->reportMessage( "\n   ... done.\n" );
	}

}
