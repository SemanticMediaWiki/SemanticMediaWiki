<?php

namespace SMW\SQLStore\Lookup;

use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Shared keyset (cursor-based) pagination logic for property list lookups.
 *
 * @since 7.0
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
	 */
	private function applyCursorPagination( SelectQueryBuilder $queryBuilder, Database $db ): void {
		$cursorAfter = $this->requestOptions->getCursorAfter();
		$cursorBefore = $this->requestOptions->getCursorBefore();

		if ( $cursorAfter !== null ) {
			$sort = $this->resolveCursorSort( $cursorAfter );
			if ( $sort !== null ) {
				$queryBuilder->andWhere(
					'(smw_sort, smw_id) > (' .
					$db->addQuotes( $sort ) . ', ' . (int)$cursorAfter . ')'
				);
			}
			$queryBuilder->orderBy( [ 'smw_sort', 'smw_id' ], SelectQueryBuilder::SORT_ASC );
		} elseif ( $cursorBefore !== null ) {
			$sort = $this->resolveCursorSort( $cursorBefore );
			if ( $sort !== null ) {
				$queryBuilder->andWhere(
					'(smw_sort, smw_id) < (' .
					$db->addQuotes( $sort ) . ', ' . (int)$cursorBefore . ')'
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
