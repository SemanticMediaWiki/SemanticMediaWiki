<?php

namespace SMW\SQLStore;

use SMW\MediaWiki\Database;
use SMW\SemanticData;
use SMW\Store;
use SMW\DIProperty;
use SMWDIError as DIError;
use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author Markus Krötzsch
 * @author Nischay Nahata
 * @author mwjames
 */
class PropertyTableRowDiffer {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var CompositePropertyTableDiffIterator
	 */
	private $compositePropertyTableDiffIterator = null;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * Compute necessary insertions, deletions, and new table hashes for
	 * updating the database to contain $newData for the subject with ID
	 * $sid. Insertions and deletions are returned in as an array mapping
	 * table names to arrays of table rows. Each row is an array mapping
	 * column names to values as usual. The table hashes are returned as
	 * an array mapping table names to hash values.
	 *
	 * It is ensured that table names (keys) in the returned insert
	 * data are exaclty the same as the table names (keys) in the delete
	 * data, even if one of them maps to an empty array (no changes). If
	 * a table needs neither insertions nor deletions, then it will not
	 * be mentioned as a key anywhere.
	 *
	 * The given database is only needed for reading the data that is
	 * related assigned to sid.
	 *
	 * @since 2.3
	 *
	 * @param integer $sid
	 * @param SemanticData $semanticData
	 *
	 * @return array
	 */
	public function computeTableRowDiffFor( $sid, SemanticData $semanticData ) {

		$tablesDeleteRows = array();
		$tablesInsertRows = array();

		$newHashes = array();

		$newData = $this->mapToInsertValueFormat(
			$sid,
			$semanticData
		);

		$oldHashes = $this->fetchPropertyTableHashesForId( $sid );
		$propertyTables = $this->store->getPropertyTables();

		foreach ( $propertyTables as $propertyTable ) {

			if ( !$propertyTable->usesIdSubject() ) { // ignore; only affects redirects anyway
				continue;
			}

			$tableName = $propertyTable->getName();
			$fixedProperty = false;

			// Fixed property tables have no p_id declared, the auxiliary
			// information is provided to easily map fixed tables and
			// its assigned property/id
			if ( $propertyTable->isFixedPropertyTable() ) {
				$fixedProperty['key'] = $propertyTable->getFixedProperty();
				$fixedProperty['p_id'] = $this->store->getObjectIds()->getSMWPropertyID(
					new DIProperty( $fixedProperty['key'] )
				);
			}

			if ( array_key_exists( $tableName, $newData ) ) {
				// Note: the order within arrays should remain the same while page is not updated.
				// Hence we do not sort before serializing. It is hoped that this assumption is valid.
				$newHashes[$tableName] = $this->createNewHashForTable(
					$tableName,
					$newData,
					$semanticData->getLastModified()
				);

				if ( array_key_exists( $tableName, $oldHashes ) && $newHashes[$tableName] == $oldHashes[$tableName] ) {
					// Table contains data and should contain the same data after update
					continue;
				} else { // Table contains no data or contains data that is different from the new
					list( $tablesInsertRows[$tableName], $tablesDeleteRows[$tableName] ) = $this->arrayDeleteMatchingValues(
						$this->fetchCurrentContentsForPropertyTable( $sid, $propertyTable ),
						$newData[$tableName]
					);

					if ( $fixedProperty ) {
						$this->getCompositePropertyTableDiff()->addFixedPropertyRecord( $tableName, $fixedProperty );
					}
				}
			} elseif ( array_key_exists( $tableName, $oldHashes ) ) {
				// Table contains data but should not contain any after update
				$tablesInsertRows[$tableName] = array();
				$tablesDeleteRows[$tableName] = $this->fetchCurrentContentsForPropertyTable(
					$sid,
					$propertyTable
				);

				if ( $fixedProperty ) {
					$this->getCompositePropertyTableDiff()->addFixedPropertyRecord( $tableName, $fixedProperty );
				}
			}
		}

		$this->getCompositePropertyTableDiff()->addTableRowsToCompositeDiff(
			$tablesInsertRows,
			$tablesDeleteRows
		);

		return array( $tablesInsertRows, $tablesDeleteRows, $newHashes );
	}

