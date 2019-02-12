<?php

namespace SMW\SQLStore\EntityStore;

use SMW\SQLStore\SQLStore;
use SMW\SQLStore\PropertyTableDefinition as TableDefinition;
use SMWDataItem as DataItem;
use SMW\DIContainer;
use SMW\RequestOptions;
use SMW\Options;
use SMW\MediaWiki\DatabaseHelper;
use SMW\ApplicationFactory;
use SMW\SQLStore\RequestOptionsProc;
use RuntimeException;

/**
 * @license GNU GPL v2
 * @since 3.0
 *
 * @author mwjames
 */
class PropertySubjectsLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var DataItemHandler
	 */
	private $dataItemHandler;

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->iteratorFactory = ApplicationFactory::getInstance()->getIteratorFactory();
	}

	/**
	 * @see Store::getPropertySubjects
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function fetchFromTable( $pid, TableDefinition $proptable, DataItem $dataItem = null, RequestOptions $requestOptions = null ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$group = false;

		$dataItemHandler = $this->store->getDataItemHandlerForDIType(
			$proptable->getDiType()
		);

		$sortField = $dataItemHandler->getSortField();
		$query = $connection->newQuery();
		$query->type( 'SELECT' );

		if ( $requestOptions === null ) {
			$requestOptions = new RequestOptions();
		} else{
			// Clone a `RequestOptions` instance so that it can be modified freely
			// for the current request without a possible interference on an
			// upcoming request (as in case where it is called from within a loop
			// with the same initial RequestOptions instance)
			$requestOptions = clone $requestOptions;
		}

		if ( $sortField === '' ) {
			$sortField = 'smw_sort';
		}

		// For certain tables (blob) the query planner chooses a suboptimal plan
		// and causes an unacceptable query time therefore force an index for
		// those tables where the behaviour has been observed.
		$index = $this->getIndexHint( $dataItemHandler, $pid, $dataItem );
		$result = [];

		if ( $proptable->usesIdSubject() ) {
			$group = true;

			$query->table( SQLStore::ID_TABLE );

			$query->join(
				'INNER JOIN',
				[ $proptable->getName() => "t1 $index ON t1.s_id=smw_id" ]
			);

			$query->fields(
				[
					'smw_id',
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_sortkey',
					'smw_sort'
				]
			);

		} else { // no join needed, title+namespace as given in proptable
			$query->table( $proptable->getName(), "t1" );

			$query->fields(
				[
					's_title AS smw_title',
					's_namespace AS smw_namespace',
					'\'\' AS smw_iw',
					'\'\' AS smw_subobject',
					's_title AS smw_sortkey',
					's_title AS smw_sort'
				]
			);

			$requestOptions->setOption( 'ORDER BY', false );
		}

		if ( !$proptable->isFixedPropertyTable() ) {
			$query->condition( $query->eq( "t1.p_id", $pid ) );
		}

		$this->getWhereConds( $query, $dataItem );

		if ( $requestOptions !== null ) {
			foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
				if ( isset( $extraCondition['o_id'] ) ) {
					$query->condition( $query->eq( 't1.o_id', $extraCondition['o_id'] ) );
				}

				if ( is_callable( $extraCondition ) ) {
					$extraCondition( $query );
				}
			}

			// Avoid `getSQLConditions` to work on the condition
			$requestOptions->emptyExtraConditions();
		}

		if ( $proptable->usesIdSubject() ) {
			foreach ( [ SMW_SQL3_SMWIW_OUTDATED, SMW_SQL3_SMWDELETEIW, SMW_SQL3_SMWREDIIW ] as $v ) {
				$query->condition( $query->neq( "smw_iw", $v ) );
			}
		}

		if ( $group && $connection->isType( 'postgres') ) {
			// Avoid a "... 42803 ERROR:  column "s....smw_title" must appear in
			// the GROUP BY clause or be used in an aggregate function ..."
			// https://stackoverflow.com/questions/1769361/postgresql-group-by-different-from-mysql
			$requestOptions->setOption( 'DISTINCT', 'ON (smw_sort, smw_id)' );
			$requestOptions->setOption( 'ORDER BY', false );
		} elseif ( $group ) {
			// Using GROUP BY will sort on the field and since we disinguish smw_sort
			// and the ID at the end of the field, we ensure
			// the filter duplicates while sorting the list without using DISTINCT which
			// would cause a filesort
			// http://www.mysqltutorial.org/mysql-distinct.aspx
			$requestOptions->setOption( 'GROUP BY', $sortField . ', smw_id' );
			$requestOptions->setOption( 'ORDER BY', false );
		} else {
			$requestOptions->setOption( 'DISTINCT', true );
		}

		$cond = $this->store->getSQLConditions(
			$requestOptions,
			'smw_sortkey',
			'smw_sortkey',
			false
		);

		$query->condition( $cond );

		$opts = $this->store->getSQLOptions(
			$requestOptions,
			$sortField
		);

		$query->options( $opts );

		$res = $connection->query(
			$query,
			__METHOD__
		);

		$this->dataItemHandler = $this->store->getDataItemHandlerForDIType(
			DataItem::TYPE_WIKIPAGE
		);

		// Return an iterator and avoid resolving the resources directly as it
		// may contain a large list of possible matches
		$res = $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $res ),
			[ $this, 'newFromRow' ]
		);

		return $res;
	}

	/**
	 * @since 3.0
	 *
	 * @param stdClass $row
	 *
	 * @return DIWikiPage
	 */
	public function newFromRow( $row ) {

		try {
			if ( $row->smw_iw === '' || $row->smw_iw{0} != ':' ) { // filter special objects

				$keys = [
					$row->smw_title,
					$row->smw_namespace,
					$row->smw_iw,
					$row->smw_sort,
					$row->smw_subobject

				];

				$dataItem = $this->dataItemHandler->dataItemFromDBKeys( $keys );

				if ( isset( $row->smw_id ) ) {
					$dataItem->setId( $row->smw_id );
				}

				return $dataItem;
			}
		} catch ( DataItemHandlerException $e ) {
			// silently drop data, should be extremely rare and will usually fix itself at next edit
		}

		$title = ( $row->smw_title !== '' ? $row->smw_title : 'Empty' ) . '/' . $row->smw_namespace;

		// Avoid null return in Iterator
		return $this->dataItemHandler->dataItemFromDBKeys( [ 'Blankpage/' . $title, NS_SPECIAL, '', '', '' ] );
	}

	private function getWhereConds( $query, $dataItem ) {

		$conds = '';

		if ( $dataItem instanceof \SMWDIContainer ) {
			throw new RuntimeException( 'SMWDIContainer support is missing!');
		}

		if ( $dataItem !== null ) {
			$dataItemHandler = $this->store->getDataItemHandlerForDIType(
				$dataItem->getDIType()
			);

			foreach ( $dataItemHandler->getWhereConds( $dataItem ) as $fieldname => $value ) {
				$query->condition( $query->eq( "t1.$fieldname", $value ) );
			}
		}
	}

	private function getIndexHint( $dataItemHandler, $pid, $dataItem = null ) {

		$index = '';

		if ( $dataItem !== null || $dataItemHandler->getIndexHint( $dataItemHandler::IHINT_PSUBJECTS ) === '' ) {
			return $index;
		}

		// For tables with only a few entries, the index hint seems to create
		// a disadvantage, yet when the amount reaches a certain level the
		// index hint becomes necessary to retain an acceptable response
		// time.
		//
		// Table with < 100 entries
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids` INNER JOIN `smw_di_number` AS t1 ON t1.s_id=smw_id
		// WHERE (t1.p_id='196959') AND (smw_iw!=':smw') AND (smw_iw!=':smw-delete') AND (smw_iw!=':smw-redi')
		// GROUP BY smw_sort, smw_id LIMIT 21	8.2510ms (without index hint)
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids` INNER JOIN `smw_di_number` AS t1 FORCE INDEX(s_id) ON t1.s_id=smw_id
		// WHERE (t1.p_id='196959') AND (smw_iw!=':smw') AND (smw_iw!=':smw-delete') AND (smw_iw!=':smw-redi')
		// GROUP BY smw_sort, smw_id LIMIT 21	7548.6171ms (with index hint)
		//
		// vs.
		//
		// Table with > 5000 entries
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids` INNER JOIN `smw_di_blob` AS t1 FORCE INDEX(s_id) ON t1.s_id=smw_id
		// WHERE (t1.p_id='310170') AND (smw_iw!=':smw') AND (smw_iw!=':smw-delete') AND (smw_iw!=':smw-redi')
		// GROUP BY smw_sort, smw_id LIMIT 21	62.6249ms (with index hint)
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids` INNER JOIN `smw_di_blob` AS t1 ON t1.s_id=smw_id
		// WHERE (t1.p_id='310170') AND (smw_iw!=':smw') AND (smw_iw!=':smw-delete') AND (smw_iw!=':smw-redi')
		// GROUP BY smw_sort, smw_id LIMIT 21	8856.1242ms (without index hint)
		//
		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[ 'usage_count' ],
			[ 'p_id' => $pid ],
			__METHOD__
		);

		// 5000? It just showed to be a sweet spot while doing some
		// exploratory queries
		if ( $row !== false && $row->usage_count > 5000 ) {
			$index = 'FORCE INDEX(' . $dataItemHandler->getIndexHint( $dataItemHandler::IHINT_PSUBJECTS ) . ')';
		}

		return $index;
	}

}
