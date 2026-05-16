<?php

namespace SMW\SQLStore;

use InvalidArgumentException;
use SMW\MediaWiki\Connection\Database;

/**
 * Builds the explicit-OR keyset (cursor) pagination predicate. This is the
 * single source of the predicate form for both SQLStore cursor paths: the
 * `#ask` query engine and the lookup-based pagination behind the Browse API
 * and Special pages.
 *
 * For N sort levels `c_1 ... c_N` with anchor values `v_1 ... v_N`, a
 * tiebreak column `id_col` with anchor `id_val`, and per-level operators
 * `op_1 ... op_N` (`>` for ASC, `<` for DESC), the predicate is:
 *
 *     (c_1 op_1 v_1)
 *     OR (c_1 = v_1 AND c_2 op_2 v_2)
 *     OR ...
 *     OR (c_1 = v_1 AND ... AND c_N op_N v_N)
 *     OR (c_1 = v_1 AND ... AND c_N = v_N AND id_col op_tie id_val)
 *
 * Clause k matches rows tied with the anchor on the first k-1 levels and
 * strictly past it on level k; the final clause matches rows tied on every
 * sort level and past the anchor on the id tiebreak. Together the clauses
 * select exactly the rows ordered strictly after the anchor.
 *
 * MariaDB recognises this form as an index range seek when an index covers
 * the sort columns; the row-constructor form
 * `(c_1, ..., c_N, id_col) OP (v_1, ..., v_N, id_val)` plans a full index
 * scan instead. PostgreSQL and SQLite plan the OR form at least as well as
 * the tuple form. See issue #6559 and #6794.
 *
 * For N=1 the form collapses to the two-clause predicate used by the
 * single-column lookup pagination.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class KeysetPredicateBuilder {

	/**
	 * Build the explicit-OR keyset predicate as a raw SQL string.
	 *
	 * @since 7.0.0
	 *
	 * @param Database $connection Used only to quote anchor values.
	 * @param array<int, array{column: string, value: mixed, order: string}> $levels
	 *   Sort levels in major-to-minor order. `column` is a raw SQL column
	 *   expression, `value` the anchor value at that level, `order` either
	 *   "ASC" or "DESC" ("DESC" selects `<`, every other value selects
	 *   `>`). Must be non-empty.
	 * @param string $idColumn Raw SQL column expression for the id
	 *   tiebreak (the strict total-order column, in practice smw_id).
	 * @param int $idValue Anchor id, the tiebreak value of the cursor row.
	 * @param string $idOrder Tiebreak direction, "ASC" or "DESC" (same
	 *   mapping as a level's `order`). To keep the tiebreak consistent
	 *   with the ORDER BY, pass the last sort level's direction.
	 *
	 * @return string
	 */
	public static function build(
		Database $connection,
		array $levels,
		string $idColumn,
		int $idValue,
		string $idOrder
	): string {
		if ( $levels === [] ) {
			throw new InvalidArgumentException( '$levels must not be empty' );
		}

		$columns = [];
		$quotedValues = [];
		$ops = [];
		foreach ( $levels as $level ) {
			$columns[] = $level['column'];
			$quotedValues[] = $connection->addQuotes( $level['value'] );
			$ops[] = $level['order'] === 'DESC' ? '<' : '>';
		}
		$idOp = $idOrder === 'DESC' ? '<' : '>';
		$n = count( $columns );

		// Build the N+1 OR clauses. Clause k (0-indexed) equates the
		// first k levels to the anchor and applies the strict operator
		// on level k; the final clause equates all N levels and applies
		// the strict operator on the id tiebreak.
		$clauses = [];
		for ( $k = 0; $k < $n; $k++ ) {
			$parts = [];
			for ( $j = 0; $j < $k; $j++ ) {
				$parts[] = "$columns[$j] = $quotedValues[$j]";
			}
			$parts[] = "$columns[$k] $ops[$k] $quotedValues[$k]";
			$clauses[] = '(' . implode( ' AND ', $parts ) . ')';
		}
		$tiebreakParts = [];
		for ( $j = 0; $j < $n; $j++ ) {
			$tiebreakParts[] = "$columns[$j] = $quotedValues[$j]";
		}
		$tiebreakParts[] = "$idColumn $idOp $idValue";
		$clauses[] = '(' . implode( ' AND ', $tiebreakParts ) . ')';

		return implode( ' OR ', $clauses );
	}

}