	/**
	 * @since 2.3
	 */
	public function resetCompositePropertyTableDiff() {
		$this->compositePropertyTableDiffIterator = null;
	}

	/**
	 * @since 2.3
	 *
	 * @return CompositePropertyTableDiffIterator
	 */
	public function getCompositePropertyTableDiff() {

		if ( $this->compositePropertyTableDiffIterator === null ) {
			$this->compositePropertyTableDiffIterator = new CompositePropertyTableDiffIterator();
		}

		return $this->compositePropertyTableDiffIterator;
	}

	private function fetchPropertyTableHashesForId( $sid ) {
		return $this->store->getObjectIds()->getPropertyTableHashes( $sid );
	}

	/**
	 * The hashModifier can be used to force a modification in order to detect
	 * content edits where text has been changed but the md5 table hash remains
	 * unchanged and therefore would not re-compute the diff and misses out
	 * critical updates on property tables.
	 *
	 * The phenomenon has been observed in connection with a page turned from
	 * a redirect to a normal page or for undeleted pages.
	 */
	private function createNewHashForTable( $tableName, $newData, $hashModifier = '' ) {
		return md5( serialize( array_values( $newData[$tableName] ) ) . $hashModifier );
	}

	/**
	 * Get the current data stored for the given ID in the given database
	 * table. The result is an array of updates, formatted like the one of
	 * the table insertion arrays created by preparePropertyTableInserts().
	 *
	 * @note Tables without IDs as subject are not supported. They will
	 * hopefully vanish soon anyway.
	 *
	 * @since 1.8
	 * @param integer $sid
	 * @param TableDefinition $tableDeclaration
	 * @return array
	 */
	private function fetchCurrentContentsForPropertyTable( $sid, TableDefinition $propertyTable ) {

		if ( !$propertyTable->usesIdSubject() ) { // does not occur, but let's be strict
			throw new InvalidArgumentException('Operation not supported for tables without subject IDs.');
		}

		$contents = array();
		$connection = $this->store->getConnection( 'mw.db' );

		$result = $connection->select(
			$connection->tablename( $propertyTable->getName() ),
			'*',
			array( 's_id' => $sid ),
			__METHOD__
		);

		foreach( $result as $row ) {
			if ( is_object( $row ) ) {

				$resultRow = (array)$row;

				// Always make sure to use int values for ids so
				// that the compare/hash will be of the same type
				if ( isset( $resultRow['s_id'] ) ) {
					$resultRow['s_id'] = (int)$resultRow['s_id'];
				}

				if ( isset( $resultRow['p_id'] ) ) {
					$resultRow['p_id'] = (int)$resultRow['p_id'];
				}

				if ( isset( $resultRow['o_id'] ) ) {
					$resultRow['o_id'] = (int)$resultRow['o_id'];
				}

				$contents[] = $resultRow;
			}
		}

		return $contents;
	}

	/**
	 * Delete all matching values from old and new arrays and return the
	 * remaining new values as insert values and the remaining old values as
	 * delete values.
	 *
	 * @param array $oldValues
	 * @param array $newValues
	 * @return array
	 */
	private function arrayDeleteMatchingValues( $oldValues, $newValues ) {

		// cycle through old values
		foreach ( $oldValues as $oldKey => $oldValue ) {

			// cycle through new values
			foreach ( $newValues as $newKey => $newValue ) {

				// delete matching values;
				// use of == is intentional to account for oldValues only
				// containing strings while new values might also contain other
				// types
				if ( $newValue == $oldValue ) {
					unset( $newValues[$newKey] );
					unset( $oldValues[$oldKey] );
				}
			}
		};

		// arrays have to be renumbered because database functions expect an
		// element with index 0 to be present in the array
		return array( array_values( $newValues ), array_values( $oldValues ) );
	}

