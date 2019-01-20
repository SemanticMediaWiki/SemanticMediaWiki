<?php

namespace SMW\SQLStore;

use SMW\Store;
use SMW\ChangePropListener;
use SMW\Parameters;
use SMW\DIProperty;
use SMW\SQLStore\Exception\TableMissingIdFieldException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableUpdater {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertyStatisticsStore
	 */
	private $propertyStatisticsStore;

	/**
	 * @var array
	 */
	private $stats = [];

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 */
	public function __construct( Store $store, PropertyStatisticsStore $propertyStatisticsStore ) {
		$this->store = $store;
		$this->propertyStatisticsStore = $propertyStatisticsStore;
	}

	/**
	 * Update all property tables and any dependent data (hashes,
	 * statistics, etc.) by inserting/deleting the given values. The ID of
	 * the page that is updated, and the hashes of the properties must be
	 * given explicitly (the hashes could not be computed from the insert
	 * and delete data alone anyway).
	 *
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param Parameters $parameters
	 */
	public function update( $id, Parameters $parameters ) {

		$this->stats = [];

		$insert_rows = $parameters->get( 'insert_rows' );
		$delete_rows = $parameters->get( 'delete_rows' );

		$this->doUpdate( $insert_rows, $delete_rows );
		$new_hashes = $parameters->get( 'new_hashes' );

		// If only rows are marked for deletion then modify hashs to ensure that
		// any inbalance can be corrected by the next insert operation for which
		// the new_hashes are computed (seen in connection with redirects)
		if ( $insert_rows === [] && $delete_rows !== [] ) {
			foreach ( $new_hashes as $key => $hash ) {
				$new_hashes[$key] = $hash . '.d';
			}
		}

		if ( $insert_rows !== [] || $delete_rows !== [] ) {
			$this->store->getObjectIds()->setPropertyTableHashes( $id, $new_hashes );
		}

		$this->propertyStatisticsStore->addToUsageCounts(
			$this->stats
		);
	}

	/**
	 * It is assumed and required that the tables mentioned in
	 * $tablesInsertRows and $tablesDeleteRows are the same, and that all
	 * $rows in these datasets refer to the same subject ID.
	 *
	 * @param array $insert_rows
	 * @param array $delete_rows
	 */
	private function doUpdate( array $insert_rows, array $delete_rows ) {

		$propertyTables = $this->store->getPropertyTables();
		$ids = [];

		// Note: by construction, the inserts and deletes have the same table keys.
		// Note: by construction, the inserts and deletes are currently disjoint;
		// yet we delete first to make the method more robust/versatile.
		foreach ( $insert_rows as $tableName => $insertRows ) {

			$propertyTable = $propertyTables[$tableName];

			// Should not occur, but let's be strict
			if ( !$propertyTable->usesIdSubject() ) {
				throw new TableMissingIdFieldException( $propertyTable->getName() );
			}

			// Delete
			$this->update_rows( $propertyTable, $delete_rows[$tableName], false );

			// Insert
			$this->update_rows( $propertyTable, $insertRows, true );

			$this->aggregate_ids( $ids, $propertyTable, $insertRows );
			$this->aggregate_ids( $ids, $propertyTable, $delete_rows[$tableName] );
		}

		$this->update_touched( array_keys( $ids ) );
	}

	/**
	 * Update one property table by inserting or deleting rows, and compute
	 * the changes that this entails for the property usage counts. The
	 * given rows are inserted into the table if $insert is true; otherwise
	 * they are deleted. The property usage counts are recorded in the
	 * call-by-ref parameter $propertyUseIncrements.
	 *
	 * The method assumes that all of the given rows are about the same
	 * subject. This is ensured by callers.
	 *
	 * @param PropertyTableDefinition $propertyTable
	 * @param array $rows array of rows to insert/delete
	 * @param boolean $insert
	 */
	private function update_rows( PropertyTableDefinition $propertyTable, array $rows, $insert ) {

		if ( empty( $rows ) ) {
			return;
		}

		if ( $insert ) {
			$this->insert( $propertyTable, $rows );
		} else {
			$this->delete( $propertyTable, $rows );
		}

		if ( $propertyTable->isFixedPropertyTable() ) {

			$property = new DIProperty(
				$propertyTable->getFixedProperty()
			);

			$pid = $this->store->getObjectIds()->makeSMWPropertyID( $property );
		}

		foreach ( $rows as $row ) {

			if ( !$propertyTable->isFixedPropertyTable() ) {
				$pid = $row['p_id'];
			}

			ChangePropListener::record(
				$pid,
				[
					'row' => $row,
					'is_insert' => $insert
				]
			);

			if ( !array_key_exists( $pid, $this->stats ) ) {
				$this->stats[$pid] = 0;
			}

			$this->stats[$pid] += ( $insert ? 1 : -1 );
		}
	}

	private function insert( PropertyTableDefinition $propertyTable, array $rows ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$tableName = $propertyTable->getName();

		$connection->insert(
			$tableName,
			$rows,
			__METHOD__ . "-$tableName"
		);
	}

	private function delete( PropertyTableDefinition $propertyTable, array $rows ) {

		$condition = '';
		$connection = $this->store->getConnection( 'mw.db' );

		// We build a condition that mentions s_id only once,
		// since it must be the same for all rows. This should
		// help the DBMS in selecting the rows (it would not be
		// easy for to detect that all tuples share one s_id).
		$sid = false;
		$tableName = $propertyTable->getName();

		foreach ( $rows as $row ) {
			if ( $sid === false ) {
				if ( !array_key_exists( 's_id', (array)$row ) ) {
					// FIXME: The assumption that s_id is present does not hold.
					// This return is there to prevent fatal errors, but does
					// not fix the issue of this code being broken
					return;
				}

				// 's_id' exists for all tables with $propertyTable->usesIdSubject()
				$sid = $row['s_id'];
			}

			unset( $row['s_id'] );

			if ( $condition != '' ) {
				$condition .= ' OR ';
			}

			$condition .= '(' . $connection->makeList( $row, LIST_AND ) . ')';
		}

		$condition = "s_id=" . $connection->addQuotes( $sid ) . " AND ($condition)";

		$connection->delete(
			$tableName,
			[ $condition ],
			__METHOD__ . "-$tableName"
		);
	}

	private function aggregate_ids( &$ids, $propertyTable, $rows ) {

		$isCategory = false;

		if ( $propertyTable->isFixedPropertyTable() ) {

			$property = new DIProperty(
				$propertyTable->getFixedProperty()
			);

			$pid = $this->store->getObjectIds()->makeSMWPropertyID( $property );
			$isCategory = $property->getKey() === '_INST';
		}

		foreach ( $rows as $row ) {
			$sid = $isCategory ? $row['o_id'] : $row['s_id'];
			$ids[$sid] = true;

			// Individual pid? or fixed?
			if ( isset( $row['p_id'] ) ) {
				$pid = $row['p_id'];
			}

			$ids[$pid] = true;
		}
	}

	private function update_touched( $ids ) {

		if ( $ids === [] ) {
			return;
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$touched = $connection->timestamp();

		// Updating across the entity table with some properties (Modification
		// date etc.) to see more frequent updates than others. Do we need to use
		// onTransctionIdle( ... ) to avoid locking the rows for succeeding
		// updates?

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_touched' => $touched
			],
			[
				'smw_id' => $ids
			],
			__METHOD__
		);
	}

}
