<?php

namespace SMW\SQLStore\Lookup;

use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
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
	 * Both cursor branches express the (smw_sort, smw_id) total order via an
	 * explicit OR rather than a row-constructor comparison such as
	 * `(smw_sort, smw_id) > (?, ?)`. MariaDB does not optimise row-constructor
	 * comparisons into an index range seek; it plans a full index scan with a
	 * WHERE filter, so cost grows linearly with cursor depth. The explicit-OR
	 * form is recognised as a range predicate and seeks the (smw_sort, smw_id)
	 * index directly. PostgreSQL and SQLite plan the OR form at least as well
	 * as the tuple form. See issue #6559.
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

		if ( $cursorAfter !== null ) {
			$sort = $this->resolveCursorSort( $cursorAfter );
			if ( $sort !== null ) {
				$quotedSort = $db->addQuotes( $sort );
				$queryBuilder->andWhere(
					'smw_sort > ' . $quotedSort .
					' OR (smw_sort = ' . $quotedSort . ' AND smw_id > ' . $cursorAfter . ')'
				);
			}
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], SelectQueryBuilder::SORT_ASC );
		} elseif ( $cursorBefore !== null ) {
			$sort = $this->resolveCursorSort( $cursorBefore );
			if ( $sort !== null ) {
				$quotedSort = $db->addQuotes( $sort );
				$queryBuilder->andWhere(
					'smw_sort < ' . $quotedSort .
					' OR (smw_sort = ' . $quotedSort . ' AND smw_id < ' . $cursorBefore . ')'
				);
			}
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], SelectQueryBuilder::SORT_DESC );
		} else {
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], SelectQueryBuilder::SORT_ASC );
			if ( $requestOptions->offset > 0 ) {
				$queryBuilder->offset( $requestOptions->offset );
			}
		}
	}

}
