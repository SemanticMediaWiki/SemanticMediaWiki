<?php

namespace SMW\SQLStore\TableBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PostgresRdbmsTableBuilder extends RdbmsTableBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getStandardFieldType( $input ) {

		switch ( $input ) {
			case 'id':
			return 'SERIAL'; // like page_id in MW page table
			case 'id primary':
			return 'SERIAL' . ' NOT NULL PRIMARY KEY'; // like page_id in MW page table
			case 'namespace':
			return 'BIGINT'; // like page_namespace in MW page table
			case 'title':
			return 'TEXT'; // like page_title in MW page table
			case 'iw':
			return 'TEXT'; // like iw_prefix in MW interwiki table
			case 'blob':
			return 'BYTEA'; // larger blobs of character data, usually not subject to SELECT conditions
			case 'boolean':
			return 'BOOLEAN';
			case 'double':
			return 'DOUBLE PRECISION';
			case 'integer':
			case 'usage count':
			return 'bigint';
			case 'integer unsigned':
			return 'INTEGER';
			case 'sort':
			return 'TEXT';
		}

		return false;
	}

	/**
	 * @see RdbmsTableBuilder::getSQLFromDBName
	 */
	protected function getSQLFromDBName( array $tableOptions ) {
		return '';
	}

	/**
	 * @see RdbmsTableBuilder::getSQLFromDBTableOptions
	 */
	protected function getSQLFromDBTableOptions( array $tableOptions ) {
		return '';
	}

	/**
	 * @see RdbmsTableBuilder::getCurrentFields
	 */
	protected function getCurrentFields( $tableName ) {

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

	/**
	 * @see RdbmsTableBuilder::doDropTable
	 */
	protected function doDropTable( $tableName ) {
		$this->connection->query( 'DROP TABLE IF EXISTS ' . $this->connection->tableName( $tableName ), __METHOD__ );
	}

	/**
	 * @see RdbmsTableBuilder::doUpdateField
	 */
	protected function doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, array $tableOptions ) {

		$keypos = strpos( $fieldType, ' PRIMARY KEY' );

		if ( $keypos > 0 ) {
			$fieldType = substr( $fieldType, 0, $keypos );
		}

		$fieldType = strtoupper( $fieldType );

		if ( !array_key_exists( $fieldName, $currentFields ) ) {
			$this->reportMessage( "   ... creating field $fieldName ... " );
			$this->connection->query( "ALTER TABLE $tableName ADD \"" . $fieldName . "\" $fieldType", __METHOD__ );
			$this->reportMessage( "done.\n" );
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

	/**
	 * @see RdbmsTableBuilder::doDropField
	 */
	protected function doDropField( $tableName, $fieldName ) {
		$this->connection->query( 'ALTER TABLE ' . $tableName . ' DROP COLUMN "' . $fieldName . '"', __METHOD__ );
	}

	/**
	 * @see RdbmsTableBuilder::doDropObsoleteIndicies
	 */
	protected function doDropObsoleteIndicies( $tableName, array &$indicies ) {

		$tableName = $this->connection->tableName( $tableName, 'raw' );
		$currentIndicies = $this->getIndexInfo( $tableName );

		foreach ( $currentIndicies as $indexName => $indexColumn ) {
			$id = array_search( $indexColumn, $indicies );
			if ( $id !== false || $indexName == 'PRIMARY' ) {
				$this->reportMessage( "   ... index $indexColumn is fine.\n" );

				if ( $id !== false ) {
					unset( $indicies[$id] );
				}

			} else { // Duplicate or unrequired index.
				$this->doDropIndex( $tableName, $indexName, $indexColumn );
			}
		}
	}

	/**
	 * @see RdbmsTableBuilder::doCreateIndex
	 */
	protected function doCreateIndex( $tableName, $indexType, $indexName, $columns, array $indexOptions ) {

		if ( $indexType === 'FULLTEXT' ) {
			return $this->reportMessage( "   ... skipping the fulltext index creation ..." );
		}

		$tableName = $this->connection->tableName( $tableName, 'raw' );
		$indexName = "{$tableName}_index{$indexName}";

		$this->reportMessage( "   ... creating new index $columns ..." );

		if ( $this->connection->indexInfo( $tableName, $indexName ) === false ) {
			$this->connection->query( "CREATE $indexType $indexName ON $tableName ($columns)", __METHOD__ );
		}

		$this->reportMessage( "done.\n" );
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

}
