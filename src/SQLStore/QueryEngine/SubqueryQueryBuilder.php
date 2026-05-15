<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\MediaWiki\Connection\Database;

/**
 * Builds derived-table SQL for the SQLStore query engine, hoisting DISTINCT
 * and the inner LIMIT/ORDER BY into a subquery to avoid MariaDB picking
 * inefficient query plans when DISTINCT and ORDER BY are combined against
 * a wide outer projection.
 *
 * @note The builder consumes $root->from (the pre-assembled string-form
 * joins produced by QuerySegmentListProcessor). $root->fromTables and
 * $root->joinConditions are equivalent structured representations of the
 * same joins and are not consumed; callers using this builder do not need
 * to pass them separately.
 *
 * @note Current QueryEngine callers pass $outerWhere = ''. ID-table filters
 * from applyExtraWhereCondition land in $root->where and end up inside the
 * derived table, where t0.smw_iw resolves because the inner query joins
 * smw_object_ids AS t0 (ConditionBuilder always anchors the root segment
 * on SQLStore::ID_TABLE). The $outerWhere parameter is retained as an
 * extension point for future callers that want to keep $root->where clean
 * and apply ID-table filters on the outer query instead.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SubqueryQueryBuilder {

	private const OUTER_ALIAS = 'outer_q';
	private const INNER_ALIAS = 'inner_q';
	private const ID_TABLE = 'smw_object_ids';

	public function __construct( private readonly Database $connection ) {
	}

	/**
	 * @since 7.0.0
	 *
	 * @param QuerySegment $root
	 * @param array $sqlOptions Requires `LIMIT`; honours `OFFSET` and
	 *   `ORDER BY`. In cursor mode, `OFFSET` should be unset by the
	 *   caller (the keyset predicate or bootstrap ORDER BY replaces it).
	 * @param string $outerWhere Filters applied on the outer SELECT.
	 * @param string $cursorPredicate Phase 3c: raw SQL fragment for the
	 *   keyset WHERE clause, anchored at the inner segment's alias. ANDed
	 *   into the inner WHERE so the LIMIT'd subset is the cursor-anchored
	 *   slice. Empty for non-cursor queries AND for bootstrap cursors
	 *   (which carry no anchor yet); use `$cursorSortColumns` to detect
	 *   cursor mode itself.
	 * @param string[] $cursorSortColumns Phase 3c: ordered list of SQL
	 *   column expressions (e.g. `t0.smw_sort`, `t1.o_serialized`)
	 *   matching the keyset sort levels declared by the caller. Non-
	 *   empty array signals cursor mode; the builder emits
	 *   `cursor_sort_N` aliases (in this list's order) for the
	 *   QueryEngine row loop to mint the next anchor. Order is
	 *   independent of `$root->sortfields` iteration order so
	 *   multi-property queries align correctly with what
	 *   `$query->sortkeys` declared.
	 */
	public function buildInstanceQuerySQL(
		QuerySegment $root,
		array $sqlOptions,
		string $outerWhere,
		string $cursorPredicate = '',
		array $cursorSortColumns = []
	): string {
		if ( !isset( $sqlOptions['LIMIT'] ) ) {
			throw new \InvalidArgumentException( 'sqlOptions[\'LIMIT\'] is required' );
		}
		$outerLimit = (int)$sqlOptions['LIMIT'];
		$offset = (int)( $sqlOptions['OFFSET'] ?? 0 );
		$orderBy = $sqlOptions['ORDER BY'] ?? '';

		$sortfieldPairs = $this->buildSortfieldAliases( $root->sortfields );

		$innerSelect = $this->buildInnerSelect(
			$root,
			$sortfieldPairs,
			$orderBy,
			$this->innerLimit( $outerLimit ),
			$cursorPredicate,
			$cursorSortColumns
		);

		$outerProjection = $this->buildOuterProjection(
			$sortfieldPairs,
			$cursorSortColumns
		);
		$outerOrderBy = $this->rewriteOrderByForOuter(
			$orderBy,
			$sortfieldPairs,
			$root->alias
		);

		$idTable = $this->connection->tableName( self::ID_TABLE );
		$outer = self::OUTER_ALIAS;
		$inner = self::INNER_ALIAS;

		$sql = "SELECT $outerProjection "
			. "FROM $idTable AS $outer "
			. "INNER JOIN ($innerSelect) AS $inner "
			. "ON $outer.smw_id = $inner.s_id";

		if ( $outerWhere !== '' ) {
			$sql .= " WHERE $outerWhere";
		}

		if ( $outerOrderBy !== '' ) {
			$sql .= " ORDER BY $outerOrderBy";
		}

		$sql .= " LIMIT $outerLimit";

		if ( $offset > 0 ) {
			$sql .= " OFFSET $offset";
		}

		return $sql;
	}

	/**
	 * Builds COUNT(*) SQL for a query segment.
	 *
	 * $sqlOptions['LIMIT']/['OFFSET']/['ORDER BY'] are intentionally
	 * ignored — applying LIMIT or ORDER BY to a single-row aggregate is
	 * meaningless. The parameter is kept for signature parity with
	 * buildInstanceQuerySQL.
	 *
	 * @since 7.0.0
	 */
	public function buildCountQuerySQL(
		QuerySegment $root,
		array $sqlOptions,
		string $outerWhere
	): string {
		$idTable = $this->connection->tableName( self::ID_TABLE );
		$outer = self::OUTER_ALIAS;
		$inner = self::INNER_ALIAS;

		$innerSelect = "SELECT DISTINCT {$root->joinfield} AS s_id"
			. ' FROM ' . $this->connection->tableName( $root->joinTable ) . " AS {$root->alias}"
			. $root->from
			. ( $root->where !== '' ? " WHERE {$root->where}" : '' );

		$sql = "SELECT COUNT(*) AS count "
			. "FROM ($innerSelect) AS $inner "
			. "INNER JOIN $idTable AS $outer "
			. "ON $outer.smw_id = $inner.s_id";

		if ( $outerWhere !== '' ) {
			$sql .= " WHERE $outerWhere";
		}

		return $sql;
	}

	private function innerLimit( int $outerLimit ): int {
		return max( $outerLimit + 5, (int)ceil( $outerLimit * 1.2 ) + 10 );
	}

	/**
	 * Flatten compound sortfields (e.g. "t0.a,t0.b") into a list of
	 * (expression, alias) pairs. Each individual expression gets its own
	 * sf<n> alias regardless of which sortfields entry it came from.
	 *
	 * @param string[] $sortfields property-key => column expression(s),
	 *                              comma-separated for compound sorts
	 * @return array<int, array{expr: string, alias: string}>
	 */
	private function buildSortfieldAliases( array $sortfields ): array {
		$pairs = [];
		$i = 0;
		foreach ( $sortfields as $value ) {
			foreach ( explode( ',', $value ) as $expr ) {
				$expr = trim( $expr );
				if ( $expr === '' ) {
					continue;
				}
				$pairs[] = [ 'expr' => $expr, 'alias' => 'sf' . $i++ ];
			}
		}
		return $pairs;
	}

	/**
	 * @param QuerySegment $root
	 * @param array<int, array{expr: string, alias: string}> $sortfieldPairs
	 * @param string $orderBy
	 * @param int $innerLimit
	 * @param string $cursorPredicate Phase 3c: raw SQL fragment ANDed
	 *   into the inner WHERE. Anchored at the root segment's alias so
	 *   column refs resolve inside the derived table.
	 * @param string[] $cursorSortColumns Phase 3c: when non-empty, the
	 *   inner SELECT projects each as `cursor_sort_N` so the outer
	 *   query can surface anchor values in the order the engine row
	 *   loop expects (matches `$query->sortkeys` declaration order,
	 *   not `$root->sortfields` iteration order).
	 */
	private function buildInnerSelect(
		QuerySegment $root,
		array $sortfieldPairs,
		string $orderBy,
		int $innerLimit,
		string $cursorPredicate = '',
		array $cursorSortColumns = []
	): string {
		$columns = [ "{$root->joinfield} AS s_id" ];

		foreach ( $sortfieldPairs as [ 'expr' => $expr, 'alias' => $alias ] ) {
			$columns[] = "$expr AS $alias";
		}

		// Cursor anchor projections. These are deliberately decoupled
		// from `$sortfieldPairs` so multi-property queries with
		// divergent sortfield iteration vs sortkey order still emit
		// `cursor_sort_N` in the correct sequence.
		foreach ( $cursorSortColumns as $i => $col ) {
			$columns[] = "$col AS cursor_sort_$i";
		}

		// Combine existing WHERE with the cursor predicate. Both come
		// pre-formed; we just AND them. The predicate references the
		// inner segment's raw columns (e.g. `t0.smw_sort`, `t0.smw_id`),
		// which resolve inside the derived table because the inner FROM
		// aliases the root table as `t0`.
		$where = $root->where;
		if ( $cursorPredicate !== '' ) {
			$where = $where !== ''
				? "($where) AND ($cursorPredicate)"
				: $cursorPredicate;
		}

		$sql = 'SELECT DISTINCT ' . implode( ', ', $columns )
			. ' FROM ' . $this->connection->tableName( $root->joinTable ) . " AS {$root->alias}"
			. $root->from
			. ( $where !== '' ? " WHERE $where" : '' );

		if ( $orderBy !== '' ) {
			$sql .= " ORDER BY $orderBy";
		}

		$sql .= " LIMIT $innerLimit";

		return $sql;
	}

	/**
	 * @param array<int, array{expr: string, alias: string}> $sortfieldPairs
	 * @param string[] $cursorSortColumns Phase 3c: when non-empty, the
	 *   outer projection surfaces `cursor_sort_N` columns directly from
	 *   the inner SELECT, in this list's order. Independent of
	 *   `$sortfieldPairs` ordering.
	 */
	private function buildOuterProjection(
		array $sortfieldPairs,
		array $cursorSortColumns = []
	): string {
		$outer = self::OUTER_ALIAS;
		$inner = self::INNER_ALIAS;

		$columns = [
			"$outer.smw_id AS id",
			"$outer.smw_title AS t",
			"$outer.smw_namespace AS ns",
			"$outer.smw_iw AS iw",
			"$outer.smw_subobject AS so",
			"$outer.smw_sortkey AS sortkey",
		];

		// sortfield aliases (driven by `$sortfieldPairs`) feed the
		// outer ORDER BY's substitution from raw expression to inner
		// alias. They are independent of the cursor anchor projection.
		foreach ( $sortfieldPairs as $pair ) {
			$columns[] = "$inner.{$pair['alias']}";
		}

		// cursor_sort_N projections follow `$cursorSortColumns` order
		// (the engine's keyset sort-level ordering), which may differ
		// from `$root->sortfields` iteration order.
		foreach ( $cursorSortColumns as $i => $_col ) {
			$columns[] = "$inner.cursor_sort_$i";
		}

		return implode( ', ', $columns );
	}

	/**
	 * Rewrite an ORDER BY string built from compound and/or multiple
	 * sortfield expressions so each underlying expression is replaced
	 * with its inner-query alias.
	 *
	 * Substitutions are applied in descending order of expression length
	 * to prevent shorter expressions (e.g. "t0.smw_sort") from corrupting
	 * longer ones that share a prefix (e.g. "t0.smw_sortkey").
	 *
	 * Phase 3c: the cursor-mode ORDER BY also references
	 * `<rootAlias>.smw_id` (the tiebreak column). This rewrites that
	 * reference to `outer_q.smw_id`, which the outer query has direct
	 * access to via the JOIN on `smw_object_ids AS outer_q`.
	 *
	 * @param string $orderBy
	 * @param array<int, array{expr: string, alias: string}> $sortfieldPairs
	 * @param string $rootAlias Inner segment's table alias (e.g. `t0`),
	 *   used to rewrite the smw_id reference for cursor mode. Empty
	 *   string skips the smw_id rewrite.
	 */
	private function rewriteOrderByForOuter(
		string $orderBy,
		array $sortfieldPairs,
		string $rootAlias = ''
	): string {
		if ( $orderBy === '' ) {
			return $orderBy;
		}

		// Sort by descending expression length so longer expressions are
		// replaced first.
		usort(
			$sortfieldPairs,
			static fn ( array $a, array $b ) => strlen( $b['expr'] ) <=> strlen( $a['expr'] )
		);

		$rewritten = $orderBy;
		$inner = self::INNER_ALIAS;
		$outer = self::OUTER_ALIAS;

		foreach ( $sortfieldPairs as [ 'expr' => $expr, 'alias' => $alias ] ) {
			$rewritten = str_replace( $expr, "$inner.$alias", $rewritten );
		}

		// Cursor-mode `smw_id` tiebreak: maps from inner segment's
		// `<alias>.smw_id` to outer `outer_q.smw_id` (which is the
		// JOINed column the outer query has direct access to).
		if ( $rootAlias !== '' ) {
			$rewritten = str_replace(
				"$rootAlias.smw_id",
				"$outer.smw_id",
				$rewritten
			);
		}

		return $rewritten;
	}
}
