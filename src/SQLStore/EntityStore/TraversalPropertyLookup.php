<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\DIContainer;
use SMW\MediaWiki\Connection\OptionsBuilder;
use SMW\Options;
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
	public function fetchFromTable( PropertyTableDef $propertyTableDef, DataItem $dataItem, RequestOptions $requestOptions = null ) {

		$connection = $this->store->getConnection( 'mw.db' );

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

			$cond = $this->getWhereConds( $dataItem );
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

			$opt = OptionsBuilder::toString( $options );

			$cond = ( $cond !== '' ? ' WHERE ' : '' ) . $cond;

			// Use a subquery to match all possible IDs, no ORDER BY or DISTINCT to avoid
			// a filesort
			$from = $connection->tableName( SQLStore::ID_TABLE ) .
				" INNER JOIN (" .
				" SELECT p_id FROM " . $connection->tableName( $propertyTableDef->getName() ) .
				" $cond $opt ) AS t1 ON t1.p_id=smw_id";

			$conditions .= ( $conditions ? ' AND ' : ' ' ) .
				" smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) .
				" AND smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW );

			$conditions .= $this->store->getSQLConditions( $subOptions, 'smw_sortkey', 'smw_sortkey', $conditions !== '' );

			$options = $this->store->getSQLOptions( $subOptions, '' ) + [ 'DISTINCT' ];

			$result = $connection->select(
				$from,
				' smw_title,smw_sortkey,smw_iw',
				$conditions,
				__METHOD__,
				$options
			);

		} else {
			$from = $connection->tableName( $propertyTableDef->getName() ) . " AS t1";
			$where = $this->getWhereConds( $dataItem );
			$fields = $propertyTableDef->usesIdSubject() ? 's_id' : '*';

			$result = $connection->select(
				$from,
				$fields,
				$where,
				__METHOD__,
				[ 'LIMIT' => 1 ]
			);

			if ( $result->numRows() > 0 ) {
				$res = new \stdClass;
				$res->smw_title = $propertyTableDef->getFixedProperty();
				$result = [ $res ];
			}
		}

		return $result;
	}

	private function getWhereConds( $dataItem ) {

		$where = '';
		$connection = $this->store->getConnection( 'mw.db' );

		if ( $dataItem !== null ) {
			$dataItemHandler = $this->store->getDataItemHandlerForDIType( $dataItem->getDIType() );
			foreach ( $dataItemHandler->getWhereConds( $dataItem ) as $fieldname => $value ) {
				$where .= ( $where ? ' AND ' : '' ) . "$fieldname=" . $connection->addQuotes( $value );
			}
		}

		return $where;
	}

}
