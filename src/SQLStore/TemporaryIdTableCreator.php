<?php

namespace SMW\SQLStore;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TemporaryIdTableCreator {

	private $dbType;

	/**
	 * @param string $dbType
	 */
	public function __construct( $dbType ) {
		$this->dbType = $dbType;
	}

	public function getSqlToCreate( $tableName ) {
		// PostgreSQL: no memory tables, use RULE to emulate INSERT IGNORE
		if ( $this->dbType == 'postgres' ) {

			// Remove any double quotes from the name
			$tableName = str_replace( '"', '', $tableName );

			return "DO \$\$BEGIN "
				. " IF EXISTS(SELECT NULL FROM pg_tables WHERE tablename='{$tableName}' AND schemaname = ANY (current_schemas(true))) "
				. " THEN DELETE FROM {$tableName}; "
				. " ELSE "
				. "  CREATE TEMPORARY TABLE {$tableName} (id INTEGER PRIMARY KEY); "
				. "    CREATE RULE {$tableName}_ignore AS ON INSERT TO {$tableName} WHERE (EXISTS (SELECT 1 FROM {$tableName} "
				. "	 WHERE ({$tableName}.id = new.id))) DO INSTEAD NOTHING; "
				. " END IF; "
				. "END\$\$";
		}

		// MySQL_ just a temporary table, use INSERT IGNORE later
		return "CREATE TEMPORARY TABLE " . $tableName . "( id INT UNSIGNED KEY ) ENGINE=MEMORY";
	}

}

