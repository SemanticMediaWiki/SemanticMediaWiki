<?php

/**
 * Some static helper functions that SMW uses for setting up
 * SQL databases.
 *
 * @author Markus Krötzsch
 * @author Marcel Gsteiger
 * @author Jeroen De Dauw
 *
 * @ingroup SMWStore
 */
class SMWSQLHelpers {

	/**
	 * Database backends often have different types that need to be used
	 * repeatedly in (Semantic) MediaWiki. This function provides the
	 * preferred type (as a string) for various common kinds of columns.
	 * The input is one of the following strings: 'id' (page id numbers or
	 * similar), 'title' (title strings or similar), 'namespace' (namespace
	 * numbers), 'blob' (longer text blobs), 'iw' (interwiki prefixes).
	 *
	 * @param string $input
	 * @return string|false SQL type declaration
	 */
	static public function getStandardDBType( $input ) {
		global $wgDBtype;

		switch ( $input ) {
			case 'id':
			return $wgDBtype == 'postgres' ? 'SERIAL' : ($wgDBtype == 'sqlite' ? 'INTEGER' :'INT(8) UNSIGNED'); // like page_id in MW page table
			case 'namespace':
			return $wgDBtype == 'postgres' ? 'BIGINT' : 'INT(11)'; // like page_namespace in MW page table
			case 'title':
			return $wgDBtype == 'postgres' ? 'TEXT' : 'VARBINARY(255)'; // like page_title in MW page table
			case 'iw':
			return ($wgDBtype == 'postgres' || $wgDBtype == 'sqlite') ? 'TEXT' : 'VARBINARY(32)'; // like iw_prefix in MW interwiki table
			case 'blob':
			return $wgDBtype == 'postgres' ? 'BYTEA' : 'MEDIUMBLOB'; // larger blobs of character data, usually not subject to SELECT conditions
		}

		return false;
	}

	/**
	 * Generic creation and updating function for database tables. Ideally, it
	 * would be able to modify a table's signature in arbitrary ways, but it will
	 * fail for some changes. Its string-based interface is somewhat too
	 * impoverished for a permanent solution. It would be possible to go for update
	 * scripts (specific to each change) in the style of MediaWiki instead.
	 *
	 * Make sure the table of the given name has the given fields, provided
	 * as an array with entries fieldname => typeparams. typeparams should be
	 * in a normalised form and order to match to existing values.
	 *
	 * The function returns an array that includes all columns that have been
	 * changed. For each such column, the array contains an entry
	 * columnname => action, where action is one of 'up', 'new', or 'del'
	 *
	 * If progress reports during this operation are desired, then the parameter $reportTo should
	 * be given an object that has a method reportProgress(string) for doing so.
	 *
	 * @note The function partly ignores the order in which fields are set up.
	 * Only if the type of some field changes will its order be adjusted explicitly.
	 *
	 * @param string $tableName The table name. Does not need to have been passed to DatabaseBase->tableName yet.
	 * @param array $columns The fields and their types the table should have.
	 * @param DatabaseBase or Database $db
	 * @param object $reportTo Object to report back to.
	 */
	public static function setupTable( $rawTableName, array $fields, $db, $reportTo = null ) {
		$tableName = $db->tableName( $rawTableName );

		self::reportProgress( "Checking table $tableName ...\n", $reportTo );

		if ( $db->tableExists( $rawTableName ) === false ) { // create new table
			self::reportProgress( "   Table not found, now creating...\n", $reportTo );
			self::createTable( $tableName, $fields, $db );
			self::reportProgress( "   ... done.\n", $reportTo );
		} else {
			self::reportProgress( "   Table already exists, checking structure ...\n", $reportTo );
			self::updateTable( $tableName, $fields, $db, $reportTo );
			self::reportProgress( "   ... done.\n", $reportTo );
		}
	}

