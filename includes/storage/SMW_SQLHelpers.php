<?php
/**
 * Some static helper functions that SMW uses for setting up
 * SQL databases.
 *
 * @author Markus Krötzsch
 * @author Marcel Gsteiger
 * @file
 * @ingroup SMWStore
 */

/**
 * Static class to collect some helper functions that SMW uses
 * for settnig up SQL databases.
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
	static public function getStandardDBType($input) {
		global $wgDBtype;
		switch ($input) {
			case 'id': return $wgDBtype=='postgres'?'SERIAL':'INT(8) UNSIGNED'; // like page_id in MW page table
			case 'namespace': return $wgDBtype=='postgres'?'BIGINT':'INT(11)'; // like page_namespace in MW page table
			case 'title': return $wgDBtype=='postgres'?'TEXT':'VARBINARY(255)'; // like page_title in MW page table
			case 'iw': return $wgDBtype=='postgres'?'TEXT':'VARCHAR(32) binary'; // like iw_prefix in MW interwiki table
			case 'blob': return $wgDBtype=='postgres'?'BYTEA':'MEDIUMBLOB'; // larger blobs of character data, usually not subject to SELECT conditions
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
	 * If the table was already fine or was created completely anew, an empty
	 * array is returned (assuming that both cases require no action).
	 *
	 * If progress reports during this operation are desired, then the parameter $reportTo should
	 * be given an object that has a method reportProgress(string) for doing so.
	 *
	 * @note The function partly ignores the order in which fields are set up.
	 * Only if the type of some field changes will its order be adjusted explicitly.
	 */
	public static function setupTable($table, $fields, $db, $reportTo = NULL) {
		global $wgDBname, $wgDBtype;
		$fname = 'SMWSQLHelpers::setupTable';

		SMWSQLHelpers::reportProgress("Setting up table $table ...\n",$reportTo);
		if ($db->tableExists($table) === false) { // create new table
			$sql = 'CREATE TABLE ' . ($wgDBtype=='postgres'?'': "`$wgDBname`.") . $table . ' (';
			$first = true;
			foreach ($fields as $name => $type) {
				if ($first) {
					$first = false;
				} else {
					$sql .= ',';
				}
				$sql .= $name . '  ' . $type;
			}
			$sql .= ') ' . ($wgDBtype=='postgres'?'':'TYPE=innodb');
			$db->query( $sql, $fname );
			SMWSQLHelpers::reportProgress("   ... new table created\n",$reportTo);
			return array();
		} else { // check table signature
			SMWSQLHelpers::reportProgress("   ... table exists already, checking structure ...\n",$reportTo);
			if ($wgDBtype=='postgres') { // postgresql
				// use the data dictionary in postgresql to get an output comparable to DESCRIBE
				// To find out what kind of magic takes place here (and to remove the bugs included), simply use:
				// psql
				// \set ECHO_HIDDEN
				// \d <tablename>
				$sql = 'SELECT a.attname as "Field", '
					.' upper(pg_catalog.format_type(a.atttypid, a.atttypmod)) as "Type", '
					.' (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128) '
					.'  FROM pg_catalog.pg_attrdef d '
						.'  WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as "Extra", '
					.'  case when a.attnotnull THEN \'NO\'::text else \'YES\'::text END as "Null", a.attnum '
					.' FROM pg_catalog.pg_attribute a '
					.' WHERE a.attrelid = ('
					.'    SELECT c.oid '
					.'    FROM pg_catalog.pg_class c '
					.'    LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace '
					.'    WHERE c.relname ~ \'^(' . $table . ')$\' '
					.'    AND pg_catalog.pg_table_is_visible(c.oid) '
					.'    LIMIT 1 '
					.' ) AND a.attnum > 0 AND NOT a.attisdropped '
					.' ORDER BY a.attnum';
			} else { // mysql
				$sql = 'DESCRIBE ' . $table;
			}
			$res = $db->query($sql, $fname);
			$curfields = array();
			$result = array();
			while ($row = $db->fetchObject($res)) {
				$type = strtoupper($row->Type);
				if ($wgDBtype=='postgres') { // postgresql
					if (eregi('^nextval\\(.+\\)$',$row->Extra)) {
						$type = 'SERIAL NOT NULL';
					} elseif ($row->Null != 'YES') {
							$type .= ' NOT NULL';
					}
				} else { // mysql
					if (substr($type,0,8) == 'VARCHAR(') {
						$type .= ' binary'; // just assume this to be the case for VARCHAR, though DESCRIBE will not tell us
					}
					if ($row->Null != 'YES') {
						$type .= ' NOT NULL';
					}
					if ($row->Key == 'PRI') { /// FIXME: updating "KEY" is not possible, the below query will fail in this case.
						$type .= ' KEY';
					}
					if ($row->Extra == 'auto_increment') {
						$type .= ' AUTO_INCREMENT';
					}
				}
				$curfields[$row->Field] = $type;
			}

			if ($wgDBtype=='postgres') { // postgresql
				foreach ($fields as $name => $type) {
					$keypos = strpos($type,' PRIMARY KEY');
					if ($keypos > 0) {
						$type=substr($type,0,$keypos);
					}
					if ( !array_key_exists($name,$curfields) ) {
						SMWSQLHelpers::reportProgress("   ... creating column $name ... ",$reportTo);
						$db->query("ALTER TABLE $table ADD \"" . $name . "\" $type", $fname);
						$result[$name] = 'new';
						SMWSQLHelpers::reportProgress("done \n",$reportTo);
					} elseif ($curfields[$name] != $type) {
						SMWSQLHelpers::reportProgress("   ... changing type of column $name from '$curfields[$name]' to '$type' ... ",$reportTo);
						$notnullposnew = strpos($type,' NOT NULL');
						if ($notnullposnew > 0) {
							$type=substr($type,0,$notnullposnew);
						}
						$notnullposold = strpos($curfields[$name],' NOT NULL');
						$typeold = ($notnullposold > 0)?substr($curfields[$name],0,$notnullposold):$curfields[$name];
						if ($typeold!=$type) {
							$db->query("ALTER TABLE \"".$table."\" ALTER COLUMN \"".$name."\" TYPE " . $type, $fname);
						}
						if ($notnullposold!=$notnullposnew) {
							$db->query("ALTER TABLE \"". $table . "\" ALTER COLUMN \"".$name."\" ".($notnullposnew>0?'SET':'DROP'). " NOT NULL", $fname);
						}
						$result[$name] = 'up';
						$curfields[$name] = false;
						SMWSQLHelpers::reportProgress("done.\n",$reportTo);
					} else {
						SMWSQLHelpers::reportProgress("   ... column $name is fine\n",$reportTo);
						$curfields[$name] = false;
					}
				}
				foreach ($curfields as $name => $value) {
					if ($value !== false) {
						SMWSQLHelpers::reportProgress("   ... deleting obsolete column $name ... ",$reportTo);
						$db->query("ALTER TABLE \"". $table . "\" DROP COLUMN \"" . $name . "\"", $fname);
						$result[$name] = 'del';
						SMWSQLHelpers::reportProgress("done.\n",$reportTo);
					}
				}
			} else { // mysql
			  $position = 'FIRST';
			  foreach ($fields as $name => $type) {
				if ( !array_key_exists($name,$curfields) ) {
					SMWSQLHelpers::reportProgress("   ... creating column $name ... ",$reportTo);
					$db->query("ALTER TABLE $table ADD `$name` $type $position", $fname);
					$result[$name] = 'new';
					SMWSQLHelpers::reportProgress("done \n",$reportTo);
				} elseif ($curfields[$name] != $type) {
					SMWSQLHelpers::reportProgress("   ... changing type of column $name from '$curfields[$name]' to '$type' ... ",$reportTo);
					$db->query("ALTER TABLE $table CHANGE `$name` `$name` $type $position", $fname);
					$result[$name] = 'up';
					$curfields[$name] = false;
					SMWSQLHelpers::reportProgress("done.\n",$reportTo);
				} else {
					SMWSQLHelpers::reportProgress("   ... column $name is fine\n",$reportTo);
					$curfields[$name] = false;
				}
				$position = "AFTER $name";
			  }
			  foreach ($curfields as $name => $value) {
				if ($value !== false) { // not encountered yet --> delete
					SMWSQLHelpers::reportProgress("   ... deleting obsolete column $name ... ",$reportTo);
					$db->query("ALTER TABLE $table DROP COLUMN `$name`", $fname);
					$result[$name] = 'del';
					SMWSQLHelpers::reportProgress("done.\n",$reportTo);
				}
			  }
			}

			SMWSQLHelpers::reportProgress("   ... table $table set up successfully.\n",$reportTo);
			return $result;
		}
	}

	/**
	 * Make sure that each of the column descriptions in the given array is indexed by *one* index
	 * in the given DB table.
	 */
	public static function setupIndex($table, $columns, $db, $reportTo = NULL) {
		global $wgDBtype,$verbose;
		$table = $db->tableName($table);
		$fname = 'SMWSQLHelpers::setupIndex';

		if ($wgDBtype=='postgres') { // postgresql
			$sql= "SELECT  i.relname AS indexname,"
				. " pg_get_indexdef(i.oid) AS indexdef, "
				. " replace(substring(pg_get_indexdef(i.oid) from '\\\\((.*)\\\\)'),' ','') AS indexcolumns"
				. " FROM pg_index x"
				. " JOIN pg_class c ON c.oid = x.indrelid"
				." JOIN pg_class i ON i.oid = x.indexrelid"
				." LEFT JOIN pg_namespace n ON n.oid = c.relnamespace"
				." LEFT JOIN pg_tablespace t ON t.oid = i.reltablespace"
				." WHERE c.relkind = 'r'::\"char\" AND i.relkind = 'i'::\"char\""
				." AND c.relname = '" . $table . "'"
				." AND NOT pg_get_indexdef(i.oid) ~ '^CREATE UNIQUE INDEX'";
			$res = $db->query($sql,$fname);
			if ( !$res ) {
				return false;
			}
			$indexes = array();
			while ( $row = $db->fetchObject( $res ) ) {
				// remove unneeded indexes, let indexes alone that already exist in the correct fashion
				if (array_key_exists($row->indexcolumns,$columns)) {
					$columns[$row->indexcolumns]=false;
				} else {
					$db->query('DROP INDEX IF EXISTS ' . $row->indexname, $fname);
				}
			}
			foreach ($columns as $key => $column) { // add remaining indexes
				if ($column != false) {
					$db->query("CREATE INDEX " . $table . "_index" . $key . " ON " . $table . " USING btree(" . $column . ")", $fname);
				}
			}
		} else { // mysql
			$res = $db->query( 'SHOW INDEX FROM ' . $table , $fname);
			if ( !$res ) {
				return false;
			}
			$indexes = array();
			while ( $row = $db->fetchObject( $res ) ) {
				if (!array_key_exists($row->Key_name, $indexes)) {
					$indexes[$row->Key_name] = array();
				}
				$indexes[$row->Key_name][$row->Seq_in_index] = $row->Column_name;
			}
			foreach ($indexes as $key => $index) { // clean up existing indexes
				$id = array_search(implode(',', $index), $columns );
				if ( $id !== false ) {
					$columns[$id] = false;
				} else { // duplicate or unrequired index
					$db->query( 'DROP INDEX ' . $key . ' ON ' . $table, $fname);
				}
			}

			foreach ($columns as $key => $column) { // add remaining indexes
				if ($column != false) {
					$db->query( "ALTER TABLE $table ADD INDEX ( $column )", $fname);
				}
			}
		}
		return true;
	}

	/// If a receiver is given, report the given message to its reportProgress method.
	protected static function reportProgress($msg, $receiver = NULL) {
		if ($receiver !== NULL) $receiver->reportProgress($msg);
	}

}