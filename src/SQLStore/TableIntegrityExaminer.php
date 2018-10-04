<?php

namespace SMW\SQLStore;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use Onoi\MessageReporter\NullMessageReporter;
use SMW\DIProperty;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\MediaWiki\Collator;
use SMW\PropertyRegistry;
use SMW\SQLStore\TableBuilder\Table;
use SMWSql3SmwIds;

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
	private $predefinedProperties = [];

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->messageReporter = new NullMessageReporter();
		$this->setPredefinedPropertyList( PropertyRegistry::getInstance()->getPropertyList() );
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
	 * @param array $propertyList
	 */
	public function setPredefinedPropertyList( array $propertyList ) {

		$fixedPropertyList = SMWSql3SmwIds::$special_ids;
		$predefinedPropertyList = [];

		foreach ( $propertyList as $key => $val ) {
			$predefinedPropertyList[$key] = null;

			if ( isset( $fixedPropertyList[$key] ) ) {
				$predefinedPropertyList[$key] = $fixedPropertyList[$key];
			} elseif ( is_integer( $val ) ) {
				$predefinedPropertyList[$key] = $val;
			}
		}

		$this->predefinedPropertyList = $predefinedPropertyList;
	}

	/**
	 * @since 2.5
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function checkOnPostCreation( TableBuilder $tableBuilder ) {

		$this->checkPredefinedPropertyIndices();
		$this->checkHashField();
		$this->checkSortField( $tableBuilder->getLog() );

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
	private function checkPredefinedPropertyIndices() {

		$connection = $this->store->getConnection( DB_MASTER );

		$this->messageReporter->reportMessage( "Checking predefined properties ...\n" );
		$this->checkPredefinedPropertyUpperbound();

		// now write actual properties; do that each time, it is cheap enough
		// and we can update sortkeys by current language
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

			$this->updatePredefinedProperty( $property, $id );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function checkPredefinedPropertyUpperbound() {

		$connection = $this->store->getConnection( DB_MASTER );

		// Check if we already have this structure
		$upperbound = SQLStore::FIXED_PROPERTY_ID_UPPERBOUND;
		$legacyBound = 50;

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			'smw_id',
			'smw_iw=' . $connection->addQuotes( SMW_SQL3_SMWBORDERIW )
		);

		if ( $row !== false && $row->smw_id == $upperbound ) {
			return $this->messageReporter->reportMessage( "   ... space for internal properties already allocated.\n" );
		} elseif ( $row === false ) {
			$currentUpperbound = $legacyBound;
		} else {
			$currentUpperbound = $row->smw_id;

			// Delete the current upperbound to avoid having a duplicate border
			$connection->delete(
				SQLStore::ID_TABLE,
				[ 'smw_id' => $currentUpperbound ],
				__METHOD__
			);
		}

		$this->messageReporter->reportMessage( "   ... allocating space for internal properties ...\n" );
		$this->store->getObjectIds()->moveSMWPageID( $upperbound );

		$connection->insert(
			SQLStore::ID_TABLE,
			[
				'smw_id' => $upperbound,
				'smw_title' => '',
				'smw_namespace' => 0,
				'smw_iw' => SMW_SQL3_SMWBORDERIW,
				'smw_subobject' => '',
				'smw_sortkey' => ''
			],
			__METHOD__
		);

		if ( $currentUpperbound == $upperbound ) {
			return $this->messageReporter->reportMessage( "   ... done.\n" );
		}

		if ( $currentUpperbound < $upperbound ) {
			$this->messageReporter->reportMessage( "   ... moving from $currentUpperbound to $upperbound upperbound (may take a moment) ..." );
			$this->messageReporter->reportMessage( "       " );
		}

		for ( $i = $currentUpperbound; $i < $upperbound; $i++ ) {

			if ( ( $i - $currentUpperbound ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			}

			$this->messageReporter->reportMessage( "." );
			$this->store->getObjectIds()->moveSMWPageID( $i );
		}

		$this->messageReporter->reportMessage( "\n   ... done.\n" );
	}

	private function checkSortField( $log ) {

		$connection = $this->store->getConnection( DB_MASTER );

		$tableName = $connection->tableName( SQLStore::ID_TABLE );
		$this->messageReporter->reportMessage( "Checking smw_sortkey, smw_sort fields ...\n" );

		// #2429, copy smw_sortkey content to the new smw_sort field once
		if ( isset( $log[$tableName]['smw_sort'] ) && $log[$tableName]['smw_sort'] === TableBuilder::PROC_FIELD_NEW ) {
			$emptyField = 'smw_sort';
			$copyField = 'smw_sortkey';

			$this->messageReporter->reportMessage( "   Table " . SQLStore::ID_TABLE . " ...\n" );
			$this->messageReporter->reportMessage( "   ... copying $copyField to $emptyField ... " );
			$connection->query( "UPDATE $tableName SET $emptyField = $copyField", __METHOD__ );
			$this->messageReporter->reportMessage( "done.\n" );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function updatePredefinedProperty( $property, $id ) {

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
				'smw_hash'
			],
			[
				'smw_id' => $id
			],
			__METHOD__
		);

		if ( $row === false ) {
			$row = (object)[ 'smw_proptable_hash' => null, 'smw_hash' => null ];
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
				'smw_hash' => $row->smw_hash
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

	private function checkHashField() {

		$this->messageReporter->reportMessage( "Checking smw_hash field ...\n" );
		$connection = $this->store->getConnection( DB_MASTER );

		$rows = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			[
				'smw_hash' => null
			],
			__METHOD__
		);

		$count = $rows !== null ? $rows->numRows() : 0;
		$i = 0;

		if ( $count == 0 ) {
			return $this->messageReporter->reportMessage( "   ... done.\n"  );
		}

		$this->messageReporter->reportMessage( "   ... missing $count rows ...\n"  );

		foreach ( $rows as $row ) {
			$i++;

			$hash = $this->store->getObjectIds()->computeSha1(
				[
					$row->smw_title,
					(int)$row->smw_namespace,
					$row->smw_iw,
					$row->smw_subobject
				]
			);

			$this->messageReporter->reportMessage(
				"\r". sprintf( "%-35s%s", "   ... updating document no.", sprintf( "%s (%1.0f%%)", $row->smw_id, round( ( $i / $count ) * 100 ) ) )
			);

			$connection->update(
				SQLStore::ID_TABLE,
				[ 'smw_hash' => $hash ],
				[ 'smw_id' => $row->smw_id ],
				__METHOD__
			);
		}

		$this->messageReporter->reportMessage( "\n   ... done.\n" );
	}

}
