<?php

namespace SMW\SQLStore\Lookup;

use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Shared keyset (cursor-based) pagination logic for property list lookups.
 *
 * @since 7.0
 *
 * @property SQLStore $store
 * @property RequestOptions $requestOptions
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
	 * @param SelectQueryBuilder $queryBuilder
	 * @param Database $db
	 *
	 * @return void
	 */
	private function applyCursorPagination( SelectQueryBuilder $queryBuilder, Database $db ): void {
		$cursorAfter = $this->requestOptions->getCursorAfter();
		$cursorBefore = $this->requestOptions->getCursorBefore();

		if ( $cursorAfter !== null ) {
			$sort = $this->resolveCursorSort( $cursorAfter );
			if ( $sort !== null ) {
				// MariaDB does not optimise row-constructor comparisons
				// `(a, b) > (?, ?)` into an index range seek; it falls back
				// to a full index scan with a WHERE filter. Expressing the
				// same total order via explicit OR is recognised as a
				// range predicate and seeks the (smw_sort, smw_id) index
				// directly. See issue #6559.
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
			if ( $this->requestOptions->offset > 0 ) {
				$queryBuilder->offset( $this->requestOptions->offset );
			}
		}
	}

}
