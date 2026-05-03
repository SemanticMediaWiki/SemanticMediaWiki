<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\DataItems\Property;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\MediaWiki\Collator;
use SMW\SQLStore\SQLStore;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedProperties {

	use MessageReporterAwareTrait;

	/**
	 * @var array
	 */
	private array $predefinedPropertyList = [];

	/**
	 * @since 3.1
	 */
	public function __construct( private SQLStore $store ) {
	}

	/**
	 * @since 3.1
	 *
	 * @param array $predefinedPropertyList
	 */
	public function setPredefinedPropertyList( array $predefinedPropertyList ): void {
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
	public function check( array $opts = [] ): void {
		// now write actual properties; do that each time, it is cheap enough
		// and we can update sortkeys by current language
		$this->messageReporter->reportMessage( "Checking predefined properties ...\n" );
		$this->messageReporter->reportMessage( "   ... initialize predefined properties ...\n" );

		foreach ( $this->predefinedPropertyList as $prop => $id ) {

			try {
				$property = new Property( $prop );
			} catch ( PredefinedPropertyLabelMismatchException ) {
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

	private function doUpdate( Property $property, $id ): void {
		$connection = $this->store->getConnection( DB_PRIMARY );

		// Try to find the ID for a non-fixed predefined property
		if ( $id === null ) {
			$row = $connection->newSelectQueryBuilder()
				->select( [ 'smw_id' ] )
				->from( SQLStore::ID_TABLE )
				->where( [
					'smw_title' => $property->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_subobject' => ''
				] )
				->caller( __METHOD__ )
				->fetchRow();

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

		$row = $connection->newSelectQueryBuilder()
			->select( [
				'smw_proptable_hash',
				'smw_hash',
				'smw_rev',
				'smw_touched'
			] )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_id' => $id ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row === false ) {
			$row = (object)[
				'smw_proptable_hash' => null,
				'smw_hash' => $property->getSha1(),
				'smw_rev' => null,
				'smw_touched' => $connection->timestamp( '1970-01-01 00:00:00' )
			];
		}

		$connection->newReplaceQueryBuilder()
			->replaceInto( SQLStore::ID_TABLE )
			->uniqueIndexFields( [ 'smw_id' ] )
			->row( [
				'smw_id' => $id,
				'smw_title' => $property->getKey(),
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_iw' => $iw,
				'smw_subobject' => '',
				'smw_sortkey' => $label,
				'smw_sort' => Collator::singleton()->getSortKey( $label ),
				'smw_proptable_hash' => $row->smw_proptable_hash,
				'smw_hash' => $row->smw_hash,
				'smw_rev' => $row->smw_rev,
				'smw_touched' => $row->smw_touched
			] )
			->caller( __METHOD__ )
			->execute();

		if ( $id === null ) {
			return;
		}

		$row = $connection->newSelectQueryBuilder()
			->select( [ 'p_id' ] )
			->from( SQLStore::PROPERTY_STATISTICS_TABLE )
			->where( [ 'p_id' => $id ] )
			->caller( __METHOD__ )
			->fetchRow();

		// Entry is available therefore don't try to override the count
		// value
		if ( $row !== false ) {
			return;
		}

		$connection->newInsertQueryBuilder()
			->insertInto( SQLStore::PROPERTY_STATISTICS_TABLE )
			->row( [
				'p_id' => $id,
				'usage_count' => 0,
				'null_count' => 0
			] )
			->caller( __METHOD__ )
			->execute();
	}

}
