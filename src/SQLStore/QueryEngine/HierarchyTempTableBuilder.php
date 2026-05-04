<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
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
				$this->connection->tableName( $tableDefinition['table'] ),
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
	 * @param string $type
	 * @param string $tablename
	 * @param string $valueComposite
	 * @param int|null $depth
	 *
	 * @throws RuntimeException
	 */
	public function fillTempTable( $type, $tablename, $valueComposite, $depth = null ): void {
		$this->temporaryTableBuilder->create( $tablename );

		[ $smwtable, $d ] = $this->getTableDefinitionByType( $type );

		if ( $depth === null ) {
			$depth = $d;
		}

		if ( array_key_exists( $valueComposite, $this->hierarchyCache ) ) { // Just copy known result.

			$this->connection->query(
				"INSERT INTO $tablename (id) SELECT id" . ' FROM ' . $this->hierarchyCache[$valueComposite],
				__METHOD__,
				ISQLPlatform::QUERY_CHANGE_ROWS
			);

			return;
		}

		$this->buildTempTable( $tablename, $valueComposite, $smwtable, $depth );
	}

	/**
	 * @note we use two helper tables. One holds the results of each new iteration, one holds the
	 * results of the previous iteration. One could of course do with only the above result table,
	 * but then every iteration would use all elements of this table, while only the new ones
	 * obtained in the previous step are relevant. So this is a performance measure.
	 */
	private function buildTempTable( $tablename, $values, $smwtable, $depth ): void {
		$db = $this->connection;

		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$this->temporaryTableBuilder->create( $tmpnew );
		$this->temporaryTableBuilder->create( $tmpres );

		// Adding multiple values for the same column in sqlite is not supported.
		//
		// $tablename and $tmpnew are temporary tables created via
		// TemporaryTableBuilder, which uses raw query() and so does not apply
		// the table prefix. Routing these inserts through the QueryBuilder
		// would apply the prefix and target a non-existent table under MW's
		// unittest_ prefix mode. Stay on $db->query() with getType() dispatch
		// (same pattern as the INSERT...SELECT calls below) so the dialect-
		// correct SQL is emitted directly without depending on the cross-DB
		// regex shim.
		//
		// Postgres temp tables created by TemporaryTableBuilder have no unique
		// constraint; their dedup comes from a CREATE RULE ... DO INSTEAD
		// NOTHING that runs at INSERT time. ON CONFLICT DO NOTHING here is a
		// no-op (no unique constraints to arbitrate) and is kept only to
		// match the legacy regex-shim output. SQLite uses INSERT OR IGNORE
		// (its native equivalent of MySQL's INSERT IGNORE).
		foreach ( explode( ',', $values ) as $value ) {
			if ( $db->getType() === 'postgres' ) {
				$tablenameInsertSql = "INSERT INTO $tablename (id) VALUES $value ON CONFLICT DO NOTHING";
				$tmpnewInsertSql = "INSERT INTO $tmpnew (id) VALUES $value ON CONFLICT DO NOTHING";
			} elseif ( $db->getType() === 'sqlite' ) {
				$tablenameInsertSql = "INSERT OR IGNORE INTO $tablename (id) VALUES $value";
				$tmpnewInsertSql = "INSERT OR IGNORE INTO $tmpnew (id) VALUES $value";
			} else {
				$tablenameInsertSql = "INSERT IGNORE INTO $tablename (id) VALUES $value";
				$tmpnewInsertSql = "INSERT IGNORE INTO $tmpnew (id) VALUES $value";
			}

			$db->query( $tablenameInsertSql, __METHOD__, ISQLPlatform::QUERY_CHANGE_ROWS );
			$db->query( $tmpnewInsertSql, __METHOD__, ISQLPlatform::QUERY_CHANGE_ROWS );
		}

		for ( $i = 0; $i < $depth; $i++ ) {
			if ( $db->getType() === 'postgres' ) {
				$insertIgnoreSql = "INSERT INTO $tmpres (id) SELECT CAST(s_id AS INTEGER) FROM $smwtable, $tmpnew WHERE o_id=id ON CONFLICT DO NOTHING";
			} elseif ( $db->getType() === 'sqlite' ) {
				$insertIgnoreSql = "INSERT OR IGNORE INTO $tmpres (id) SELECT CAST(s_id AS INTEGER) FROM $smwtable, $tmpnew WHERE o_id=id";
			} else {
				$insertIgnoreSql = "INSERT IGNORE INTO $tmpres (id) SELECT CAST(s_id AS INTEGER) FROM $smwtable, $tmpnew WHERE o_id=id";
			}

			$db->query( $insertIgnoreSql, __METHOD__, ISQLPlatform::QUERY_CHANGE_ROWS );

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			if ( $db->getType() === 'postgres' ) {
				$carrybackSql = "INSERT INTO $tablename (id) SELECT $tmpres.id FROM $tmpres ON CONFLICT DO NOTHING";
			} elseif ( $db->getType() === 'sqlite' ) {
				$carrybackSql = "INSERT OR IGNORE INTO $tablename (id) SELECT $tmpres.id FROM $tmpres";
			} else {
				$carrybackSql = "INSERT IGNORE INTO $tablename (id) SELECT $tmpres.id FROM $tmpres";
			}

			$db->query( $carrybackSql, __METHOD__, ISQLPlatform::QUERY_CHANGE_ROWS );

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			// empty "new" table — see prefix-bypass note above; stay on raw query()
			$db->query( "DELETE FROM $tmpnew", __METHOD__, ISQLPlatform::QUERY_CHANGE_ROWS );

			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		$this->hierarchyCache[$values] = $tablename;

		$this->temporaryTableBuilder->drop( $tmpnew );
		$this->temporaryTableBuilder->drop( $tmpres );
	}

}
