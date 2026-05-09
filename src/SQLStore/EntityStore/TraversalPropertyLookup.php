<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\DataItems\Container;
use SMW\DataItems\DataItem;
use SMW\MediaWiki\Connection\LegacyOptionsApplier;
use SMW\MediaWiki\Connection\OptionsBuilder;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyTableDefinition as PropertyTableDef;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\Subquery;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TraversalPropertyLookup {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly SQLStore $store ) {
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

		if ( $dataItem instanceof Container ) {
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

			// No sorting
			$subQueryOptions = $this->store->getSQLOptions( $subOptions, '' );

			// Avoid any limit or offset for the sub-query in order to find all
			// incoming properties
			unset( $subQueryOptions['LIMIT'] );
			unset( $subQueryOptions['OFFSET'] );

			// Ensure to group same IDs to reduce the amount of data transferred
			// from the inner join
			$subQueryOptions['GROUP BY'] = 'p_id';

			$opt = OptionsBuilder::toString( $subQueryOptions );

			$cond = ( $cond !== '' ? ' WHERE ' : '' ) . $cond;

			// Use a subquery to match all possible IDs, no ORDER BY or DISTINCT to avoid
			// a filesort
			$subquery = new Subquery(
				'SELECT p_id FROM ' . $connection->tableName( $propertyTableDef->getName() ) . " $cond $opt"
			);

			$conditions = ' ' .
				" smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) .
				" AND smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW );

			$conditions .= $this->store->getSQLConditions( $subOptions, 'smw_sortkey', 'smw_sortkey', $conditions !== '' );

			$outerOptions = $this->store->getSQLOptions( $subOptions, '' ) + [ 'DISTINCT' ];

			$qb = $connection->newSelectQueryBuilder()
				->rawTables( [ SQLStore::ID_TABLE, 't1' => $subquery ] )
				->joinConds( [ 't1' => [ 'INNER JOIN', 't1.p_id=smw_id' ] ] )
				->select( [ 'smw_title', 'smw_sortkey', 'smw_iw' ] );

			if ( trim( $conditions ) !== '' ) {
				$qb->andWhere( $conditions );
			}

			LegacyOptionsApplier::applyTo( $qb, $outerOptions );

			$result = $qb->caller( __METHOD__ )->fetchResultSet();

		} else {
			$where = $this->getWhereConds( $dataItem );
			$fields = $propertyTableDef->usesIdSubject() ? 's_id' : '*';

			$qb = $connection->newSelectQueryBuilder()
				->from( $propertyTableDef->getName(), 't1' )
				->select( $fields )
				->limit( 1 );

			if ( $where !== '' ) {
				$qb->where( $where );
			}

			$result = $qb->caller( __METHOD__ )->fetchResultSet();

			if ( $result->numRows() > 0 ) {
				$res = new stdClass;
				$res->smw_title = $propertyTableDef->getFixedProperty();
				$result = [ $res ];
			}
		}

		return $result;
	}

	private function getWhereConds( DataItem $dataItem ): string {
		$where = '';
		$connection = $this->store->getConnection( 'mw.db' );

		$dataItemHandler = $this->store->getDataItemHandlerForDIType( $dataItem->getDIType() );
		foreach ( $dataItemHandler->getWhereConds( $dataItem ) as $fieldname => $value ) {
			$where .= ( $where ? ' AND ' : '' ) . "$fieldname=" . $connection->addQuotes( $value );
		}

		return $where;
	}

}
