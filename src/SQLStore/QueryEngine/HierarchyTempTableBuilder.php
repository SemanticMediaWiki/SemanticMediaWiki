<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class HierarchyTempTableBuilder {

	/**
	 * Cache of computed hierarchy queries for reuse ("catetgory/property value
	 * string" => "tablename").
	 *
	 * @var string[]
	 */
	private $hierarchyCache = [];

	/**
	 * Hierarchy-table definitions keyed by type (`'class'` / `'property'`),
	 * each entry shaped as `[ bareTableName, depth ]`. Names are stored
	 * bare; QueryBuilder consumers (insertSelect, newSelectQueryBuilder)
	 * apply the prefix internally.
	 */
	private array $tableDefinitions = [];

	/**
	 * @since 2.3
	 */
	public function __construct(
		private readonly Database $connection,
		private readonly TemporaryTableBuilder $temporaryTableBuilder,
	) {
	}

	/**
	 * @since 2.3
	 */
	public function emptyHierarchyCache(): void {
		$this->hierarchyCache = [];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getHierarchyCache(): array {
		return $this->hierarchyCache;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $tableDefinitions
	 */
	public function setTableDefinitions( array $tableDefinitions ): void {
		foreach ( $tableDefinitions as $key => $tableDefinition ) {
			$this->tableDefinitions[$key] = [
				$tableDefinition['table'],
				$tableDefinition['depth']
			];
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function getTableDefinitionByType( $type ) {
		if ( !isset( $this->tableDefinitions[$type] ) ) {
			throw new RuntimeException( "$type is unknown" );
		}

		return $this->tableDefinitions[$type];
	}

	/**
	 * @since 2.3
	 *
	 * @throws RuntimeException
	 */
	public function fillTempTable( string $type, string $tablename, string $valueComposite, ?int $depth = null ): void {
		$this->temporaryTableBuilder->create( $tablename );

		[ $smwtable, $d ] = $this->getTableDefinitionByType( $type );

		if ( $depth === null ) {
			$depth = $d;
		}

		if ( array_key_exists( $valueComposite, $this->hierarchyCache ) ) { // Just copy known result.

			// No IGNORE: the cache source is already deduped, matching legacy
			// SQL semantics (a plain INSERT...SELECT with no conflict clause).
			$this->connection->insertSelect(
				$tablename,
				$this->hierarchyCache[$valueComposite],
				[ 'id' => 'id' ],
				'*',
				__METHOD__,
				[],
				[],
				[]
			);

			return;
		}

		$this->buildTempTable( $tablename, $valueComposite, $smwtable, $depth );
	}

	/**
	 * Populate the already-created result table $tablename with the seed ids in
	 * $values plus their sub-elements (subcategories / subproperties) down to
	 * $depth levels, then record it in the per-request cache.
	 *
	 * Prefers a single recursive CTE; falls back to an iterative temp-table walk
	 * on databases without WITH RECURSIVE (MySQL < 8).
	 */
	private function buildTempTable( string $tablename, string $values, string $smwtable, int $depth ): void {
		if ( $this->connection->supportsRecursiveCommonTableExpressions() ) {
			$this->buildTempTableRecursively( $tablename, $values, $smwtable, $depth );
		} else {
			$this->buildTempTableIteratively( $tablename, $values, $smwtable, $depth );
		}

		$this->hierarchyCache[$values] = $tablename;
	}

	/**
	 * Hierarchy resolution via a recursive CTE: resolve the seed ids and their
	 * sub-elements (down to $depth levels) in a single recursive read, then
	 * insert them as literals into the result table $tablename.
	 *
	 * The recursion is run as a STANDALONE SELECT, not a coupled
	 * INSERT ... SELECT: under InnoDB REPEATABLE READ an INSERT ... SELECT takes
	 * shared next-key locks on the persistent $smwtable it reads, which would
	 * reintroduce the issue #4527 lock-wait contention against concurrent
	 * writers (rebuild / ChangePropagation). A standalone SELECT is a
	 * non-locking snapshot read, so the read/write split is preserved exactly as
	 * in the iterative fallback. The single recursive read still replaces the
	 * iterative path's per-level round-trips and scratch temp tables.
	 */
	private function buildTempTableRecursively( string $tablename, string $values, string $smwtable, int $depth ): void {
		$db = $this->connection;

		// Anchor members: the seed ids at recursion level 0. `$values` is a
		// comma-joined string of parenthesised single literals like
		// '(123),(456)'. buildIntegerCast() keeps the CTE id column type
		// consistent with the cast s_id of the recursive member across dialects
		// (MySQL/MariaDB SIGNED, SQLite/PostgreSQL INTEGER), which the stricter
		// engines require for a recursive CTE.
		$anchors = [];
		foreach ( explode( ',', $values ) as $value ) {
			// $value looks like '(123)'. Strip parens/spaces to get the int id.
			$id = (int)trim( $value, '() ' );
			$anchors[] = 'SELECT ' . $db->buildIntegerCast( (string)$id ) . ' AS id, 0 AS lvl';
		}

		// WITH RECURSIVE smw_cte (id, lvl) AS (
		//   <seed ids at lvl 0>
		//   UNION
		//   SELECT <cast t.s_id>, lvl + 1 FROM <smwtable> t JOIN smw_cte ON t.o_id = smw_cte.id
		//     WHERE lvl < <depth>
		// ) SELECT DISTINCT id FROM smw_cte
		// Termination is bounded by the depth cap (a cycle keeps producing new
		// rows at higher lvl until lvl reaches $depth); DISTINCT collapses a node
		// reached at several depths to a single id.
		$sql = 'WITH RECURSIVE smw_cte (id, lvl) AS ( '
			. implode( ' UNION ', $anchors )
			. ' UNION SELECT ' . $db->buildIntegerCast( 't.s_id' ) . ', smw_cte.lvl + 1 '
			. 'FROM ' . $db->tableName( $smwtable ) . ' AS t JOIN smw_cte ON t.o_id = smw_cte.id '
			. 'WHERE smw_cte.lvl < ' . $depth
			. ' ) SELECT DISTINCT id FROM smw_cte';

		$res = $db->query( $sql, __METHOD__, ISQLPlatform::QUERY_CHANGE_NONE );

		$ids = [];
		foreach ( $res as $row ) {
			$ids[] = (int)$row->id;
		}

		// Re-insert the resolved ids as literals into the result table, batched
		// to bound statement size (see buildTempTableIteratively).
		foreach ( array_chunk( $ids, 1000 ) as $chunk ) {
			$qb = $db->newInsertQueryBuilder()
				->insertInto( $tablename )
				->ignore()
				->caller( __METHOD__ );

			foreach ( $chunk as $id ) {
				$qb->row( [ 'id' => $id ] );
			}

			$qb->execute();
		}
	}

	/**
	 * Iterative hierarchy resolution for databases without recursive CTEs.
	 *
	 * @note we use two helper tables. One holds the results of each new iteration, one holds the
	 * results of the previous iteration. One could of course do with only the above result table,
	 * but then every iteration would use all elements of this table, while only the new ones
	 * obtained in the previous step are relevant. So this is a performance measure.
	 */
	private function buildTempTableIteratively( string $tablename, string $values, string $smwtable, int $depth ): void {
		$db = $this->connection;

		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$this->temporaryTableBuilder->create( $tmpnew );
		$this->temporaryTableBuilder->create( $tmpres );

		// Seed both temp tables with the supplied id values. `$values` is a
		// comma-joined string of parenthesised single literals like
		// '(123),(456)'. Use one builder per destination, accumulate rows
		// via row(), then a single execute() per builder. The IGNORE option
		// produces platform-correct INSERT IGNORE / OR IGNORE / ON CONFLICT
		// DO NOTHING automatically.
		$tablenameQb = $db->newInsertQueryBuilder()
			->insertInto( $tablename )
			->ignore()
			->caller( __METHOD__ );
		$tmpnewQb = $db->newInsertQueryBuilder()
			->insertInto( $tmpnew )
			->ignore()
			->caller( __METHOD__ );

		foreach ( explode( ',', $values ) as $value ) {
			// $value looks like '(123)'. Strip parens/spaces to get the int id.
			$id = (int)trim( $value, '() ' );
			$tablenameQb->row( [ 'id' => $id ] );
			$tmpnewQb->row( [ 'id' => $id ] );
		}

		$tablenameQb->execute();
		$tmpnewQb->execute();

		// Resolve the persistent source table's physical (prefix-applied) name
		// once. The temp-table names are resolved per iteration because $tmpnew
		// and $tmpres are swapped at the end of each pass.
		$smwtableName = $db->tableName( $smwtable );

		for ( $i = 0; $i < $depth; $i++ ) {
			// Read the next hierarchy frontier with a PLAIN, non-locking SELECT
			// (no FOR UPDATE) and re-insert the ids as literals. Splitting the
			// read from the write keeps the persistent $smwtable lock-free:
			// IDatabase::insertSelect() takes a locking SELECT ... FOR UPDATE on
			// the web-request path (and a shared-lock INSERT ... SELECT on the
			// native path), which under concurrent writes to $smwtable (rebuild /
			// ChangePropagation jobs) caused Error 1205 lock-wait timeouts
			// (issue #4527). A standalone SELECT is a non-locking snapshot read,
			// so it takes no locks on $smwtable on any path. It runs on the write
			// connection because $tmpnew is a session-local temporary table that
			// exists only there.
			$res = $db->query(
				"SELECT s_id FROM $smwtableName," . $db->tableName( $tmpnew ) . ' WHERE o_id=id',
				__METHOD__,
				ISQLPlatform::QUERY_CHANGE_NONE
			);

			$ids = [];
			foreach ( $res as $row ) {
				$ids[] = (int)$row->s_id;
			}

			// A child reachable from several parents appears once per parent;
			// dedupe so the literal INSERT below stays compact (INSERT IGNORE
			// would drop the duplicates anyway).
			$ids = array_unique( $ids );

			if ( $ids === [] ) { // no new ids, exit loop
				break;
			}

			// INSERT IGNORE the frontier ids into $tmpres, in bounded batches so
			// a very wide hierarchy level cannot produce an oversized statement
			// (max_allowed_packet) or an unbounded PHP buffer. The IGNORE option
			// produces platform-correct INSERT IGNORE / OR IGNORE / ON CONFLICT
			// DO NOTHING automatically.
			foreach ( array_chunk( $ids, 1000 ) as $chunk ) {
				$tmpresQb = $db->newInsertQueryBuilder()
					->insertInto( $tmpres )
					->ignore()
					->caller( __METHOD__ );

				foreach ( $chunk as $id ) {
					$tmpresQb->row( [ 'id' => $id ] );
				}

				$tmpresQb->execute();
			}

			// INSERT IGNORE INTO $tablename (id) SELECT $tmpres.id FROM $tmpres.
			// Both are temporary tables, so insertSelect()'s FOR UPDATE is
			// session-local and harmless here.
			$db->insertSelect(
				$tablename,
				$tmpres,
				[ 'id' => 'id' ],
				'*',
				__METHOD__,
				[ 'IGNORE' ],
				[],
				[]
			);

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$db->newDeleteQueryBuilder()
				->deleteFrom( $tmpnew )
				->where( IDatabase::ALL_ROWS )
				->caller( __METHOD__ )
				->execute();

			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		$this->temporaryTableBuilder->drop( $tmpnew );
		$this->temporaryTableBuilder->drop( $tmpres );
	}

}