	/**
	 * Create an array of rows to insert into property tables in order to
	 * store the given SMWSemanticData. The given $sid (subject page id) is
	 * used directly and must belong to the subject of the data container.
	 * Sortkeys are ignored since they are not stored in a property table
	 * but in the ID table.
	 *
	 * The returned array uses property table names as keys and arrays of
	 * table rows as values. Each table row is an array mapping column
	 * names to values.
	 *
	 * @note Property tables that do not use ids as subjects are ignored.
	 * This just excludes redirects that are handled differently anyway;
	 * it would not make a difference to include them here.
	 *
	 * @since 1.8
	 *
	 * @param integer $sid
	 * @param SemanticData $semanticData
	 *
	 * @return array
	 */
	private function mapToInsertValueFormat( $sid, SemanticData $semanticData ) {
		$updates = array();

		$subject = $semanticData->getSubject();
		$propertyTables = $this->store->getPropertyTables();

		foreach ( $semanticData->getProperties() as $property ) {

			$tableId = $this->store->findPropertyTableID( $property );

			// not stored in a property table, e.g., sortkeys
			if ( $tableId === null ) {
				continue;
			}

			$propertyTable = $propertyTables[$tableId];

			// not using subject ids, e.g., redirects
			if ( !$propertyTable->usesIdSubject() ) {
				continue;
			}

			$insertValues = array( 's_id' => $sid );

			if ( !$propertyTable->isFixedPropertyTable() ) {
				$insertValues['p_id'] = $this->store->getObjectIds()->makeSMWPropertyID( $property );
			}

			foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {

				if ( $dataItem instanceof DIError ) { // ignore error values
					continue;
				}

				if ( !array_key_exists( $propertyTable->getName(), $updates ) ) {
					$updates[$propertyTable->getName()] = array();
				}

				$dataItemValues = $this->store->getDataItemHandlerForDIType( $dataItem->getDIType() )->getInsertValues( $dataItem );

				// Ensure that the sortkey is a string
				if ( isset( $dataItemValues['o_sortkey'] ) ) {
					$dataItemValues['o_sortkey'] = (string)$dataItemValues['o_sortkey'];
				}

				// Make sure to build a unique set without duplicates which could happen
				// if an annotation is made to a property that has a redirect pointing
				// to the same p_id
				$insertValues = array_merge( $insertValues, $dataItemValues );
				$insertValuesHash = md5( implode( '#', $insertValues ) );

				$updates[$propertyTable->getName()][$insertValuesHash] = $insertValues;
			}
		}

		// Special handling of Concepts
		if ( $subject->getNamespace() === SMW_NS_CONCEPT && $subject->getSubobjectName() == '' ) {
			$this->fetchConceptTableInserts( $sid, $updates );
		}

		return $updates;
	}

	/**
	 * Add cache information to concept data and make sure that there is
	 * exactly one value for the concept table.
	 *
	 * @note This code will vanish when concepts have a more standard
	 * handling. So not point in optimizing this much now.
	 *
	 * @since 1.8
	 * @param integer $sid
	 * @param &array $insertData
	 */
	private function fetchConceptTableInserts( $sid, &$insertData ) {

		$connection = $this->store->getConnection( 'mw.db' );

		// Make sure that there is exactly one row to be written:
		if ( array_key_exists( 'smw_fpt_conc', $insertData ) && !empty( $insertData['smw_fpt_conc'] ) ) {
			$insertValues = end( $insertData['smw_fpt_conc'] );
		} else {
			$insertValues = array(
				's_id'          => $sid,
				'concept_txt'   => '',
				'concept_docu'  => '',
				'concept_features' => 0,
				'concept_size'  => -1,
				'concept_depth' => -1
			);
		}

		// Add existing cache status data to this row:
		$row = $connection->selectRow(
			'smw_fpt_conc',
			array( 'cache_date', 'cache_count' ),
			array( 's_id' => $sid ),
			__METHOD__
		);

		if ( $row === false ) {
			$insertValues['cache_date'] = null;
			$insertValues['cache_count'] = null;
		} else {
			$insertValues['cache_date'] = $row->cache_date;
			$insertValues['cache_count'] = $row->cache_count;
		}

		$insertData['smw_fpt_conc'] = array( $insertValues );
	}

}
