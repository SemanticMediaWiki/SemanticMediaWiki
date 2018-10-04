<?php

namespace SMW\SQLStore\TableBuilder;

use SMW\MediaWiki\Database;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class TemporaryTableBuilder {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var boolean
	 */
	private $autoCommitFlag = false;

	/**
	 * @since 2.3
	 *
	 * @param Database $connection
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @see $smwgQTemporaryTablesWithAutoCommit
	 * @since 2.5
	 *
	 * @param boolean $autoCommitFlag
	 */
	public function setAutoCommitFlag( $autoCommitFlag ) {
		$this->autoCommitFlag = (bool)$autoCommitFlag;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 */
	public function create( $tableName ) {

		if ( $this->autoCommitFlag ) {
			$this->connection->setFlag( Database::AUTO_COMMIT );
		}

		$this->connection->query(
			$this->getSQLCodeFor( $tableName ),
			__METHOD__,
			false
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 */
	public function drop( $tableName ) {

		if ( $this->autoCommitFlag ) {
			$this->connection->setFlag( Database::AUTO_COMMIT );
		}

		$this->connection->query(
			"DROP TEMPORARY TABLE " . $tableName,
			__METHOD__,
			false
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
	private function getSQLCodeFor( $tableName ) {
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

		// MySQL_ just a temporary table, use INSERT IGNORE later
		return "CREATE TEMPORARY TABLE " . $tableName . "( id INT UNSIGNED KEY ) ENGINE=MEMORY";
	}

}
