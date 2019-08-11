<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\SQLStore\SQLStore;
use SMW\DIProperty;
use SMW\MediaWiki\Collator;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedProperties {

	use MessageReporterAwareTrait;

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var []
	 */
	private $predefinedPropertyList = [];

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $predefinedPropertyList
	 */
	public function setPredefinedPropertyList( array $predefinedPropertyList ) {
		$this->predefinedPropertyList = $predefinedPropertyList;
	}

	/**
	 * Create some initial DB entries for important built-in properties. Having
	 * the DB contents predefined allows us to safe DB calls when certain data
	 * is needed. At the same time, the entries in the DB make sure that DB-based
	 * functions work as with all other properties.
	 *
	 * @since 3.1
	 *
	 * @param array $opts
	 */
	public function check( array $opts = [] ) {

		// now write actual properties; do that each time, it is cheap enough
		// and we can update sortkeys by current language
		$this->messageReporter->reportMessage( "Checking predefined properties ...\n" );
		$this->messageReporter->reportMessage( "   ... initialize predefined properties ...\n" );

		foreach ( $this->predefinedPropertyList as $prop => $id ) {

			try{
				$property = new DIProperty( $prop );
			} catch ( PredefinedPropertyLabelMismatchException $e ) {
				$property = null;
				$this->messageReporter->reportMessage( "   ... skipping {$prop} due to invalid registration ...\n" );
			}

			if ( $property === null ) {
				continue;
			}

			$this->doUpdate( $property, $id );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function doUpdate( $property, $id ) {

		$connection = $this->store->getConnection( DB_MASTER );

		// Try to find the ID for a non-fixed predefined property
		if ( $id === null ) {
			$row = $connection->selectRow(
				SQLStore::ID_TABLE,
				[
					'smw_id'
				],
				[
					'smw_title' => $property->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_subobject' => ''
				],
				__METHOD__
			);

			if ( $row !== false ) {
				$id = $row->smw_id;
			}
		}

		if ( $id === null ) {
			return;
		}

		$label = $property->getCanonicalLabel();

		$iw = $this->store->getObjectIds()->getPropertyInterwiki(
			$property
		);

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			[
				'smw_proptable_hash',
				'smw_hash',
				'smw_rev',
				'smw_touched'
			],
			[
				'smw_id' => $id
			],
			__METHOD__
		);

		if ( $row === false ) {
			$row = (object)[
				'smw_proptable_hash' => null,
				'smw_hash' => null,
				'smw_rev' => null,
				'smw_touched' => $connection->timestamp( '1970-01-01 00:00:00' )
			];
		}

		$connection->replace(
			SQLStore::ID_TABLE,
			[ 'smw_id' ],
			[
				'smw_id' => $id,
				'smw_title' => $property->getKey(),
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_iw' =>  $iw,
				'smw_subobject' => '',
				'smw_sortkey' => $label,
				'smw_sort' => Collator::singleton()->getSortKey( $label ),
				'smw_proptable_hash' => $row->smw_proptable_hash,
				'smw_hash' => $row->smw_hash,
				'smw_rev' => $row->smw_rev,
				'smw_touched' => $row->smw_touched
			],
			__METHOD__
		);

		if ( $id === null ) {
			return;
		}

		$row = $connection->selectRow(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[ 'p_id' ],
			[ 'p_id' => $id ],
			__METHOD__
		);

		// Entry is available therefore don't try to override the count
		// value
		if ( $row !== false ) {
			return;
		}

		$connection->insert(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[
				'p_id' => $id,
				'usage_count' => 0,
				'null_count' => 0
			],
			__METHOD__
		);
	}

}
