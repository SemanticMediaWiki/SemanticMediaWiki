<?php

namespace SMW\SQLStore\TableBuilder;

use SMW\SQLStore\SQLStore;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 * @author Markus Krötzsch
 * @author Marcel Gsteiger
 * @author Jeroen De Dauw
 */
class PostgresTableBuilder extends TableBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getStandardFieldType( $fieldType ) {

		$fieldTypes = array(
			 // like page_id in MW page table
			'id'         => 'SERIAL',
			 // like page_id in MW page table
			'id_primary' => 'SERIAL NOT NULL PRIMARY KEY',
			 // like page_namespace in MW page table
			'namespace'  => 'BIGINT',
			 // like page_title in MW page table
			'title'      => 'TEXT',
			 // like iw_prefix in MW interwiki table
			'interwiki'  => 'TEXT',
			'iw'         => 'TEXT',
			'hash'       => 'TEXT',
			 // larger blobs of character data, usually not subject to SELECT conditions
			'blob'       => 'BYTEA',
			'text'       => 'TEXT',
			'boolean'    => 'BOOLEAN',
			'double'     => 'DOUBLE PRECISION',
			'integer'    => 'bigint',
			'char_long'  => 'TEXT',
			// Requires citext extension
			'char_nocase' => 'citext NOT NULL',
			'char_long_nocase' => 'citext NOT NULL',
			'usage_count'      => 'bigint',
			'integer_unsigned' => 'INTEGER'
		);

