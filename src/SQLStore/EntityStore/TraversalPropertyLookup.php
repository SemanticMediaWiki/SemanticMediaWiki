<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\DIContainer;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyTableDefinition as PropertyTableDef;
use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2
 * @since 3.0
 *
 * @author mwjames
 */
class TraversalPropertyLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @see Store::getInProperties
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function fetchFromTable( PropertyTableDef $propertyTableDef, DataItem $dataItem, ?RequestOptions $requestOptions = null ) {
		$connection = $this->store->getConnection( 'mw.db' );
		$builder = $connection->newSelectQueryBuilder( 'read' );

		if ( $dataItem instanceof DIContainer ) {
			throw new RuntimeException( "DIContainer: " . $dataItem->getSerialization() );
		}

		// Potentially need to get more results, since options apply to union.
		if ( $requestOptions !== null ) {
			$subOptions = clone $requestOptions;
			$subOptions->limit = $requestOptions->limit + $requestOptions->offset;
			$subOptions->offset = 0;
		} else {
			$subOptions = null;
		}

		if ( !$propertyTableDef->isFixedPropertyTable() ) {

			$conditions = '';

			// No sorting
			$options = $this->store->getSQLOptions( $subOptions, '' );

			// Avoid any limit or offset for the sub-query in order to find all
			// incoming properties
			unset( $options['LIMIT'] );
			unset( $options['OFFSET'] );

			// Ensure to group same IDs to reduce the amount of data transferred
			// from the inner join
			$options['GROUP BY'] = 'p_id';

			$subquery = $builder->newSubquery();
			$subquery->select( 'p_id' )->from( $propertyTableDef->getName() );
			$this->buildWhereConds( $subquery, $dataItem );
			$connection->applySqlOptions( $subquery, $options );

			// Use subquery to match all possible IDs, no ORDER BY or DISTINCT to avoid filesort
			$builder->from( SQLStore::ID_TABLE )
				->join( $subquery, "t1", "t1.p_id=smw_id" );

			$builder->where( "smw_iw != " . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) )
				->where( "smw_iw != " . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ) );

			$conditions .= $this->store->getSQLConditions( $subOptions, 'smw_sortkey', 'smw_sortkey', $conditions !== '' );
			$options = $this->store->getSQLOptions( $subOptions, '' );

			if ( $conditions ) {
				$builder->where( $conditions );
			}
			if ( ! empty( $options ) ) {
				$connection->applySqlOptions( $builder, $options );
			}
			$builder->select( 'smw_title,smw_sortkey,smw_iw' )->distinct();

			$result = $builder->caller( __METHOD__ )->fetchResultSet();

		} else {
			$this->buildWhereConds( $builder, $dataItem );
			$builder->from( $propertyTableDef->getName(), "t1" )
				->select( $propertyTableDef->usesIdSubject() ? 's_id' : '*' )
				->limit( 1 );

			$result = $builder->caller( __METHOD__ )->fetchResultSet();

			if ( $result->numRows() > 0 ) {
				$res = new \stdClass;
				$res->smw_title = $propertyTableDef->getFixedProperty();
				$result = [ $res ];
			}
		}

		return $result;
	}

	private function buildWhereConds( $builder, $dataItem ) {
		if ( $dataItem == null ) {
			return;
		}

		$dataItemHandler = $this->store->getDataItemHandlerForDIType( $dataItem->getDIType() );
		$builder->where( $dataItemHandler->getWhereConds( $dataItem ) );
	}

}
