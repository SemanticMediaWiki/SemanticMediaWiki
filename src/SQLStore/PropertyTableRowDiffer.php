<?php

namespace SMW\SQLStore;

use InvalidArgumentException;
use SMW\DIProperty;
use SMW\Exception\DataItemException;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\SemanticData;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Store;
use SMWDataItem as DataItem;

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
	private $store;

	/**
	 * @var PropertyTableRowMapper
	 */
	private $propertyTableRowMapper;

	/**
	 * @var ChangeOp
	 */
	private $changeOp;

	/**
	 * @var boolean
	 */
	private $checkRemnantEntities = false;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 * @param PropertyTableRowMapper $propertyTableRowMapper
	 */
	public function __construct( Store $store, PropertyTableRowMapper $propertyTableRowMapper ) {
		$this->store = $store;
		$this->propertyTableRowMapper = $propertyTableRowMapper;
	}

	/**
	 * @since 3.0
	 *
	 * @param ChangeOp|null $changeOp
	 */
	public function setChangeOp( ChangeOp $changeOp = null ) {
		$this->changeOp = $changeOp;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $checkRemnantEntities
	 */
	public function checkRemnantEntities( $checkRemnantEntities ) {
		$this->checkRemnantEntities = (bool)$checkRemnantEntities;
	}

	/**
	 * @since 3.0
	 *
	 * @return ChangeOp
	 */
	public function getChangeOp() {
		return $this->changeOp;
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
	public function computeTableRowDiff( $sid, SemanticData $semanticData ) {

		$tablesDeleteRows = [];
		$tablesInsertRows = [];

		$propertyList = [];
		$textItems = [];

		$newHashes = [];

		if ( $this->changeOp === null ) {
			$this->setChangeOp( new ChangeOp( $semanticData->getSubject() ) );
		}

		list( $newData, $textItems, $propertyList, $fixedPropertyList ) = $this->propertyTableRowMapper->mapToRows(
			$sid,
			$semanticData
		);

		$this->changeOp->addPropertyList( $propertyList );

		$oldHashes = $this->fetchPropertyTableHashesById(
			$sid
		);

		$propertyTables = $this->store->getPropertyTables();
		$connection = $this->store->getConnection( 'mw.db' );

		$fixedProperties = [];

		foreach ( $propertyTables as $propertyTable ) {

			if ( !$propertyTable->isFixedPropertyTable() ) {
				continue;
			}

			try {
				$fixedProperties[] = new DIProperty( $propertyTable->getFixedProperty() );
			} catch( PredefinedPropertyLabelMismatchException $e ) {
				// Do nothing!
			}
		}

		$this->store->getObjectIds()->warmUpCache(
			$fixedProperties
		);

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

				// Isn't registered therefore leave it alone (property was removed etc.)
				try {
					$property = new DIProperty( $fixedProperty['key'] );
					$fixedProperty['p_id'] = $this->store->getObjectIds()->getSMWPropertyID(
						$property
					);
				} catch ( DataItemException $e ) {
					$fixedProperty = false;
				}
			}

			if ( $fixedProperty ) {
				$this->changeOp->addFixedPropertyRecord( $tableName, $fixedProperty );
			}

			if ( array_key_exists( $tableName, $newData ) ) {
				// Note: the order within arrays should remain the same while page is not updated.
				// Hence we do not sort before serializing. It is hoped that this assumption is valid.
				$newHashes[$tableName] = $this->createHash(
					$tableName,
					$newData,
					$semanticData->getOption( SemanticData::OPT_LAST_MODIFIED )
				);

				if ( array_key_exists( $tableName, $oldHashes ) && $newHashes[$tableName] == $oldHashes[$tableName] ) {
					// Table contains data and should contain the same data after update
					continue;
				} else { // Table contains no data or contains data that is different from the new
					list( $tablesInsertRows[$tableName], $tablesDeleteRows[$tableName] ) = $this->arrayDeleteMatchingValues(
						$this->fetchCurrentContentsForPropertyTable( $sid, $propertyTable ),
						$newData[$tableName],
						$propertyTable
					);
				}
			} elseif ( array_key_exists( $tableName, $oldHashes ) ) {
				// Table contains data but should not contain any after update
				$tablesInsertRows[$tableName] = [];
				$tablesDeleteRows[$tableName] = $this->fetchCurrentContentsForPropertyTable(
					$sid,
					$propertyTable
				);
			} elseif ( $this->checkRemnantEntities ) {

				// #3849
				// Check the table that wasn't part of the old and new hash
				$row = $connection->selectRow(
					$propertyTable->getName(),
					's_id',
					[
						's_id' => $sid
					],
					__METHOD__
				);

				// Find and remove any remnants (ghosts) from possible failed
				// updates that weren't rollback correctly
				if ( $row !== false ) {
					$tablesInsertRows[$tableName] = [];
					$tablesDeleteRows[$tableName] = $this->fetchCurrentContentsForPropertyTable(
						$sid,
						$propertyTable
					);
				}
			}
		}

		$this->changeOp->addTextItems(
			$sid,
			$textItems
		);

		$this->changeOp->addDataOp(
			$semanticData->getSubject()->getHash(),
			$newData
		);

		$this->changeOp->addDiffOp(
			$tablesInsertRows,
			$tablesDeleteRows
		);

		return [ $tablesInsertRows, $tablesDeleteRows, $newHashes ];
	}

	private function fetchPropertyTableHashesById( $sid ) {
		return $this->store->getObjectIds()->getPropertyTableHashes( $sid );
	}

	/**
	 * @note The hashMutator can be used to force a modification in order to detect
	 * content edits where text has been changed but the md5 table hash remains
	 * unchanged and therefore would not re-compute the diff and misses out
	 * critical updates on property tables.
	 *
	 * The phenomenon has been observed in connection with a page turned from
	 * a redirect to a normal page or for undeleted pages.
	 */
	private function createHash( $tableName, $newData, $hashMutator = '' ) {
		return md5( serialize( array_values( $newData[$tableName] ) ) . $hashMutator );
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

		$contents = [];
		$connection = $this->store->getConnection( 'mw.db' );

		$result = $connection->select(
			$connection->tablename( $propertyTable->getName() ),
			'*',
			[ 's_id' => $sid ],
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

				$hash = $this->propertyTableRowMapper->makeHash( $resultRow );
				$contents[$hash] = $resultRow;
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
	 * @param PropertyTableDefinition $propertyTable
	 *
	 * @return array
	 */
	private function arrayDeleteMatchingValues( $oldValues, $newValues, $propertyTable ) {

		$isString = $propertyTable->getDIType() === DataItem::TYPE_BLOB;

		// Cycle through old values
		foreach ( $oldValues as $oldKey => $oldValue ) {

			// Cycle through new values
			foreach ( $newValues as $newKey => $newValue ) {

				// #2061
				// Loose comparison on a string will fail for cases like 011 == 0011
				// therefore use the strict comparison and have the values
				// remain if they don't match
				if ( $isString && $newValue !== $oldValue ) {
					continue;
				}

				// Delete matching values
				// use of == is intentional to account for oldValues only
				// containing strings while new values might also contain other
				// types
				if ( $newValue == $oldValue ) {
					unset( $newValues[$newKey] );
					unset( $oldValues[$oldKey] );
				}
			}
		};

		// Arrays have to be renumbered because database functions expect an
		// element with index 0 to be present in the array
		return [ array_values( $newValues ), array_values( $oldValues ) ];
	}

}