		return FieldType::mapType( $fieldType, $fieldTypes );
	}

	/** Create */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doCreateTable( $tableName, array $attributes = null ) {

		$tableName = $this->connection->tableName( $tableName );

		$fieldSql = array();
		$fields = $attributes['fields'];

		foreach ( $fields as $fieldName => $fieldType ) {
			$fieldSql[] = "$fieldName " . $this->getStandardFieldType( $fieldType );
		}

		$sql = 'CREATE TABLE ' . $tableName . ' (' . implode( ',', $fieldSql ) . ') ';

		$this->connection->query( $sql, __METHOD__ );
	}

	/** Update */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doUpdateTable( $tableName, array $attributes = null ) {

		$tableName = $this->connection->tableName( $tableName );
		$currentFields = $this->getCurrentFields( $tableName );

		$fields = $attributes['fields'];
		$position = 'FIRST';

		if ( !isset( $this->activityLog[$tableName] ) ) {
			$this->activityLog[$tableName] = [];
		}

		// Loop through all the field definitions, and handle each definition
		foreach ( $fields as $fieldName => $fieldType ) {
			$this->doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, $attributes );

			$position = "AFTER $fieldName";
			$currentFields[$fieldName] = false;
		}

		// The updated fields have their value set to false, so if a field has a value
		// that differs from false, it's an obsolete one that should be removed.
		foreach ( $currentFields as $fieldName => $value ) {
			if ( $value !== false ) {
				$this->doDropField( $tableName, $fieldName );
			}
		}
	}

	private function getCurrentFields( $tableName ) {

		$tableName = str_replace( '"', '', $tableName );
		// Use the data dictionary in postgresql to get an output comparable to DESCRIBE.
/*
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
*/

		$sql = "SELECT a.attname as \"Field\","
			. " upper(pg_catalog.format_type(a.atttypid, a.atttypmod)) as \"Type\","
			. " (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)"
			. " FROM pg_catalog.pg_attrdef d"
			. " WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as \"Extra\", "
			. " case when a.attnotnull THEN 'NO'::text else 'YES'::text END as \"Null\", a.attnum"
			. " FROM pg_catalog.pg_attribute a"
			. " WHERE a.attrelid = (SELECT c.oid"
			. " FROM pg_catalog.pg_class c"
			. " LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace"
			. " WHERE c.relname ~ '^(" . $tableName . ")$'"
			. " AND pg_catalog.pg_table_is_visible(c.oid)"
			. " LIMIT 1) AND a.attnum > 0 AND NOT a.attisdropped"
			. " ORDER BY a.attnum";

		$res = $this->connection->query( $sql, __METHOD__ );
		$currentFields = array();

		foreach ( $res as $row ) {
			$type = strtoupper( $row->Type );

			if ( preg_match( '/^nextval\\(.+\\)/i', $row->Extra ) ) {
				$type = 'SERIAL NOT NULL';
			} elseif ( $row->Null != 'YES' ) {
					$type .= ' NOT NULL';
			}

			$currentFields[$row->Field] = $type;
		}

		return $currentFields;
	}

	private function doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, array $attributes ) {

		$fieldType = $this->getStandardFieldType( $fieldType );
		$keypos = strpos( $fieldType, ' PRIMARY KEY' );

		if ( $keypos > 0 ) {
			$fieldType = substr( $fieldType, 0, $keypos );
		}

		$fieldType = strtoupper( $fieldType );
		$default = '';

		if ( isset( $attributes['defaults'][$fieldName] ) ) {
			$default = "DEFAULT '" . $attributes['defaults'][$fieldName] . "'";
		}

		if ( !array_key_exists( $fieldName, $currentFields ) ) {
			$this->doCreateField( $tableName, $fieldName, $position, $fieldType, $default );
		} elseif ( $currentFields[$fieldName] != $fieldType ) {
			$this->reportMessage( "   ... changing type of field $fieldName from '$currentFields[$fieldName]' to '$fieldType' ... " );

			$notnullposnew = strpos( $fieldType, ' NOT NULL' );

			if ( $notnullposnew > 0 ) {
				$fieldType = substr( $fieldType, 0, $notnullposnew );
			}

			$notnullposold = strpos( $currentFields[$fieldName], ' NOT NULL' );
			$typeold  = strtoupper( ( $notnullposold > 0 ) ? substr( $currentFields[$fieldName], 0, $notnullposold ) : $currentFields[$fieldName] );

			// Added USING statement to avoid
			// "Query: ALTER TABLE "smw_object_ids" ALTER COLUMN "smw_proptable_hash" TYPE BYTEA ...
			// Error: 42804 ERROR:  column "smw_proptable_hash" cannot be cast automatically to type bytea
			// HINT:  You might need to specify "USING smw_proptable_hash::bytea"."

			if ( $typeold != $fieldType ) {
				$sql = "ALTER TABLE " . $tableName . " ALTER COLUMN \"" . $fieldName . "\" TYPE " . $fieldType  . " USING \"$fieldName\"::$fieldType";
				$this->connection->query( $sql, __METHOD__ );
			}

			if ( $notnullposold != $notnullposnew ) {
				$sql = "ALTER TABLE " . $tableName . " ALTER COLUMN \"" . $fieldName . "\" " . ( $notnullposnew > 0 ? 'SET' : 'DROP' ) . " NOT NULL";
				$this->connection->query( $sql, __METHOD__ );
			}

			$this->reportMessage( "done.\n" );
		} else {
			$this->reportMessage( "   ... field $fieldName is fine.\n" );
		}
	}

	private function doCreateField( $tableName, $fieldName, $position, $fieldType, $default ) {

		$this->activityLog[$tableName][$fieldName] = self::PROC_FIELD_NEW;

		$this->reportMessage( "   ... creating field $fieldName ... " );
		$this->connection->query( "ALTER TABLE $tableName ADD \"" . $fieldName . "\" $fieldType $default", __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	private function doDropField( $tableName, $fieldName ) {

		$this->activityLog[$tableName][$fieldName] = self::PROC_FIELD_DROP;

		$this->reportMessage( "   ... deleting obsolete field $fieldName ... " );
		$this->connection->query( 'ALTER TABLE ' . $tableName . ' DROP COLUMN "' . $fieldName . '"', __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	/** Index */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doCreateIndices( $tableName, array $indexOptions = null ) {

		$indices = $indexOptions['indices'];
		$ix = [];

		// In case an index has a length restriction indexZ(200), remove it since
		// Postgres doesn't know such syntax
		foreach ( $indices as $k => $columns ) {
			$ix[$k] = preg_replace("/\([^)]+\)/", "", $columns );
		}

		$indices = $ix;

		// First remove possible obsolete indices
		$this->doDropObsoleteIndices( $tableName, $indices );

		// Add new indexes.
		foreach ( $indices as $indexName => $index ) {
			// If the index is an array, it contains the column
			// name as first element, and index type as second one.
			if ( is_array( $index ) ) {
				$columns = $index[0];
				$indexType = count( $index ) > 1 ? $index[1] : 'INDEX';
			} else {
				$columns = $index;
				$indexType = 'INDEX';
			}

			$this->doCreateIndex( $tableName, $indexType, $indexName, $columns, $indexOptions );
		}
	}

	private function doDropObsoleteIndices( $tableName, array &$indices ) {

		$tableName = $this->connection->tableName( $tableName, 'raw' );
		$currentIndices = $this->getIndexInfo( $tableName );

		foreach ( $currentIndices as $indexName => $indexColumn ) {
			// Indices may contain something like array( 'id', 'UNIQUE INDEX' )
			$id = $this->recursive_array_search( $indexColumn, $indices );
			if ( $id !== false || $indexName == 'PRIMARY' ) {
				$this->reportMessage( "   ... index $indexColumn is fine.\n" );

				if ( $id !== false ) {
					unset( $indices[$id] );
				}

			} else { // Duplicate or unrequired index.
				$this->doDropIndex( $tableName, $indexName, $indexColumn );
			}
		}
	}

	private function doCreateIndex( $tableName, $indexType, $indexName, $columns, array $indexOptions ) {

		if ( $indexType === 'FULLTEXT' ) {
			return $this->reportMessage( "   ... skipping the fulltext index creation ..." );
		}

		$tableName = $this->connection->tableName( $tableName, 'raw' );
		$indexName = $this->getCumulatedIndexName( $tableName, $columns );

		$this->reportMessage( "   ... creating new index $columns ..." );

		if ( $this->connection->indexInfo( $tableName, $indexName ) === false ) {
			$this->connection->query( "CREATE $indexType $indexName ON $tableName ($columns)", __METHOD__ );
		}

		$this->reportMessage( "done.\n" );
	}

	private function getCumulatedIndexName( $tableName, $columns ) {
		// Identifiers -- table names, column names, constraint names,
		// etc. -- are limited to a maximum length of 63 bytes
		return str_replace( '__' , '_', "{$tableName}_idx_" . str_replace( array( '_', 'smw', ',' ), array( '', '_', '_' ), $columns ) );
	}

	private function getIndexInfo( $tableName ) {

		$indices = array();

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

		$res = $this->connection->query( $sql, __METHOD__ );

		if ( !$res ) {
			return array();
		}

		foreach ( $res as $row ) {
			$indices[$row->indexname] = $row->indexcolumns;
		}

		return $indices;
	}

	private function doDropIndex( $tableName, $indexName, $columns ) {
		$this->reportMessage( "   ... removing index $columns ..." );
		$this->connection->query( 'DROP INDEX IF EXISTS ' . $indexName, __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	/** Drop */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doDropTable( $tableName ) {
		// Function: SMW\SQLStore\TableBuilder\PostgresTableBuilder::doDropTable
		// Error: 2BP01 ERROR:  cannot drop table smw_object_ids because other objects depend on it
		// DETAIL:  default for table sunittest_smw_object_ids column smw_id depends on sequence smw_object_ids_smw_id_seq
		// HINT:  Use DROP ... CASCADE to drop the dependent objects too.
		$this->connection->query( 'DROP TABLE IF EXISTS ' . $this->connection->tableName( $tableName ) . ' CASCADE', __METHOD__ );
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	protected function doOptimize( $tableName ) {

		$this->reportMessage( "Checking table $tableName ...\n" );

		// https://www.postgresql.org/docs/9.0/static/sql-analyze.html
		$this->reportMessage( "   ... analyze " );
		$this->connection->query( 'ANALYZE ' . $this->connection->tableName( $tableName ), __METHOD__ );

		$this->reportMessage( "done.\n" );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function checkOn( $event ) {
		if ( $event === self::POST_CREATION ) {
			$this->doCheckOnPostCreation();
		}
	}

	private function doCheckOnPostCreation() {

		// To avoid things like:
		// "Error: 23505 ERROR:  duplicate key value violates unique constraint "smw_object_ids_pkey""
		$sequenceIndex = SQLStore::ID_TABLE . '_smw_id_seq';
		$max = $this->connection->selectField( SQLStore::ID_TABLE, 'max(smw_id)', array(), __METHOD__ );
		$max += 1;

		$this->reportMessage( "Checking {$sequenceIndex} sequence consistency ...\n" );
		$this->reportMessage( "   ... setting sequence to {$max} ...\n" );

		$this->connection->query( "ALTER SEQUENCE {$sequenceIndex} RESTART WITH {$max}", __METHOD__ );
		$this->reportMessage( "   ... done.\n" );
	}

}
