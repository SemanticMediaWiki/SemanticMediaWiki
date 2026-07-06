<?php

namespace SMW\SQLStore\TableBuilder;

use SMW\MediaWiki\Connection\Database;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class TemporaryTableBuilder {

	private bool $autoCommitFlag = false;

	/**
	 * @since 2.3
	 */
	public function __construct( private readonly Database $connection ) {
	}

	/**
	 * @see $smwgQTemporaryTablesWithAutoCommit
	 * @since 2.5
	 *
	 * @param bool $autoCommitFlag
	 */
	public function setAutoCommitFlag( $autoCommitFlag ): void {
		$this->autoCommitFlag = (bool)$autoCommitFlag;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 */
	public function create( $tableName ): void {
		if ( $this->autoCommitFlag ) {
			$this->connection->setFlag( Database::AUTO_COMMIT );
		}

		// Resolve the bare logical name through tableName() so the temp table
		// lives at the prefix-applied physical name, matching what MW core's
		// QueryBuilder produces when consumers reference it later.
		$resolvedName = $this->connection->tableName( $tableName );

		$this->connection->query(
			$this->getSQLCodeFor( $resolvedName ),
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_SCHEMA
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 */
	public function drop( string $tableName ): void {
		if ( $this->autoCommitFlag ) {
			$this->connection->setFlag( Database::AUTO_COMMIT );
		}

		// Resolve the bare logical name through tableName() so we drop the
		// prefix-applied physical name. See create() for the rationale.
		$resolvedName = $this->connection->tableName( $tableName );

		// Platform-specific DDL: MySQL has DROP TEMPORARY TABLE; Postgres
		// uses plain DROP TABLE IF EXISTS (the temp-table scope handles
		// teardown); SQLite uses plain DROP TABLE.
		if ( $this->connection->isType( 'postgres' ) ) {
			$sql = 'DROP TABLE IF EXISTS ' . $resolvedName;
		} elseif ( $this->connection->isType( 'sqlite' ) ) {
			$sql = 'DROP TABLE ' . $resolvedName;
		} else {
			$sql = 'DROP TEMPORARY TABLE ' . $resolvedName;
		}

		$this->connection->query(
			$sql,
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_SCHEMA
		);
	}

	/**
	 * Get SQL code suitable to create a temporary table of the given name, used
	 * to store ids.
	 *
	 * MySQL can do that simply by creating new temporary tables. PostgreSQL first
	 * checks if such a table exists, so the code is ready to reuse existing tables
	 * if the code was modified to keep them after query answering. Also, PostgreSQL
	 * tables will use a RULE to achieve built-in duplicate elimination. The latter
	 * is done using INSERT IGNORE in MySQL.
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	private function getSQLCodeFor( $tableName ): string {
		// PostgreSQL: no memory tables, use RULE to emulate INSERT IGNORE
		if ( $this->connection->isType( 'postgres' ) ) {

			// Remove any double quotes from the name
			$tableName = str_replace( '"', '', $tableName );

			return "DO \$\$BEGIN "
				. " IF EXISTS(SELECT NULL FROM pg_tables WHERE tablename='{$tableName}' AND schemaname = ANY (current_schemas(true))) "
				. " THEN DELETE FROM {$tableName}; "
				. " ELSE "
				. "  CREATE TEMPORARY TABLE {$tableName} (id SERIAL); "
				. "    CREATE RULE {$tableName}_ignore AS ON INSERT TO {$tableName} WHERE (EXISTS (SELECT 1 FROM {$tableName} "
				. "	 WHERE ({$tableName}.id = new.id))) DO INSTEAD NOTHING; "
				. " END IF; "
				. "END\$\$";
		}

		// SQLite: use TEMPORARY rather than the TEMP synonym SQLite also
		// accepts, and no engine clause. MediaWiki's rdbms Query verb parser
		// only recognises `CREATE TEMPORARY` as temporary-table creation; with
		// `CREATE TEMP` the table is never registered as temporary, so later
		// INSERT/DELETE into it are accounted as permanent primary writes.
		// `INTEGER PRIMARY KEY` is a SQLite-specific rowid alias: the column
		// accepts explicit values supplied via INSERT (HierarchyTempTableBuilder
		// supplies them) and rejects duplicates, so dedup later via
		// INSERT OR IGNORE works without an extra constraint clause.
		if ( $this->connection->isType( 'sqlite' ) ) {
			return 'CREATE TEMPORARY TABLE IF NOT EXISTS ' . $tableName . ' ( id INTEGER PRIMARY KEY )';
		}

		// MySQL: temporary memory table; dedup via INSERT IGNORE later.
		return 'CREATE TEMPORARY TABLE IF NOT EXISTS ' . $tableName . '( id INT UNSIGNED KEY ) ENGINE=MEMORY';
	}

}
