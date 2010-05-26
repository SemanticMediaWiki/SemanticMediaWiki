<?php
/**
 * Some static helper functions that SMW uses for setting up
 * SQL databases.
 *
 * @author Markus Krötzsch
 * @author Marcel Gsteiger
 * @author Jeroen De Dauw
 * 
 * @file
 * @ingroup SMWStore
 */

/**
 * Static class to collect some helper functions that SMW uses
 * for settnig up SQL databases.
 * 
 * @ingroup SMWStore
 */
class SMWSQLHelpers {

	/**
	 * Database backends often have different types that need to be used
	 * repeatedly in (Semantic) MediaWiki. This function provides the preferred
	 * type (as a string) for various common kinds of columns. The input
	 * is one of the following strings: 'id' (page id numbers or similar),
	 * 'title' (title strings or similar), 'namespace' (namespace numbers),
	 * 'blob' (longer text blobs), 'iw' (interwiki prefixes).
	 */
	static public function getStandardDBType( $input ) {
		global $wgDBtype;
		
		switch ( $input ) {
			case 'id': return $wgDBtype == 'postgres' ? 'SERIAL' : 'INT(8) UNSIGNED'; // like page_id in MW page table
			case 'namespace': return $wgDBtype == 'postgres' ? 'BIGINT' : 'INT(11)'; // like page_namespace in MW page table
			case 'title': return $wgDBtype == 'postgres' ? 'TEXT' : 'VARBINARY(255)'; // like page_title in MW page table
			case 'iw': return $wgDBtype == 'postgres' ? 'TEXT' : 'VARCHAR(32) binary'; // like iw_prefix in MW interwiki table
			case 'blob': return $wgDBtype == 'postgres' ? 'BYTEA' : 'MEDIUMBLOB'; // larger blobs of character data, usually not subject to SELECT conditions
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
	 * @param $reportTo Object to report back to.
	 */
	public static function setupTable( $tableName, array $fields, $db, $reportTo = null ) {
		global $wgDBname, $wgDBtype, $wgDBTableOptions;
		
		$tableName = $db->tableName( $tableName );

		self::reportProgress( "Checking table $tableName ...\n", $reportTo );
		
		if ( $db->tableExists( $tableName ) === false ) { // create new table
			self::reportProgress( "   Table not found, now creating...\n", $reportTo );
			self::createTable( $tableName, $fields, $db, $reportTo );
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
	 * @param DatabaseBase or Database $db
	 * @param $reportTo Object to report back to.
	 */
	protected static function createTable( $tableName, array $fields, $db, $reportTo ) {
		global $wgDBtype, $wgDBTableOptions, $wgDBname;
		
		$sql = 'CREATE TABLE ' . ( $wgDBtype == 'postgres' ? '' : "`$wgDBname`." ) . $tableName . ' (';
		
		$fieldSql = array();
		
		foreach ( $fields as $fieldName => $fieldType ) {
			$fieldSql[] = "$fieldName  $fieldType";
		}
		
		$sql .= implode( ',', $fieldSql ) . ') ';
		if ( $wgDBtype != 'postgres' ) $sql .= $wgDBTableOptions;
		
		$db->query( $sql, __METHOD__ );
	}
	
	/**
	 * Update a table given an array of field names and field types.
	 * 
	 * @param string $tableName The table name.
	 * @param array $columns The fields and their types the table should have.
	 * @param DatabaseBase or Database $db
	 * @param $reportTo Object to report back to.
	 */
	protected static function updateTable( $tableName, array $fields, $db, $reportTo ) {
		global $wgDBtype;
		
		$currentFields = self::getFields( $tableName, $db, $reportTo );

		$isPostgres = $wgDBtype == 'postgres';
		
		if ( !$isPostgres ) $position = 'FIRST';
		
		// Loop through all the field definitions, and handle each definition for either postgres or MySQL.
		foreach ( $fields as $fieldName => $fieldType ) {
			if ( $isPostgres ) {
				self::updatePostgresField( $tableName, $fieldName, $fieldType, $currentFields, $db, $reportTo );
			}
			else {
				self::updateMySqlField( $tableName, $fieldName, $fieldType, $currentFields, $db, $reportTo, $position );
				$position = "AFTER $fieldName";
			}
			
			$currentFields[$fieldName] = false;
		}
		
		// The updated fields have their value set to false, so if a field has a value
		// that differs from false, it's an obsolete one that should be removed.
		foreach ( $currentFields as $fieldName => $value ) {
			if ( $value !== false ) {
				SMWSQLHelpers::reportProgress( "   ... deleting obsolete field $fieldName ... ", $reportTo );
				
				if ( $isPostgres ) {
					$db->query( "ALTER TABLE \"" . $tableName . "\" DROP COLUMN \"" . $fieldName . "\"", __METHOD__ );
				}
				else {
					$db->query( "ALTER TABLE $tableName DROP COLUMN `$fieldName`", __METHOD__ );					
				}
				
				SMWSQLHelpers::reportProgress( "done.\n", $reportTo );
			}
		}		
	}

	/**
	 * Returns an array of fields (as keys) and their types (as values).
	 * 
	 * @param string $tableName The table name.
	 * @param DatabaseBase or Database $db
	 * @param $reportTo Object to report back to.
	 * 
	 * @return array
	 */
	protected static function getFields( $tableName, $db, $reportTo ) {
		global $wgDBtype;
		
		if ( $wgDBtype == 'postgres' ) {
			// Use the data dictionary in postgresql to get an output comparable to DESCRIBE.
			$sql = <<<EOT
SELECT
	a.attname as "Field", 
	upper(pg_catalog.format_type(a.atttypid, a.atttypmod)) as "Type", 
	(SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128) 
	FROM pg_catalog.pg_attrdef d 
	WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as "Extra", 
		case when a.attnotnull THEN \'NO\'::text else \'YES\'::text END as "Null", a.attnum 
	FROM pg_catalog.pg_attribute a 
	WHERE a.attrelid = (
	    SELECT c.oid 
	    FROM pg_catalog.pg_class c 
	    LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace 
	    WHERE c.relname ~ \'^(' . $tableName . ')$\' 
	    AND pg_catalog.pg_table_is_visible(c.oid) 
	    LIMIT 1 
	 ) AND a.attnum > 0 AND NOT a.attisdropped 
	 ORDER BY a.attnum			
EOT;
		} else { // MySQL
			$sql = 'DESCRIBE ' . $tableName;
		}
		
		$res = $db->query( $sql, __METHOD__ );
		$curfields = array();
		$result = array();
		
		while ( $row = $db->fetchObject( $res ) ) {
			$type = strtoupper( $row->Type );
			
			if ( $wgDBtype == 'postgres' ) { // postgresql
				if ( eregi( '^nextval\\(.+\\)$', $row->Extra ) ) {
					$type = 'SERIAL NOT NULL';
				} elseif ( $row->Null != 'YES' ) {
						$type .= ' NOT NULL';
				}
			} else { // mysql
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
	 * Update a single field given it's name and type and an array of current fields. Postgres version.
	 * 
	 * @param string $tableName The table name.
	 * @param string $name The field name.
	 * @param string $type The field type and attributes.
	 * @param array $currentFields List of fields as they have been found in the database.
	 * @param DatabaseBase or Database $db
	 * @param object $reportTo Object to report back to.
	 */
	protected static function updatePostgresField( $tableName, $name, $type, array $currentFields, $db, $reportTo ) {
		$keypos = strpos( $type, ' PRIMARY KEY' );
		
		if ( $keypos > 0 ) {
			$type = substr( $type, 0, $keypos );
		}
		
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
			$typeold = ( $notnullposold > 0 ) ? substr( $currentFields[$name], 0, $notnullposold ) : $currentFields[$name];
			
			if ( $typeold != $type ) {
				$db->query( "ALTER TABLE \"" . $tableName . "\" ALTER COLUMN \"" . $name . "\" TYPE " . $type, __METHOD__ );
			}
			
			if ( $notnullposold != $notnullposnew ) {
				$db->query( "ALTER TABLE \"" . $tableName . "\" ALTER COLUMN \"" . $name . "\" " . ( $notnullposnew > 0 ? 'SET' : 'DROP' ) . " NOT NULL", __METHOD__ );
			}
			
			self::reportProgress( "done.\n", $reportTo );
		} else {
			self::reportProgress( "   ... field $name is fine.\n", $reportTo );
		}
	}
	
	/**
	 * Update a single field given it's name and type and an array of current fields. MySQL version.
	 * 
	 * @param string $tableName The table name.
	 * @param string $name The field name.
	 * @param string $type The field type and attributes.
	 * @param array $currentFields List of fields as they have been found in the database.
	 * @param DatabaseBase or Database $db
	 * @param object $reportTo Object to report back to.
	 * @param string $position
	 */
	protected static function updateMySqlField( $tableName, $name, $type, array $currentFields, $db, $reportTo, $position ) {
		if ( !array_key_exists( $name, $currentFields ) ) {
			self::reportProgress( "   ... creating field $name ... ", $reportTo );
			
			$db->query( "ALTER TABLE $tableName ADD `$name` $type $position", __METHOD__ );
			$result[$name] = 'new';
			
			self::reportProgress( "done.\n", $reportTo );
		} elseif ( $currentFields[$name] != $type ) {
			self::reportProgress( "   ... changing type of field $name from '$currentFields[$name]' to '$type' ... ", $reportTo );
			
			$db->query( "ALTER TABLE $tableName CHANGE `$name` `$name` $type $position", __METHOD__ );
			$result[$name] = 'up';
			self::reportProgress( "done.\n", $reportTo );
		} else {
			self::reportProgress( "   ... field $name is fine.\n", $reportTo );
		}
	}	

	/**
	 * Make sure that each of the column descriptions in the given array is indexed by *one* index
	 * in the given DB table.
	 * 
	 * @param string $tableName The table name. Does not need to have been passed to DatabaseBase->tableName yet.
	 * @param array $columns The field names to put indexes on
	 * @param DatabaseBase or Database $db
	 */
	public static function setupIndex( $tableName, array $columns, $db ) {
		// TODO: $verbose is not a good global name! 
		global $wgDBtype, $verbose; 
		
		$tableName = $db->tableName( $tableName );

		if ( $wgDBtype == 'postgres' ) { // postgresql
			$sql = "SELECT  i.relname AS indexname,"
				. " pg_get_indexdef(i.oid) AS indexdef, "
				. " replace(substring(pg_get_indexdef(i.oid) from '\\\\((.*)\\\\)'),' ','') AS indexcolumns"
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
			
			$indexes = array();
			
			while ( $row = $db->fetchObject( $res ) ) {
				// Remove the unneeded indexes, let indexes alone that already exist in the correct fashion.
				if ( array_key_exists( $row->indexcolumns, $columns ) ) {
					$columns[$row->indexcolumns] = false;
				} else {
					$db->query( 'DROP INDEX IF EXISTS ' . $row->indexname, __METHOD__ );
				}
			}
			
			foreach ( $columns as $key => $index ) { // Ddd the remaining indexes.
				if ( $index != false ) {
					$type = 'INDEX';
					
					// If the index is an array, it'll contain the column name as first element, and index type as second one.
					if ( is_array( $index ) ) {
						$column = $index[0];
						if ( count( $index ) > 1 ) $type = $index[1];
					} 
					else {
						$column = $index;
					}
					
					$db->query( "CREATE $type {$tableName}_index{$key} ON $tableName USING btree(" . $column . ")", __METHOD__ );
				}
			}
		} else { // MySQL
			$res = $db->query( 'SHOW INDEX FROM ' . $tableName , __METHOD__ );
			
			if ( !$res ) {
				return false;
			}
			
			$indexes = array();
			
			while ( $row = $db->fetchObject( $res ) ) {
				if ( !array_key_exists( $row->Key_name, $indexes ) ) {
					$indexes[$row->Key_name] = array();
				}
				$indexes[$row->Key_name][$row->Seq_in_index] = $row->Column_name;
			}
			
			foreach ( $indexes as $key => $index ) { // Clean up the existing indexes.
				$id = array_search( implode( ',', $index ), $columns );
				if ( $id !== false ) {
					$columns[$id] = false;
				} else { // Duplicate or unrequired index.
					$db->query( 'DROP INDEX ' . $key . ' ON ' . $tableName, __METHOD__ );
				}
			}

			foreach ( $columns as $key => $index ) { // Add the remaining indexes.
				if ( $index != false ) {
					$type = 'INDEX';
					
					// If the index is an array, it'll contain the column name as first element, and index type as second one.
					if ( is_array( $index ) ) {
						$column = $index[0];
						if ( count( $index ) > 1 ) $type = $index[1];
					} 
					else {
						$column = $index;
					}	
									
					$db->query( "ALTER TABLE $tableName ADD $type ( $column )", __METHOD__ );
				}
			}
		}
		
		return true;
	}

	/**
	 * Reports the given message to the reportProgress method of the $receiver.
	 * 
	 * @param string $msg
	 * @param object $receiver
	 */
	protected static function reportProgress( $msg, $receiver ) {
		if ( $receiver !== null ) $receiver->reportProgress( $msg );
	}

}