<?php

namespace SMW\SQLStore\Lookup;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;

/**
 * @private
 *
 * @license GNU GPL v2
 * @since 3.2
 *
 * @author mwjames
 */
class ByGroupPropertyValuesLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var []
	 */
	private $cache = [];

	/**
	 * @since 3.2
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param DIProperty $property
	 * @param DIWikiPage[]|string[] $subjects
	 *
	 * @return array
	 */
	public function findValueGroups( DIProperty $property, array $subjects ) : array {

		$diType = DataTypeRegistry::getInstance()->getDataItemId(
			$property->findPropertyTypeID()
		);

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$diType
		);

		$fields = $diHandler->getFetchFields();
		$rows = $this->fetchValuesByGroup( $property, $subjects );

		$valueGroups = [];
		$rawValues = [];

		foreach ( $rows as $row ) {

			$dbKeys = [];

			foreach ( $fields as $field => $fieldType ) {

				if ( $fieldType === FieldType::FIELD_ID ) {
					$dbKeys[] = $row->smw_title;
					$dbKeys[] = $row->smw_namespace;
					$dbKeys[] = $row->smw_iw;
					$dbKeys[] = $row->smw_sort;
					$dbKeys[] = $row->smw_subobject;
					break;
				} else {
					$dbKeys[] = $row->$field;
				}
			}

			$dataItem = $diHandler->dataItemFromDBKeys(
				count( $dbKeys ) > 1 ? $dbKeys : $dbKeys[0]
			);

			$dv = DataValueFactory::getInstance()->newDataValueByItem(
				$dataItem,
				$property
			);

			$key = $dv->getShortWikiText();

			// Avoid any suprises with encoded quantity values
			$key = str_replace( [ '&#160;', '&nbsp;' ], ' ', $key );

			if ( !isset( $valueGroups[$key] ) ) {
				$valueGroups[$key] = $row->count;
			} else {
				// Record types, monolingual types aren't grouped by a value label hence
				// count them manually
				$valueGroups[$key] += $row->count;
			}

			$rawValues[$key] = $dv->getWikiValue();
		}

		return [
			'groups' => $valueGroups,
			'raw' => $rawValues
		];
	}

	public function fetchValuesByGroup( DIProperty $property, $subjects ) {

		$tableid = $this->store->findPropertyTableID( $property );
		$entityIdManager = $this->store->getObjectIds();

		$proptables = $this->store->getPropertyTables();

		if ( $tableid === '' || !isset( $proptables[$tableid] ) || $subjects === [] ) {
			return [];
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$propTable = $proptables[$tableid];
		$isIdField = false;

		$fields =  [
			'o.smw_id',
			'o.smw_title',
			'o.smw_namespace',
			'o.smw_iw',
			'o.smw_subobject',
			'o.smw_hash',
			'o.smw_sort',
		];

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$propTable->getDiType()
		);

		foreach ( $diHandler->getFetchFields() as $field => $fieldType ) {

			if ( !$isIdField && $fieldType === FieldType::FIELD_ID ) {
				$isIdField = true;
			}

			$fields[] = "p.$field";
		}

		$groupBy = $diHandler->getLabelField();
		$pid = '';

		if ( $groupBy === '' ) {
			$groupBy = $diHandler->getIndexField();
		}

		$fields[] = "COUNT( p.$groupBy ) as count";
		$groupBy = "p.$groupBy";

		// Avoid a " 42803 ERROR:  column ... must appear in the GROUP BY
		// clause or be used in an aggregate function"
		if ( $connection->isType( 'postgres' ) ) {
			$diType = $propTable->getDiType();

			if ( $diType === DataItem::TYPE_WIKIPAGE ) {
				$groupBy .= ', i.smw_id';
			} elseif ( $diType === DataItem::TYPE_BLOB ) {
				$groupBy .= ', p.o_blob, o.smw_id';
			} else {
				$groupBy .= ', o.smw_id';
			}
		}

		if ( !$propTable->isFixedPropertyTable() ) {
			$pid = $entityIdManager->getSMWPropertyID( $property );
		}

		if ( $isIdField ) {

			foreach ( $fields as $k => $f ) {
				$fields[$k] = str_replace( 'o.', 'i.', $f );
			}

			$res = $connection->select(
				[
					'o' => $connection->tableName( SQLStore::ID_TABLE ),
					'p' => $connection->tableName( $propTable->getName() ),
					'i' => $connection->tableName( SQLStore::ID_TABLE )
				],
				$fields,
				[
					'o.smw_hash' => $subjects,
					'o.smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ),
					'o.smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
				] + ( $pid !== '' ? [ 'p.p_id' => $pid ] : [] ),
				__METHOD__,
				[
					'GROUP BY' => "$groupBy"
				],
				[
					'p' => [ 'INNER JOIN', [ 'p.s_id=o.smw_id' ] ],
					'i' => [ 'INNER JOIN', [ 'p.o_id=i.smw_id' ] ],
				]
			);
		} else {
			$res = $connection->select(
				[
					'o' => $connection->tableName( SQLStore::ID_TABLE ),
					'p' => $connection->tableName( $propTable->getName() )
				],
				$fields,
				[
					'o.smw_hash' => $subjects,
					'o.smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ),
					'o.smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
				] + ( $pid !== '' ? [ 'p.p_id' => $pid ] : [] ),
				__METHOD__,
				[
					'GROUP BY' => "$groupBy"
				],
				[
					'p' => [ 'INNER JOIN', [ 'p.s_id=o.smw_id' ] ],
				]
			);
		}

		return $res;
	}

}
