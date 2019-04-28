<?php

namespace SMW\SQLStore;

use RuntimeException;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\SemanticData;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWDIError as DIError;

/**
 * Builds a table row representation for a SemanticData object.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableRowMapper {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param SemanticData $semanticData
	 *
	 * @return ChangeOp
	 */
	public function newChangeOp( $id, SemanticData $semanticData ) {

		list( $dataArray, $textItems, $propertyList, $fixedPropertyList ) = $this->mapToRows(
			$id,
			$semanticData
		);

		$subject = $semanticData->getSubject();
		$changeOp = new ChangeOp( $subject );

		foreach ( $fixedPropertyList as $key => $record ) {
			$changeOp->addFixedPropertyRecord( $key, $record );
		}

		$changeOp->addPropertyList( $propertyList );

		$changeOp->addDataOp(
			$subject->getHash(),
			$dataArray
		);

		return $changeOp;
	}

	/**
	 * Create an array of rows to insert into property tables in order to
	 * store the given SemanticData. The given $sid (subject page id) is
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
	 * @since 3.0
	 *
	 * @param integer $sid
	 * @param SemanticData $semanticData
	 *
	 * @return array
	 */
	public function mapToRows( $sid, SemanticData $semanticData ) {

		list( $rows, $textItems, $propertyList, $fixedPropertyList ) = $this->mapData(
			$sid,
			$semanticData
		);

		return [ $rows, $textItems, $propertyList, $fixedPropertyList ];
	}

	/**
	 * Create a string key for hashing an array of values that represents a
	 * row in the database. Used to eliminate duplicates and to support
	 * diff computation. This is not stored in the database, so it can be
	 * changed without causing any problems with legacy data.
	 *
	 * @since 3.0
	 *
	 * @param array $fieldArray
	 *
	 * @return string
	 */
	public function makeHash( array $array ) {
		return md5( implode( '#', $array ) );;
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
	private function mapData( $sid, SemanticData $semanticData ) {

		$subject = $semanticData->getSubject();
		$propertyTables = $this->store->getPropertyTables();

		$rows = [];

		// Keep the list for the Diff to avoid having to lookup any property ID
		// reference during a post processing
		$propertyList = [];
		$fixedPropertyList = [];
		$textItems = [];

		$this->store->getObjectIds()->warmUpCache(
			$semanticData->getProperties()
		);

		foreach ( $semanticData->getProperties() as $property ) {

			$tableId = $this->store->findPropertyTableID( $property );

			// not stored in a property table, e.g., sortkeys
			if ( $tableId === null ) {
				continue;
			}

			// "Notice: Undefined index ..." or when a type isn't registered
			if ( !isset( $propertyTables[$tableId] ) ) {
				continue;
			}

			$propertyTable = $propertyTables[$tableId];

			// not using subject ids, e.g., redirects
			if ( !$propertyTable->usesIdSubject() ) {
				continue;
			}

			$insertValues = [ 's_id' => $sid ];
			$p_type = $property->findPropertyValueType();

			if ( !$propertyTable->isFixedPropertyTable() ) {
				$insertValues['p_id'] = $this->store->getObjectIds()->makeSMWPropertyID(
					$property
				);

				$propertyList[$property->getKey()] = [ '_id' => $insertValues['p_id'], '_type' => $p_type ];
			} else {
				$pid = $this->store->getObjectIds()->makeSMWPropertyID(
					$property
				);

				$fixedPropertyList[$tableId] = [
					'key' => $property->getKey(),
					'p_id' => $pid,
				];

				$propertyList[$property->getKey()] = [ '_id' => $pid, '_type' => $p_type ];
			}

			$pid = $propertyList[$property->getKey()]['_id'];

			if ( !isset( $textItems[$pid] ) ) {
				$textItems[$pid] = [];
			}

			// Avoid issues when an expected predefined property is no longer
			// available (i.e. an extension that defined that property was disabled)
			try {
				$propertyValues = $semanticData->getPropertyValues( $property );
			} catch( PredefinedPropertyLabelMismatchException $e ) {
				continue;
			}

			foreach ( $propertyValues as $dataItem ) {

				if ( $dataItem instanceof DIError ) { // ignore error values
					continue;
				}

				$tableName = $propertyTable->getName();

				if ( !array_key_exists( $tableName, $rows ) ) {
					$rows[$tableName] = [];
				}

				if ( $dataItem->getDIType() === DataItem::TYPE_BLOB ) {
					$textItems[$pid][] = $dataItem->getString();
				} elseif ( $dataItem->getDIType() === DataItem::TYPE_URI ) {
					$textItems[$pid][] = $dataItem->getSortKey();
				} elseif ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {
					$textItems[$pid][] = $dataItem->getSortKey();
				}

				$dataItemValues = $this->store->getDataItemHandlerForDIType( $dataItem->getDIType() )->getInsertValues( $dataItem );

				// Ensure that the sortkey is a string
				if ( isset( $dataItemValues['o_sortkey'] ) ) {
					$dataItemValues['o_sortkey'] = (string)$dataItemValues['o_sortkey'];
				}

				$insertValues = array_merge( $insertValues, $dataItemValues );

				// Make sure to build a unique set without duplicates which could happen
				// if an annotation is made to a property that has a redirect pointing
				// to the same p_id
				$hash = $this->makeHash(
					$insertValues
				);

				$rows[$tableName][$hash] = $insertValues;
			}

			// Unused
			if ( $textItems[$pid] === [] ) {
				unset( $textItems[$pid] );
			}
		}

		// Special handling of Concepts
		if ( $subject->getNamespace() === SMW_NS_CONCEPT && $subject->getSubobjectName() == '' ) {
			$this->mapConceptTable( $sid, $rows );
		}

		return [ $rows, $textItems, $propertyList, $fixedPropertyList ];
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
	private function mapConceptTable( $sid, &$insertData ) {

		$connection = $this->store->getConnection( 'mw.db' );

		// Make sure that there is exactly one row to be written:
		if ( array_key_exists( 'smw_fpt_conc', $insertData ) && !empty( $insertData['smw_fpt_conc'] ) ) {
			$insertValues = end( $insertData['smw_fpt_conc'] );
		} else {
			$insertValues = [
				's_id'          => $sid,
				'concept_txt'   => '',
				'concept_docu'  => '',
				'concept_features' => 0,
				'concept_size'  => -1,
				'concept_depth' => -1
			];
		}

		// Add existing cache status data to this row:
		$row = $connection->selectRow(
			'smw_fpt_conc',
			[ 'cache_date', 'cache_count' ],
			[ 's_id' => $sid ],
			__METHOD__
		);

		if ( $row === false ) {
			$insertValues['cache_date'] = null;
			$insertValues['cache_count'] = null;
		} else {
			$insertValues['cache_date'] = $row->cache_date;
			$insertValues['cache_count'] = $row->cache_count;
		}

		$insertData['smw_fpt_conc'] = [ $insertValues ];
	}

}
