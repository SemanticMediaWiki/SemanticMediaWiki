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
	 */
	public function buildInstanceQuerySQL(
		QuerySegment $root,
		array $sqlOptions,
		string $outerWhere
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
			$this->innerLimit( $outerLimit )
		);

		$outerProjection = $this->buildOuterProjection( $sortfieldPairs );
		$outerOrderBy = $this->rewriteOrderByForOuter(
			$orderBy,
			$sortfieldPairs
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
	 */
	private function buildInnerSelect(
		QuerySegment $root,
		array $sortfieldPairs,
		string $orderBy,
		int $innerLimit
	): string {
		$columns = [ "{$root->joinfield} AS s_id" ];

		foreach ( $sortfieldPairs as [ 'expr' => $expr, 'alias' => $alias ] ) {
			$columns[] = "$expr AS $alias";
		}

		$sql = 'SELECT DISTINCT ' . implode( ', ', $columns )
			. ' FROM ' . $this->connection->tableName( $root->joinTable ) . " AS {$root->alias}"
			. $root->from
			. ( $root->where !== '' ? " WHERE {$root->where}" : '' );

		if ( $orderBy !== '' ) {
			$sql .= " ORDER BY $orderBy";
		}

		$sql .= " LIMIT $innerLimit";

		return $sql;
	}

	/**
	 * @param array<int, array{expr: string, alias: string}> $sortfieldPairs
	 */
	private function buildOuterProjection( array $sortfieldPairs ): string {
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

		foreach ( $sortfieldPairs as $pair ) {
			$columns[] = "$inner.{$pair['alias']}";
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
	 * @param string $orderBy
	 * @param array<int, array{expr: string, alias: string}> $sortfieldPairs
	 */
	private function rewriteOrderByForOuter(
		string $orderBy,
		array $sortfieldPairs
	): string {
		if ( $orderBy === '' || $sortfieldPairs === [] ) {
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

		foreach ( $sortfieldPairs as [ 'expr' => $expr, 'alias' => $alias ] ) {
			$rewritten = str_replace( $expr, "$inner.$alias", $rewritten );
		}

		return $rewritten;
	}
}