	/**
	 * Creates a new database table with the specified fields.
	 *
	 * @param string $tableName The table name.
	 * @param array $columns The fields and their types the table should have.
	 * @param DatabaseBase|Database $db
	 */
	private static function createTable( $tableName, array $fields, $db ) {
		global $wgDBtype, $wgDBname;

		$sql = 'CREATE TABLE ' . ( ( $wgDBtype == 'postgres' || $wgDBtype == 'sqlite' ) ? '' : "`$wgDBname`." ) . $tableName . ' (';

		$fieldSql = array();

		foreach ( $fields as $fieldName => $fieldType ) {
			$fieldSql[] = "$fieldName  $fieldType";
		}

		$sql .= implode( ',', $fieldSql ) . ') ';

		if ( $wgDBtype != 'postgres' && $wgDBtype != 'sqlite' ) {
			// This replacement is needed for compatibility, see http://bugs.mysql.com/bug.php?id=17501
			$sql .= str_replace( 'TYPE', 'ENGINE', $GLOBALS['wgDBTableOptions'] );
		}

		$db->query( $sql, __METHOD__ );
	}

	/**
	 * Update a table given an array of field names and field types.
	 *
	 * @param string $tableName The table name.
	 * @param array $columns The fields and their types the table should have.
	 * @param DatabaseBase|Database $db
	 * @param object $reportTo Object to report back to.
	 */
	private static function updateTable( $tableName, array $fields, $db, $reportTo ) {
		global $wgDBtype;

		$currentFields = self::getFields( $tableName, $db, $reportTo );

		$isPostgres = $wgDBtype == 'postgres';

		if ( !$isPostgres ) {
			$position = 'FIRST';
		}

		// Loop through all the field definitions, and handle each definition for either postgres or MySQL.
		foreach ( $fields as $fieldName => $fieldType ) {
			if ( $isPostgres ) {
				self::updatePostgresField( $tableName, $fieldName, $fieldType, $currentFields, $db, $reportTo );
			} else {
				self::updateMySqlField( $tableName, $fieldName, $fieldType, $currentFields, $db, $reportTo, $position );
				$position = "AFTER $fieldName";
			}

			$currentFields[$fieldName] = false;
		}

		// The updated fields have their value set to false, so if a field has a value
		// that differs from false, it's an obsolete one that should be removed.
		foreach ( $currentFields as $fieldName => $value ) {
			if ( $value !== false ) {
				self::reportProgress( "   ... deleting obsolete field $fieldName ... ", $reportTo );

				if ( $isPostgres ) {
					$db->query( 'ALTER TABLE "' . $tableName . '" DROP COLUMN "' . $fieldName . '"', __METHOD__ );
				} elseif ( $wgDBtype == 'sqlite' ) {
					// DROP COLUMN not supported in Sqlite3
					self::reportProgress( "   ... deleting obsolete field $fieldName not possible in SQLLite ... you could delete and reinitialize the tables to remove obsolete data, or just keep it ... ", $reportTo );
				} else {
					$db->query( "ALTER TABLE $tableName DROP COLUMN `$fieldName`", __METHOD__ );
				}

				self::reportProgress( "done.\n", $reportTo );
			}
		}
	}

	/**
	 * Returns an array of fields (as keys) and their types (as values).
	 *
	 * @param string $tableName The table name.
	 * @param DatabaseBase|Database $db
	 * @param object $reportTo to report back to.
	 *
	 * @return array
	 */
	private static function getFields( $tableName, $db, $reportTo ) {
		global $wgDBtype;

		if ( $wgDBtype == 'postgres' ) {
			$tableName = str_replace( '"', '', $tableName );
			// Use the data dictionary in postgresql to get an output comparable to DESCRIBE.
			$sql = <<<EOT
SELECT
	a.attname as "Field",
	upper(pg_catalog.format_type(a.atttypid, a.atttypmod)) as "Type",
	(SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)
	FROM pg_catalog.pg_attrdef d
	WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as "Extra",
		case when a.attnotnull THEN 'NO'::text else 'YES'::text END as "Null", a.attnum
	FROM pg_catalog.pg_attribute a
	WHERE a.attrelid = (
	    SELECT c.oid
	    FROM pg_catalog.pg_class c
	    LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
	    WHERE c.relname ~ '^($tableName)$'
	    AND pg_catalog.pg_table_is_visible(c.oid)
	    LIMIT 1
	 ) AND a.attnum > 0 AND NOT a.attisdropped
	 ORDER BY a.attnum
EOT;
		} elseif ( $wgDBtype == 'sqlite' ) { // SQLite
			$sql = 'PRAGMA table_info(' . $tableName . ')';
		} else { // MySQL
			$sql = 'DESCRIBE ' . $tableName;
		}

		$res = $db->query( $sql, __METHOD__ );
		$curfields = array();

		foreach ( $res as $row ) {
			if ( $wgDBtype == 'postgres' ) { // postgresql
				$type = strtoupper( $row->Type );

				if ( preg_match( '/^nextval\\(.+\\)/i', $row->Extra ) ) {
					$type = 'SERIAL NOT NULL';
				} elseif ( $row->Null != 'YES' ) {
						$type .= ' NOT NULL';
				}
			} elseif ( $wgDBtype == 'sqlite' ) { // SQLite
				$row->Field = $row->name;
				$row->Type = $row->type;
				$type = $row->type;
				if ( $row->notnull == '1' ) {
					$type .= ' NOT NULL';
				}
				if ( $row->pk == '1' ) {
					$type .= ' PRIMARY KEY AUTOINCREMENT';
				}
			} else { // mysql
				$type = strtoupper( $row->Type );

				if ( substr( $type, 0, 8 ) == 'VARCHAR(' ) {
					$type .= ' binary'; // just assume this to be the case for VARCHAR, though DESCRIBE will not tell us
				}

				if ( $row->Null != 'YES' ) {
					$type .= ' NOT NULL';
				}

				if ( $row->Key == 'PRI' ) { /// FIXME: updating "KEY" is not possible, the below query will fail in this case.
					$type .= ' KEY';
				}

				if ( $row->Extra == 'auto_increment' ) {
					$type .= ' AUTO_INCREMENT';
				}
			}

			$curfields[$row->Field] = $type;
		}

		return $curfields;
	}

