<?php

namespace SMW\MediaWiki\Connection;

use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Translates a legacy SQL-options array (as produced by
 * `Store::getSQLOptions()` and similar callers) into the equivalent
 * `SelectQueryBuilder` method calls.
 *
 * This is a transitional helper used by callsites being migrated from the
 * legacy `IDatabase::select( ..., $options )` API onto the Rdbms
 * `SelectQueryBuilder` fluent API. Once every callsite has been migrated to
 * pass typed parameters directly, this class can be removed alongside its
 * sibling `OptionsBuilder`.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class LegacyOptionsApplier {

	/**
	 * Recognised keys (matching MediaWiki's legacy `select()` options array):
	 * - `LIMIT`        (int)            → `->limit()`
	 * - `OFFSET`       (int)            → `->offset()`
	 * - `ORDER BY`     (string|array)   → `->orderBy()`
	 * - `GROUP BY`     (string|array)   → `->groupBy()`
	 * - `HAVING`       (string|array)   → `->having()`
	 * - `DISTINCT`     (truthy or numeric-keyed `'DISTINCT'`) → `->distinct()`
	 *
	 * Numeric-keyed entries (e.g. `[ 'DISTINCT' ]`) are recognised in addition
	 * to string-keyed ones, matching the MW Rdbms convention.
	 *
	 * Unknown keys are ignored — callsites are expected to apply any
	 * non-portable option directly via a builder method.
	 *
	 * @since 7.0.0
	 */
	public static function applyTo( SelectQueryBuilder $queryBuilder, array $options ): void {
		if ( isset( $options['LIMIT'] ) ) {
			$queryBuilder->limit( (int)$options['LIMIT'] );
		}

		if ( isset( $options['OFFSET'] ) ) {
			$queryBuilder->offset( (int)$options['OFFSET'] );
		}

		if ( isset( $options['ORDER BY'] ) ) {
			$queryBuilder->orderBy( $options['ORDER BY'] );
		}

		if ( isset( $options['GROUP BY'] ) ) {
			$queryBuilder->groupBy( $options['GROUP BY'] );
		}

		if ( isset( $options['HAVING'] ) ) {
			$queryBuilder->having( $options['HAVING'] );
		}

		if ( !empty( $options['DISTINCT'] ) || in_array( 'DISTINCT', $options, true ) ) {
			$queryBuilder->distinct();
		}
	}
}
