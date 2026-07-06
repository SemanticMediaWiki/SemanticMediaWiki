<?php

namespace SMW\SQLStore\Lookup;

use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\KeysetPredicateBuilder;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Shared keyset (cursor-based) pagination logic for paginated lookups.
 *
 * Consumers must expose `$this->store` (used to resolve cursor sort keys).
 * `RequestOptions` is passed explicitly to `applyCursorPagination()` rather
 * than read from a field, so any class with a store reference (Lookup,
 * SpecialPage, or otherwise) can use the trait without surfacing the
 * options instance as a field.
 *
 * The trait only emits the cursor WHERE predicate and ORDER BY (and falls
 * back to OFFSET when no cursor is active). Populating cursor metadata
 * (`firstCursor`, `lastCursor`, `cursorHasMore`) on the caller's
 * `RequestOptions` after `fetchResultSet()` is the consumer's
 * responsibility, since only the consumer knows how to trim the lookahead
 * row and reverse on backward navigation.
 *
 * @since 7.0
 *
 * @property SQLStore $store
 */
trait KeysetPaginationTrait {

	/**
	 * Look up the smw_sort value for a given cursor ID.
	 */
	private function resolveCursorSort( int $cursorId ): ?string {
		$db = $this->store->getConnection( 'mw.db' );

		$row = $db->newSelectQueryBuilder()
			->from( SQLStore::ID_TABLE )
			->field( 'smw_sort' )
			->where( [ 'smw_id' => $cursorId ] )
			->caller( __METHOD__ )
			->fetchRow();

		return $row ? $row->smw_sort : null;
	}

	/**
	 * Apply cursor-based WHERE conditions and ORDER BY to a query builder.
	 *
	 * When no cursor is active, falls back to offset-based pagination.
	 *
	 * The (smw_sort, smw_id) keyset predicate is built by
	 * `KeysetPredicateBuilder` (shared with the `#ask` query engine); see
	 * that class for the predicate form and why the explicit-OR shape is
	 * used over a row-constructor comparison.
	 *
	 * The cursor direction (after/before) and the sort direction
	 * (`$requestOptions->ascending`) compose independently. "After" always
	 * means the next page in display order: when ascending, that page
	 * holds values larger than the cursor; when descending, smaller.
	 * "Before" inverts the predicate and is served in reverse of the
	 * display order, so the consumer can `array_reverse()` it for display.
	 *
	 * @param SelectQueryBuilder $queryBuilder
	 * @param Database $db
	 * @param RequestOptions $requestOptions
	 *
	 * @return void
	 */
	private function applyCursorPagination(
		SelectQueryBuilder $queryBuilder,
		Database $db,
		RequestOptions $requestOptions
	): void {
		$cursorAfter = $requestOptions->getCursorAfter();
		$cursorBefore = $requestOptions->getCursorBefore();
		$ascending = $requestOptions->ascending;

		$displayOrder = $ascending ? SelectQueryBuilder::SORT_ASC : SelectQueryBuilder::SORT_DESC;
		$reverseOrder = $ascending ? SelectQueryBuilder::SORT_DESC : SelectQueryBuilder::SORT_ASC;
		// "Forward" walks the display order: larger values in ASC,
		// smaller in DESC. "Backward" (the before-cursor) walks the
		// inverse, so the predicate seeks with the opposite direction.
		$forwardDir = $ascending ? 'ASC' : 'DESC';
		$backwardDir = $ascending ? 'DESC' : 'ASC';

		if ( $cursorAfter !== null ) {
			$sort = $this->resolveCursorSort( $cursorAfter );
			if ( $sort !== null ) {
				$queryBuilder->andWhere( KeysetPredicateBuilder::build(
					$db,
					[ [ 'column' => 'smw_sort', 'value' => $sort, 'order' => $forwardDir ] ],
					'smw_id',
					$cursorAfter,
					$forwardDir
				) );
			}
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], $displayOrder );
		} elseif ( $cursorBefore !== null ) {
			$sort = $this->resolveCursorSort( $cursorBefore );
			if ( $sort !== null ) {
				$queryBuilder->andWhere( KeysetPredicateBuilder::build(
					$db,
					[ [ 'column' => 'smw_sort', 'value' => $sort, 'order' => $backwardDir ] ],
					'smw_id',
					$cursorBefore,
					$backwardDir
				) );
			}
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], $reverseOrder );
		} else {
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], $displayOrder );
			if ( $requestOptions->offset > 0 ) {
				$queryBuilder->offset( $requestOptions->offset );
			}
		}
	}

}