	/**
	 * Update a single field given it's name and type and an array of
	 * current fields. Postgres version.
	 *
	 * @param string $tableName The table name.
	 * @param string $name The field name.
	 * @param string $type The field type and attributes.
	 * @param array $currentFields List of fields as they have been found in the database.
	 * @param DatabaseBase|Database $db
	 * @param object $reportTo Object to report back to.
	 */
	private static function updatePostgresField( $tableName, $name, $type, array $currentFields, $db, $reportTo ) {
		$keypos = strpos( $type, ' PRIMARY KEY' );

		if ( $keypos > 0 ) {
			$type = substr( $type, 0, $keypos );
		}

		$type = strtoupper( $type );

		if ( !array_key_exists( $name, $currentFields ) ) {
			self::reportProgress( "   ... creating field $name ... ", $reportTo );

			$db->query( "ALTER TABLE $tableName ADD \"" . $name . "\" $type", __METHOD__ );

			self::reportProgress( "done.\n", $reportTo );
		} elseif ( $currentFields[$name] != $type ) {
			self::reportProgress( "   ... changing type of field $name from '$currentFields[$name]' to '$type' ... ", $reportTo );

			$notnullposnew = strpos( $type, ' NOT NULL' );
			if ( $notnullposnew > 0 ) {
				$type = substr( $type, 0, $notnullposnew );
			}

			$notnullposold = strpos( $currentFields[$name], ' NOT NULL' );
			$typeold  = strtoupper( ( $notnullposold > 0 ) ? substr( $currentFields[$name], 0, $notnullposold ) : $currentFields[$name] );

			if ( $typeold != $type ) {
				$sql = "ALTER TABLE " . $tableName . " ALTER COLUMN \"" . $name . "\" TYPE " . $type;
				$db->query( $sql, __METHOD__ );
			}

			if ( $notnullposold != $notnullposnew ) {
				$sql = "ALTER TABLE " . $tableName . " ALTER COLUMN \"" . $name . "\" " . ( $notnullposnew > 0 ? 'SET' : 'DROP' ) . " NOT NULL";
				$db->query( $sql, __METHOD__ );
			}

			self::reportProgress( "done.\n", $reportTo );
		} else {
			self::reportProgress( "   ... field $name is fine.\n", $reportTo );
		}
	}

