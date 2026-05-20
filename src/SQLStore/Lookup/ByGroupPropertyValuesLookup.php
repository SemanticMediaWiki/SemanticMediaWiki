<?php

namespace SMW\SQLStore\Lookup;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ByGroupPropertyValuesLookup {

	/**
	 * @var array
	 */
	private array $cache = [];

	/**
	 * @since 3.2
	 */
	public function __construct( private readonly SQLStore $store ) {
	}

	/**
	 * @since 3.2
	 *
	 * @param Property $property
	 * @param WikiPage[]|string[] $subjects
	 *
	 * @return array
	 */
	public function findValueGroups( Property $property, array $subjects ): array {
		$diType = DataTypeRegistry::getInstance()->getDataItemByType(
			$property->findPropertyValueType()
		);

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$diType
		);

		$fields = $diHandler->getFetchFields();
		$rows = $this->fetchValuesByGroup( $property, $subjects );

		$valueGroups = [];
		$rawValues = [];
		$dataItems = [];
		$counts = [];

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

			$dbKeys = count( $dbKeys ) > 1 ? $dbKeys : $dbKeys[0];

			$dataItem = $diHandler->dataItemFromDBKeys(
				$dbKeys
			);

			$dataItems[] = $dataItem;
			$counts[] = $row->count;
		}

		foreach ( $dataItems as $k => $dataItem ) {

			$dv = DataValueFactory::getInstance()->newDataValueByItem(
				$dataItem,
				$property
			);

			$key = $dv->getShortWikiText();

			// Avoid any suprises with encoded quantity values
			$key = str_replace( [ '&#160;', '&nbsp;' ], ' ', $key );

			if ( !isset( $valueGroups[$key] ) ) {
				$valueGroups[$key] = $counts[$k];
			} else {
				// Record types, monolingual types aren't grouped by a value label hence
				// count them manually
				$valueGroups[$key] += $counts[$k];
			}

			$rawValues[$key] = $dv->getWikiValue();
		}

		return [
			'groups' => $valueGroups,
			'raw' => $rawValues
		];
	}

	public function fetchValuesByGroup( Property $property, $subjects ) {
		$tableid = $this->store->findPropertyTableID( $property );
		$entityIdManager = $this->store->getObjectIds();

		$proptables = $this->store->getPropertyTables();

		if ( $tableid === '' || !isset( $proptables[$tableid] ) || $subjects === [] ) {
			return [];
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$propTable = $proptables[$tableid];
		$isIdField = false;

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$propTable->getDiType()
		);

		foreach ( $diHandler->getFetchFields() as $field => $fieldType ) {
			if ( !$isIdField && $fieldType === FieldType::FIELD_ID ) {
				$isIdField = true;
			}
		}

		$groupBy = $diHandler->getLabelField();
		$pid = '';

		if ( $groupBy === '' ) {
			$groupBy = $diHandler->getIndexField();
		}

		$groupBy = "p.$groupBy";
		$orderBy = "count DESC, $groupBy ASC";

		$diType = $propTable->getDiType();

		if ( $diType === DataItem::TYPE_WIKIPAGE ) {
			$fields = [
				'i.smw_id',
				'i.smw_title',
				'i.smw_namespace',
				'i.smw_iw',
				'i.smw_subobject',
				'i.smw_hash',
				'i.smw_sort',
				"COUNT( $groupBy ) as count"
			];

			$groupBy = 'p.o_id, i.smw_id';
			$orderBy = 'count DESC, i.smw_sort ASC';
		} elseif ( $diType === DataItem::TYPE_BLOB ) {
			$fields = [ 'p.o_hash, p.o_blob', 'COUNT( p.o_hash ) as count' ];
			$groupBy = 'p.o_hash, p.o_blob';
		} elseif ( $diType === DataItem::TYPE_URI ) {
			$fields = [ 'p.o_serialized, p.o_blob', 'COUNT( p.o_serialized ) as count' ];
			$groupBy = 'p.o_serialized, p.o_blob';
		} elseif ( $diType === DataItem::TYPE_NUMBER ) {
			$fields = [ 'p.o_serialized,p.o_sortkey, COUNT( p.o_serialized ) as count' ];
			$groupBy = 'p.o_serialized,p.o_sortkey';
			$orderBy = 'count DESC, p.o_sortkey DESC';
		} else {
			$fields = [ "$groupBy", "COUNT( $groupBy ) as count" ];
		}

		if ( !$propTable->isFixedPropertyTable() ) {
			$pid = $entityIdManager->getSMWPropertyID( $property );
		}

		if ( $isIdField ) {
			$res = $connection->newSelectQueryBuilder()
				->select( $fields )
				->from( SQLStore::ID_TABLE, 'o' )
				->join( $propTable->getName(), 'p', [ 'p.s_id=o.smw_id' ] )
				->join( SQLStore::ID_TABLE, 'i', [ 'p.o_id=i.smw_id' ] )
				->where( [
					'o.smw_hash' => $subjects,
					$connection->expr( 'o.smw_iw', '!=', SMW_SQL3_SMWIW_OUTDATED ),
					$connection->expr( 'o.smw_iw', '!=', SMW_SQL3_SMWDELETEIW ),
				] + ( $pid !== '' ? [ 'p.p_id' => $pid ] : [] ) )
				->groupBy( $groupBy )
				->orderBy( $orderBy )
				->caller( __METHOD__ )
				->fetchResultSet();
		} else {
			$res = $connection->newSelectQueryBuilder()
				->select( $fields )
				->from( SQLStore::ID_TABLE, 'o' )
				->join( $propTable->getName(), 'p', [ 'p.s_id=o.smw_id' ] )
				->where( [
					'o.smw_hash' => $subjects,
					$connection->expr( 'o.smw_iw', '!=', SMW_SQL3_SMWIW_OUTDATED ),
					$connection->expr( 'o.smw_iw', '!=', SMW_SQL3_SMWDELETEIW ),
				] + ( $pid !== '' ? [ 'p.p_id' => $pid ] : [] ) )
				->groupBy( $groupBy )
				->orderBy( $orderBy )
				->caller( __METHOD__ )
				->fetchResultSet();
		}

		return $res;
	}

}
