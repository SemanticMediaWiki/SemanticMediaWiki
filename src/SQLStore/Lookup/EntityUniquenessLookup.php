<?php

namespace SMW\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWDataItem as DataItem;
use SMW\RequestOptions;
use SMW\IteratorFactory;
use InvalidArgumentException;
use RuntimeException;
use SMWDIContainer as DIContainer;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class EntityUniquenessLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( Store $store, IteratorFactory $iteratorFactory ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
	}

	/**
	 * Find references (all or limited by RequestOptions) for the combination of
	 * a property and a value. This can be used to identify uniqueness violations
	 * amongst entities where the same value (+property) is assigned to different
	 * subjects or it be used to count the cardinality for a specific value
	 * representation.
	 *
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 * @param DataItem $dataItem
	 * @param RequestOptions $requestOptions
	 *
	 * @return Iterator|[]
	 */
	public function checkConstraint( DIProperty $property, DataItem $dataItem, RequestOptions $requestOptions ) {

		$propTableId = $this->store->getPropertyTableInfoFetcher()->findTableIdForProperty(
			$property
		);

		$proptables = $this->store->getPropertyTables();
		$propertyTable = $proptables[$propTableId];

		if ( !isset( $proptables[$propTableId] ) || !$propertyTable->usesIdSubject() ) {
			return [];
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->index = 1;
		$query->alias = 't';
		$i = $query->index;

		$query->table( $propertyTable->getName(), "{$query->alias}{$i}" );

		// Only find entities
		$query->field( "{$query->alias}{$i}.s_id" );

		$this->resolve_value_condition( $propertyTable, $property, $dataItem, $query );

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
			if ( is_callable( $extraCondition ) ) {
				$query->condition( $extraCondition( $this->store, $query, "{$query->alias}{$i}" ) );
			} else {
				throw new RuntimeException( "Expected a callable at this point!" );
			}
		}

		$query->type( 'SELECT' );
		$query->options( [ 'LIMIT' => $requestOptions->getLimit() ] );

		$res = $connection->query(
			$query,
			__METHOD__
		);

		$result = $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $res ),
			function( $row ) {
				return $this->store->getObjectIds()->getDataItemById( $row->s_id );
			}
		);

		return $result;
	}

	private function resolve_value_condition( $propertyTable, $property, $dataItem, $query ) {

		// Collect conditions to appear as
		// `... (t1.p_id='121913' AND t1.o_sortkey='3520062') ...`
		$conditions = [];

		// Keep the index in case of a recursive iteration
		$i = $query->index;

		if ( !$propertyTable->isFixedPropertyTable() ) {

			$pid = $this->store->getObjectIds()->getSMWPropertyID(
				$property
			);

			$conditions[] = $query->eq( "{$query->alias}{$i}.p_id", $pid );
		}

		$diHandler = $this->store->getDataItemHandlerForDIType(
			$propertyTable->getDiType()
		);

		if ( !$dataItem instanceof DIContainer ) {
			foreach ( $diHandler->getWhereConds( $dataItem ) as $fieldName => $value ) {
				$conditions[] = $query->eq( "{$query->alias}{$i}.$fieldName", $value );
			}
		} else {

			/**
			 * For a container based property/value pair we expected something similar
			 * to:
			 *
			 * SELECT t1.s_id FROM `smw_di_wikipage` AS t1
			 *   INNER JOIN `smw_di_wikipage` AS t2 ON t2.s_id=t1.o_id
			 *   INNER JOIN `smw_di_wikipage` AS t3 ON t3.s_id=t1.o_id
			 *   INNER JOIN `smw_di_number` AS t4 ON t4.s_id=t1.o_id
			 * WHERE
			 *   (t2.p_id='333615' AND t2.o_id='302096') AND
			 *   (t3.p_id='333611' AND t3.o_id='193213') AND
			 *   (t4.p_id='121913' AND t4.o_sortkey='3520062') AND
			 *   (t1.p_id='310161') AND (t1.s_id!='333608')
			 * LIMIT 2
			 */

			// Handle containers recursively
			$this->resolve_container_conditions( $propertyTable, $dataItem, $query );
		}

		$query->condition( $query->asAnd( $conditions ) );
	}

	private function resolve_container_conditions( $propertyTable, $dataItem, $query ) {

		$proptables = $this->store->getPropertyTables();
		$semanticData = $dataItem->getSemanticData();

		$alias = $query->alias;
		$i = $query->index;

		// ought to be a type 'p' object
		$keys = array_keys( $propertyTable->getFields( $this->store ) );
		$joinfield = "{$alias}{$i}." . reset( $keys );

		foreach ( $semanticData->getProperties() as $property ) {

			$tableid =  $this->store->findPropertyTableID( $property );
			$subproptable = $proptables[$tableid];

			foreach ( $semanticData->getPropertyValues( $property ) as $subvalue ) {
				// Increase the index for each iteration to ensure that each
				// condition has its own alias
				$i++;

				if ( $subproptable->usesIdSubject() ) {
					// simply add property table to check values
					$query->join(
						'INNER JOIN',
						[
							// e.g. `... INNER JOIN `smw_di_wikipage` AS t2 ON t2.s_id=t1.o_id ...`
							$subproptable->getName() => "{$alias}{$i} ON {$alias}{$i}.s_id=$joinfield"
						]
					);
				} else {
					// Rare case with a table that uses subject title+namespace
					// in a container object (should never happen in SMW core!!)
					$query->join(
						'INNER JOIN',
						[
							SQLStore::ID_TABLE => "ids{$i} ON ids{$i}.smw_id=$joinfield"
						]
					);
					$query->join(
						'INNER JOIN',
						[
							$subproptable->getName() => "{$alias}{$i} ON {$alias}{$i}.s_title=ids{$alias}{$i}.smw_title AND {$alias}{$i}.s_namespace=ids{$alias}{$i}.smw_namespace"
						]
					);
				}

				$query->index = $i;
				$this->resolve_value_condition( $subproptable, $property, $subvalue, $query );
			}
		}
	}

}