	/**
	 * Update a single field given it's name and type and an array of
	 * current fields. MySQL version.
	 *
	 * @param string $tableName The table name.
	 * @param string $name The field name.
	 * @param string $type The field type and attributes.
	 * @param array $currentFields List of fields as they have been found in the database.
	 * @param DatabaseBase|Database $db
	 * @param object $reportTo Object to report back to.
	 * @param string $position
	 */
	private static function updateMySqlField( $tableName, $name, $type, array $currentFields, $db, $reportTo, $position ) {
		if ( !array_key_exists( $name, $currentFields ) ) {
			self::reportProgress( "   ... creating field $name ... ", $reportTo );

			$db->query( "ALTER TABLE $tableName ADD `$name` $type $position", __METHOD__ );
			$result[$name] = 'new';

			self::reportProgress( "done.\n", $reportTo );
		} elseif ( $currentFields[$name] != $type ) {
			self::reportProgress( "   ... changing type of field $name from '$currentFields[$name]' to '$type' ... ", $reportTo );

			// To avoid Error: 1068 Multiple primary key defined when a PRIMARY is involved
			if ( strpos( $type, 'AUTO_INCREMENT' ) !== false ) {
				$db->query( "ALTER TABLE $tableName DROP PRIMARY KEY", __METHOD__ );
			}

			$db->query( "ALTER TABLE $tableName CHANGE `$name` `$name` $type $position", __METHOD__ );
			$result[$name] = 'up';
			self::reportProgress( "done.\n", $reportTo );
		} else {
			self::reportProgress( "   ... field $name is fine.\n", $reportTo );
		}
	}

	/**
	 * Make sure that each of the column descriptions in the given array is
	 * indexed by *one* index in the given DB table.
	 *
	 * @param string $tableName table name. Does not need to have been passed to DatabaseBase->tableName yet.
	 * @param array $indexes array of strings, each a comma separated list with column names to index
	 * @param DatabaseBase|Database $db DatabaseBase or Database
	 * @param object $reportTo object to report messages to; since 1.8
	 */
	public static function setupIndex( $rawTableName, array $indexes, $db, $reportTo = null ) {
		global $wgDBtype;

		$tableName = $wgDBtype == 'postgres' ? $db->tableName( $rawTableName, 'raw' ) : $db->tableName( $rawTableName );

		self::reportProgress( "Checking index structures for table $tableName ...\n", $reportTo );

		// First remove obsolete indexes.
		$oldIndexes = self::getIndexInfo( $db, $tableName );
		if ( $wgDBtype == 'sqlite' ) { // SQLite
			/// TODO We do not currently get the right column definitions in
			/// SQLLite; hence we can only drop all indexes. Wasteful.
			foreach ( $oldIndexes as $key => $index ) {
				self::dropIndex( $db, $key, $tableName, $key, $reportTo );
			}
		} else {
			foreach ( $oldIndexes as $key => $indexColumn ) {
				$id = array_search( $indexColumn, $indexes );
				if ( $id !== false || $key == 'PRIMARY' ) {
					self::reportProgress( "   ... index $indexColumn is fine.\n", $reportTo );
					unset( $indexes[$id] );
				} else { // Duplicate or unrequired index.
					self::dropIndex( $db, $key, $tableName, $indexColumn, $reportTo );
				}
			}
		}

		// Add new indexes.
		foreach ( $indexes as $key => $index ) {
			// If the index is an array, it contains the column
			// name as first element, and index type as second one.
			if ( is_array( $index ) ) {
				$columns = $index[0];
				$type = count( $index ) > 1 ? $index[1] : 'INDEX';
			} else {
				$columns = $index;
				$type = 'INDEX';
			}

			self::createIndex( $db, $type, "{$tableName}_index{$key}", $tableName, $columns, $reportTo );
		}

		self::reportProgress( "   ... done.\n", $reportTo );

		return true;
	}

