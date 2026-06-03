<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\DataItems\Container;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\IteratorFactory;
use SMW\MediaWiki\Connection\LegacyOptionsApplier;
use SMW\Options;
use SMW\RequestOptions;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\Lookup\KeysetPaginationTrait;
use SMW\SQLStore\PropertyTableDefinition as TableDefinition;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertySubjectsLookup {

	use KeysetPaginationTrait;

	/**
	 * Usage count at or below which the property-subjects join is never
	 * index-hinted. Preserves the historical behaviour on small wikis, where
	 * the table-relative threshold falls below this value.
	 */
	private const INDEX_HINT_USAGE_FLOOR = 5000;

	/**
	 * Multiplier for the table-size-relative index-hint threshold. The hint's
	 * break-even usage scales as sqrt( PAGE_FACTOR * entity count ); the value
	 * approximates a typical result-page size.
	 */
	private const INDEX_HINT_PAGE_FACTOR = 50;

	private IteratorFactory $iteratorFactory;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var DataItemHandler
	 */
	private $dataItemHandler;

	private array $prefetch = [];

	private string $caller = '';

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly SQLStore $store ) {
		$this->iteratorFactory = ApplicationFactory::getInstance()->getIteratorFactory();
	}

	/**
	 * @see Store::getPropertySubjects
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function fetchFromTable( $pid, TableDefinition $proptable, ?DataItem $dataItem = null, ?RequestOptions $requestOptions = null ) {
		$this->caller = __METHOD__;

		$res = $this->doFetch( $pid, $proptable, $dataItem, $requestOptions );

		// Return an iterator and avoid resolving the resources directly as it
		// may contain a large list of possible matches
		$res = $this->iteratorFactory->newMappingIterator(
			$this->iteratorFactory->newResultIterator( $res ),
			[ $this, 'newFromRow' ]
		);

		return $res;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $ids
	 * @param Property $property
	 * @param TableDefinition $proptable
	 * @param RequestOptions $requestOptions
	 */
	public function prefetchFromTable( array $ids, Property $property, TableDefinition $proptable, RequestOptions $requestOptions ) {
		if ( $ids === [] ) {
			return [];
		}

		$this->caller = __METHOD__;
		$hash = $property->getSerialization();

		if ( $requestOptions->getOption( RequestOptions::PREFETCH_FINGERPRINT ) === null ) {
			$hash .= implode( '|', $ids );
		}

		$hash = md5( $hash . $requestOptions->getHash() );

		if ( isset( $this->prefetch[$hash] ) ) {
			return $this->prefetch[$hash];
		}

		$pid = $this->store->getObjectIds()->getSMWPropertyID(
			$property
		);

		// Avoid any grouping when using the prefetch with a WHERE IN
		// Test: Q0624
		if ( $property->isInverse() ) {
			$requestOptions->setOption( 'NO_GROUPBY', true );
			$requestOptions->setOption( 'NO_DISTINCT', true );
		}

		$res = $this->doFetch( $pid, $proptable, $ids, $requestOptions );

		$resultLimiter = new ResultLimiter();
		$resultLimiter->calcSize( $requestOptions );

		$result = [];
		$warmupCache = [];

		// Reassign per ID
		foreach ( $res as $row ) {

			if ( $resultLimiter->canSkip( $row->id ) ) {
				continue;
			}

			if ( !isset( $result[$row->id] ) ) {
				$result[$row->id] = [];
			}

			$keys = [
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_sort,
				$row->smw_subobject

			];

			$dataItem = $this->dataItemHandler->dataItemFromDBKeys( $keys );
			$dataItem->setId( $row->smw_id );

			$result[$row->id][] = $dataItem;
			$warmupCache[] = $dataItem;
		}

		if ( $warmupCache !== [] ) {
			$this->store->getObjectIds()->warmupCache( $warmupCache );
		}

		$this->prefetch[$hash] = $result;
		return $this->prefetch[$hash];
	}

	private function doFetch( $pid, TableDefinition $proptable, DataItem|array|null $dataItem, ?RequestOptions $requestOptions = null ) {
		$connection = $this->store->getConnection( 'mw.db' );
		$group = false;

		$dataItemHandler = $this->store->getDataItemHandlerForDIType(
			$proptable->getDiType()
		);

		$sortField = $dataItemHandler->getSortField();

		// Capture the caller's RequestOptions so cursor metadata
		// (firstCursor, lastCursor, cursorHasMore) can be written back to it
		// after the fetch. The local working copy below is cloned for internal
		// mutation safety; the cursor branch needs to surface results on the
		// original.
		$callerRequestOptions = $requestOptions;
		$cursorMode = $proptable->usesIdSubject()
			&& $callerRequestOptions !== null
			&& (
				$callerRequestOptions->hasCursor()
				|| (bool)$callerRequestOptions->getOption( RequestOptions::CURSOR_MODE )
			);

		if ( $requestOptions === null ) {
			$requestOptions = new RequestOptions();
		} else {
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

		$qb = $connection->newSelectQueryBuilder();

		if ( $proptable->usesIdSubject() ) {
			$group = true;

			$qb->from( SQLStore::ID_TABLE )
				->join( $proptable->getName(), 't1', 't1.s_id=smw_id' )
				->select( [
					'smw_id',
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_sortkey',
					'smw_sort',
				] );

			// Preserve the FORCE INDEX(...) hint by translating to useIndex( [ 't1' => $name ] ).
			// This emits USE INDEX on MySQL (vs the original FORCE INDEX). Both restrict the
			// optimizer to the named index; on Postgres/SQLite both are no-ops. The semantic
			// difference is documented in the PR description.
			if ( $index !== '' && preg_match( '/^FORCE INDEX\(([^)]+)\)$/', $index, $m ) ) {
				$qb->useIndex( [ 't1' => $m[1] ] );
			}
		} else {
			// no join needed, title+namespace as given in proptable
			$qb->from( $proptable->getName(), 't1' )
				->select( [
					's_title AS smw_title',
					's_namespace AS smw_namespace',
					"'' AS smw_iw",
					"'' AS smw_subobject",
					's_title AS smw_sortkey',
					's_title AS smw_sort',
				] );

			$requestOptions->setOption( 'ORDER BY', false );
		}

		if ( !$proptable->isFixedPropertyTable() ) {
			$qb->where( [ 't1.p_id' => $pid ] );
		}

		// Specified by the prefetch
		if ( is_array( $dataItem ) ) {
			$fieldname = ( $proptable->getDiType() === DataItem::TYPE_WIKIPAGE ) ? 'o_id' : 's_id';
			$qb->andWhere( [ "t1.$fieldname" => $dataItem ] )
				->select( [ 'id' => "t1.$fieldname" ] );
		} else {
			$this->getWhereConds( $qb, $dataItem );
		}

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
			if ( isset( $extraCondition['o_id'] ) ) {
				$qb->andWhere( [ 't1.o_id' => $extraCondition['o_id'] ] );
			}

			if ( is_callable( $extraCondition ) ) {
				$extraCondition( $qb );
			}
		}

		// Avoid `getSQLConditions` to work on the condition
		$requestOptions->emptyExtraConditions();

		if ( $proptable->usesIdSubject() ) {
			foreach ( [ SMW_SQL3_SMWIW_OUTDATED, SMW_SQL3_SMWDELETEIW, SMW_SQL3_SMWREDIIW ] as $iw ) {
				$qb->andWhere( $connection->expr( 'smw_iw', '!=', $iw ) );
			}
		}

		if ( $requestOptions->getOption( 'NO_GROUPBY' ) ) {
			$group = false;
		}

		if ( $group && $connection->isType( 'postgres' ) ) {
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
		} elseif ( $requestOptions->getOption( 'NO_DISTINCT' ) ) {
			$requestOptions->setOption( 'DISTINCT', false );
		} else {
			$requestOptions->setOption( 'DISTINCT', true );
		}

		$cond = $this->store->getSQLConditions(
			$requestOptions,
			'smw_sortkey',
			'smw_sortkey',
			false
		);

		if ( $cond !== '' ) {
			$qb->andWhere( $cond );
		}

		$opts = $this->store->getSQLOptions(
			$requestOptions,
			$sortField
		);

		// Preserve Postgres-specific `DISTINCT ON (...)` (a string DISTINCT value),
		// which the legacy options array supports but `->distinct()` does not.
		if ( isset( $opts['DISTINCT'] ) && is_string( $opts['DISTINCT'] ) ) {
			$qb->option( 'DISTINCT', $opts['DISTINCT'] );
			unset( $opts['DISTINCT'] );
		}

		if ( $requestOptions->exclude_limit ) {
			unset( $opts['LIMIT'], $opts['OFFSET'] );
		}

		if ( $cursorMode ) {
			// Cursor path: the trait sets the WHERE predicate and ORDER BY;
			// we apply LIMIT+1 for lookahead and skip the legacy LIMIT/OFFSET.
			unset( $opts['LIMIT'], $opts['OFFSET'] );
			if ( $requestOptions->limit > 0 ) {
				$qb->limit( $requestOptions->limit + 1 );
			}
			$this->applyCursorPagination( $qb, $connection, $requestOptions );
		}

		LegacyOptionsApplier::applyTo( $qb, $opts );

		$caller = $this->caller;

		if ( strval( $requestOptions->getCaller() ) !== '' ) {
			$caller .= ' (for ' . $requestOptions->getCaller() . ')';
		}

		$res = $qb->caller( $caller )->fetchResultSet();

		$this->dataItemHandler = $this->store->getDataItemHandlerForDIType(
			DataItem::TYPE_WIKIPAGE
		);

		if ( $cursorMode && $callerRequestOptions !== null ) {
			$res = $this->postProcessCursorResult( $res, $callerRequestOptions, $requestOptions );
		}

		return $res;
	}

	/**
	 * Trim the lookahead row, reverse on backward navigation, and surface
	 * cursor metadata on the caller's RequestOptions so the page renderer
	 * can build the next/prev links.
	 *
	 * @param iterable $res Raw result rows from `fetchResultSet()`.
	 * @param RequestOptions $callerRequestOptions The caller's instance, which receives the cursor metadata.
	 * @param RequestOptions $workingRequestOptions The cloned/internal instance (used only for the original `cursorBefore`/`limit` values).
	 *
	 * @return array Post-processed rows, ready to be wrapped by the iterator factory.
	 */
	private function postProcessCursorResult( $res, RequestOptions $callerRequestOptions, RequestOptions $workingRequestOptions ): array {
		$rows = [];
		foreach ( $res as $row ) {
			$rows[] = $row;
		}

		$limit = $workingRequestOptions->limit;
		if ( $limit > 0 && count( $rows ) > $limit ) {
			array_pop( $rows );
			$callerRequestOptions->setCursorHasMore( true );
		}

		if ( $callerRequestOptions->getCursorBefore() !== null ) {
			$rows = array_reverse( $rows );
		}

		if ( $rows !== [] ) {
			$callerRequestOptions->setFirstCursor( (int)$rows[0]->smw_id );
			$callerRequestOptions->setLastCursor( (int)$rows[count( $rows ) - 1]->smw_id );
		}

		return $rows;
	}

	/**
	 * @since 3.0
	 *
	 * @param stdClass $row
	 *
	 * @return DataItem
	 */
	public function newFromRow( $row ) {
		try {
			if ( $row->smw_iw === '' || $row->smw_iw[0] != ':' ) { // filter special objects

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
		} catch ( DataItemHandlerException ) {
			// silently drop data, should be extremely rare and will usually fix itself at next edit
		}

		$title = ( $row->smw_title !== '' ? $row->smw_title : 'Empty' ) . '/' . $row->smw_namespace;

		// Avoid null return in Iterator
		return $this->dataItemHandler->dataItemFromDBKeys( [ 'Blankpage/' . $title, NS_SPECIAL, '', '', '' ] );
	}

	private function getWhereConds( SelectQueryBuilder $qb, ?DataItem $dataItem ): void {
		if ( $dataItem instanceof Container ) {
			throw new RuntimeException( '\SMW\DataItems\Container support is missing!' );
		}

		if ( $dataItem === null ) {
			return;
		}

		$dataItemHandler = $this->store->getDataItemHandlerForDIType(
			$dataItem->getDIType()
		);

		foreach ( $dataItemHandler->getWhereConds( $dataItem ) as $fieldname => $value ) {
			$qb->andWhere( [ "t1.$fieldname" => $value ] );
		}
	}

	private function getIndexHint( $dataItemHandler, $pid, DataItem|array|null $dataItem ): string {
		$index = '';

		if ( $dataItem !== null || $dataItemHandler->getIndexHint( $dataItemHandler::IHINT_PSUBJECTS ) === '' ) {
			return $index;
		}

		// Forcing the join index makes the planner drive the id table in
		// `smw_sort` order and stop once the page is filled, which avoids a
		// filesort over a large match set. That only pays off when the property
		// is used by enough subjects that the ordered scan reaches a full page
		// quickly; for a sparse property the same scan walks most of the id
		// table before finding matches and is far slower than letting the
		// planner read the property rows by `p_id` and sort them.
		//
		// The break-even usage is not a constant: it grows with the size of the
		// id table (roughly its square root), because a sparser slice of a
		// larger table must be scanned further before a page is filled. A fixed
		// threshold therefore mis-fires on large wikis by hinting properties
		// that are numerous in absolute terms yet sparse relative to the table
		// (issue 6559). The threshold below scales with the table size and keeps
		// the historical value as a floor, so behaviour on small wikis is
		// unchanged.
		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->newSelectQueryBuilder()
			->select( [ 'usage_count' ] )
			->from( SQLStore::PROPERTY_STATISTICS_TABLE )
			->where( [ 'p_id' => $pid ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row === false || $row->usage_count <= self::INDEX_HINT_USAGE_FLOOR ) {
			return $index;
		}

		$totalEntities = (int)$connection->newSelectQueryBuilder()
			->field( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField();

		$threshold = max(
			self::INDEX_HINT_USAGE_FLOOR,
			(int)sqrt( self::INDEX_HINT_PAGE_FACTOR * $totalEntities )
		);

		if ( $row->usage_count > $threshold ) {
			$index = 'FORCE INDEX(' . $dataItemHandler->getIndexHint( $dataItemHandler::IHINT_PSUBJECTS ) . ')';
		}

		return $index;
	}

}