	/**
	 * Get the information about all indexes of a table. The result is an
	 * array of format indexname => indexcolumns. The latter is a comma
	 * separated list.
	 *
	 * @since 1.8
	 * @param DatabaseBase|Database $db database handler
	 * @param string $tableName name of table
	 * @return array indexname => columns
	 */
	private static function getIndexInfo( $db, $tableName ) {
		global $wgDBtype;

		$indexes = array();
		if ( $wgDBtype == 'postgres' ) { // postgresql
			$sql = "SELECT  i.relname AS indexname,"
				. " pg_get_indexdef(i.oid) AS indexdef, "
				. " replace(substring(pg_get_indexdef(i.oid) from E'\\\\((.*)\\\\)'), ' ' , '') AS indexcolumns"
				. " FROM pg_index x"
				. " JOIN pg_class c ON c.oid = x.indrelid"
				. " JOIN pg_class i ON i.oid = x.indexrelid"
				. " LEFT JOIN pg_namespace n ON n.oid = c.relnamespace"
				. " LEFT JOIN pg_tablespace t ON t.oid = i.reltablespace"
				. " WHERE c.relkind = 'r'::\"char\" AND i.relkind = 'i'::\"char\""
				. " AND c.relname = '" . $tableName . "'"
				. " AND NOT pg_get_indexdef(i.oid) ~ '^CREATE UNIQUE INDEX'";
			$res = $db->query( $sql, __METHOD__ );

			if ( !$res ) {
				return false;
			}

			foreach ( $res as $row ) {
				$indexes[$row->indexname] = $row->indexcolumns;
			}
		} elseif ( $wgDBtype == 'sqlite' ) { // SQLite
			$res = $db->query( 'PRAGMA index_list(' . $tableName . ')', __METHOD__ );

			if ( !$res ) {
				return false;
			}

			foreach ( $res as $row ) {
				/// FIXME The value should not be $row->name below?!
				if ( !array_key_exists( $row->name, $indexes ) ) {
					$indexes[$row->name] = $row->name;
				} else {
					$indexes[$row->name] .= ',' . $row->name;
				}
			}
		} else { // MySQL and default
			$res = $db->query( 'SHOW INDEX FROM ' . $tableName, __METHOD__ );

			if ( !$res ) {
				return false;
			}

			foreach ( $res as $row ) {
				if ( !array_key_exists( $row->Key_name, $indexes ) ) {
					$indexes[$row->Key_name] = $row->Column_name;
				} else {
					$indexes[$row->Key_name] .= ',' . $row->Column_name;
				}
			}
		}

		return $indexes;
	}

	/**
	 * Drop an index using the suitable SQL for various RDBMS.
	 *
	 * @since 1.8
	 * @param DatabaseBase|Database $db database handler
	 * @param string $indexName name fo the index as in DB
	 * @param string $tableName name of the table (not relevant in all DBMSs)
	 * @param string $columns list of column names to index, comma
	 * separated; only for reporting
	 * @param object $reportTo to report messages to
	 */
	private static function dropIndex( $db, $indexName, $tableName, $columns, $reportTo = null ) {
		global $wgDBtype;

		self::reportProgress( "   ... removing index $columns ...", $reportTo );
		if ( $wgDBtype == 'postgres' ) { // postgresql
			$db->query( 'DROP INDEX IF EXISTS ' . $indexName, __METHOD__ );
		} elseif ( $wgDBtype == 'sqlite' ) { // SQLite
			$db->query( 'DROP INDEX ' . $indexName, __METHOD__ );
		} else { // MySQL and default
			$db->query( 'DROP INDEX ' . $indexName . ' ON ' . $tableName, __METHOD__ );
		}
		self::reportProgress( "done.\n", $reportTo );
	}

	/**
	 * Create an index using the suitable SQL for various RDBMS.
	 *
	 * @since 1.8
	 * @param DatabaseBase|Database $db Database handler
	 * @param string $type "INDEX", "UNIQUE" or similar
	 * @param string $indexName name fo the index as in DB
	 * @param string $tableName name of the table
	 * @param array $columns list of column names to index, comma separated
	 * @param object $reportTo object to report messages to
	 */
	private static function createIndex( $db, $type, $indexName, $tableName, $columns, $reportTo = null ) {
		global $wgDBtype;

		self::reportProgress( "   ... creating new index $columns ...", $reportTo );
		if ( $wgDBtype == 'postgres' ) { // postgresql
			if ( $db->indexInfo( $tableName, $indexName ) === false ) {
				$db->query( "CREATE $type $indexName ON $tableName ($columns)", __METHOD__ );
			}
		} elseif ( $wgDBtype == 'sqlite' ) { // SQLite
			$db->query( "CREATE $type $indexName ON $tableName ($columns)", __METHOD__ );
		} else { // MySQL and default
			$db->query( "ALTER TABLE $tableName ADD $type ($columns)", __METHOD__ );
		}
		self::reportProgress( "done.\n", $reportTo );
	}

	/**
	 * Reports the given message to the reportProgress method of the
	 * $receiver.
	 *
	 * @param string $msg
	 * @param object $receiver
	 */
	private static function reportProgress( $msg, $receiver ) {
		if ( !is_null( $receiver ) ) {
			$receiver->reportProgress( $msg );
		}
	}

}
