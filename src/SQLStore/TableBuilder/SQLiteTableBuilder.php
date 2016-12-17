<?php

namespace SMW\SQLStore\TableBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 * @author Markus Krötzsch
 * @author Marcel Gsteiger
 * @author Jeroen De Dauw
 */
class SQLiteTableBuilder extends TableBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getStandardFieldType( $fieldType ) {

		$fieldTypes = array(
			 // like page_id in MW page table
			'id'         => 'INTEGER',
			'id primary' => 'INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT',
			 // like page_namespace in MW page table
			'namespace'  => 'INT(11)',
			 // like page_title in MW page table
			'title'      => 'VARBINARY(255)',
			 // like iw_prefix in MW interwiki table
			'interwiki'  => 'TEXT',
			'iw'         => 'TEXT',
			 // larger blobs of character data, usually not subject to SELECT conditions
			'blob'       => 'MEDIUMBLOB',
			'text'       => 'TEXT',
			'boolean'    => 'TINYINT(1)',
			'double'     => 'DOUBLE',
			'integer'    => 'INT(8)',
			'char nocase'      => 'VARCHAR(255) NOT NULL COLLATE NOCASE',
			'usage count'      =>'INT(8)',
			'integer unsigned' => 'INTEGER'
		);

		return FieldType::mapType( $fieldType, $fieldTypes );
	}

	/** Create */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doCreateTable( $tableName, array $tableOptions = null ) {

		$mode = '';
		$option = '';

		$ftsOptions = null;
		$tableName = $this->connection->tableName( $tableName );

		if ( isset( $tableOptions['fulltextSearchTableOptions']['sqlite'] ) ) {
			$ftsOptions = $tableOptions['fulltextSearchTableOptions']['sqlite'];
		}

		// Filter extra module options
		// @see https://www.sqlite.org/fts3.html#fts4_options
		//
		// $smwgFulltextSearchTableOptions can define:
		// - 'sqlite' => array( 'FTS4' )
		// - 'sqlite' => array( 'FTS4', 'tokenize=porter' )
		if ( $ftsOptions !== null && is_array( $ftsOptions ) ) {
			$mode = isset( $ftsOptions[0] ) ? $ftsOptions[0] : '';
			$option = isset( $ftsOptions[1] ) ? $ftsOptions[1] : '';
		} elseif ( $ftsOptions !== null ) {
			$mode = $ftsOptions;
		}

		$fieldSql = array();
		$fields = $tableOptions['fields'];

		foreach ( $fields as $fieldName => $fieldType ) {
			$fieldSql[] = "$fieldName " . $this->getStandardFieldType( $fieldType );
		}

		if ( $mode === '' ) {
			$sql = 'CREATE TABLE ' . $tableName .'(' . implode( ',', $fieldSql ) . ') ';
		} else {
			$sql = 'CREATE VIRTUAL TABLE ' . $tableName . ' USING ' . strtolower( $mode ) .'(' . implode( ',', $fieldSql ) . $option . ') ';
		}

		$this->connection->query( $sql, __METHOD__ );
	}

	/** Update */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doUpdateTable( $tableName, array $tableOptions = null ) {

		$tableName = $this->connection->tableName( $tableName );
		$currentFields = $this->getCurrentFields( $tableName );

		$fields = $tableOptions['fields'];
		$position = 'FIRST';

		// Loop through all the field definitions, and handle each definition for either postgres or MySQL.
		foreach ( $fields as $fieldName => $fieldType ) {
			$this->doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, $tableOptions );

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

		$sql = 'PRAGMA table_info(' . $tableName . ')';

		$res = $this->connection->query( $sql, __METHOD__ );
		$currentFields = array();

		foreach ( $res as $row ) {
			$row->Field = $row->name;
			$row->Type = $row->type;
			$type = $row->type;

			if ( $row->notnull == '1' ) {
				$type .= ' NOT NULL';
			}

			if ( $row->pk == '1' ) {
				$type .= ' PRIMARY KEY AUTOINCREMENT';
			}

			$currentFields[$row->Field] = $type;
		}

		return $currentFields;
	}

	private function doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, array $tableOptions ) {

		$fieldType = $this->getStandardFieldType( $fieldType );

		if ( !array_key_exists( $fieldName, $currentFields ) ) {
			$this->doCreateField( $tableName, $fieldName, $position, $fieldType );
		} elseif ( $currentFields[$fieldName] != $fieldType ) {
			$this->doUpdateFieldType( $tableName, $fieldName, $position, $currentFields[$fieldName], $fieldType );
		} else {
			$this->reportMessage( "   ... field $fieldName is fine.\n" );
		}
	}

	private function doCreateField( $tableName, $fieldName, $position, $fieldType ) {

		if ( strpos( $tableName, 'ft_search' ) !== false ) {
			return $this->reportMessage( "   ... virtual tables can not be altered in SQLite ...\n" );
		}

		// @see https://www.sqlite.org/lang_altertable.html states that
		// "If a NOT NULL constraint is specified, then the column must have a default value other than NULL."
		$default = "DEFAULT NULL";

		// Add DEFAULT '' to avoid
		// Query: ALTER TABLE sunittest_rdbms_test ADD `t_num` INT(8) NOT NULL
		// Function: SMW\SQLStore\TableBuilder\SQLiteTableBuilder::doCreateField
		// Error: 1 Cannot add a NOT NULL column with default value NULL
		if ( strpos( $fieldType, 'NOT NULL' ) !== false ) {
			$default = "DEFAULT ''";
		}

		$this->reportMessage( "   ... creating field $fieldName ... " );
		$this->connection->query( "ALTER TABLE $tableName ADD `$fieldName` $fieldType $default", __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	private function doUpdateFieldType( $tableName, $fieldName, $position, $oldFieldType, $newFieldType ) {
		$this->reportMessage( "   ... changing field type is not supported in SQLite (http://www.sqlite.org/omitted.html) \n" );
		$this->reportMessage( "       Please delete and reinitialize the tables to remove obsolete data, or just keep it.\n" );
	}

	private function doDropField( $tableName, $fieldName ) {
		$this->reportMessage( "   ... deleting obsolete field $fieldName is not possible in SQLite.\n" );
		$this->reportMessage( "       Please delete and reinitialize the tables to remove obsolete data, or just keep it.\n" );
		$this->reportMessage( "done.\n" );
	}

	/** Index */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doCreateIndicies( $tableName, array $indexOptions = null ) {

		$indicies = $indexOptions['indicies'];

		// First remove possible obsolete indicies
		$this->doDropObsoleteIndicies( $tableName, $indicies );

		// Add new indexes.
		foreach ( $indicies as $indexName => $index ) {
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

	private function doDropObsoleteIndicies( $tableName, array &$indicies ) {

		$currentIndicies = $this->getIndexInfo( $tableName );

		// TODO We do not currently get the right column definitions in
		// SQLite; hence we can only drop all indexes. Wasteful.
		foreach ( $currentIndicies as $indexName => $indexColumn ) {
			$this->doDropIndex( $tableName, $indexName, $indexColumn );
		}
	}

	private function getIndexInfo( $tableName ) {

		$tableName = $this->connection->tableName( $tableName );
		$indices = array();

		$res = $this->connection->query( 'PRAGMA index_list(' . $tableName . ')', __METHOD__ );

		if ( !$res ) {
			return array();
		}

		foreach ( $res as $row ) {
			/// FIXME The value should not be $row->name below?!
			if ( !array_key_exists( $row->name, $indices ) ) {
				$indices[$row->name] = $row->name;
			} else {
				$indices[$row->name] .= ',' . $row->name;
			}
		}

		return $indices;
	}

	private function doDropIndex( $tableName, $indexName, $columns ) {
		$this->reportMessage( "   ... removing index $columns ..." );
		$this->connection->query( 'DROP INDEX ' . $indexName, __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	private function doCreateIndex( $tableName, $indexType, $indexName, $columns, array $indexOptions ) {

		if ( $indexType === 'FULLTEXT' ) {
			return $this->reportMessage( "   ... skipping the fulltext index creation ..." );
		}

		if ( strpos( $tableName, 'ft_search' ) !== false ) {
			return $this->reportMessage( "   ... virtual tables can not be altered in SQLite ...\n" );
		}

		$tableName = $this->connection->tableName( $tableName );
		$indexName = "{$tableName}_index{$indexName}";

		$this->reportMessage( "   ... creating new index $columns ..." );
		$this->connection->query( "CREATE $indexType $indexName ON $tableName ($columns)", __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	/** Drop */

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	protected function doDropTable( $tableName ) {
		$this->connection->query( 'DROP TABLE ' . $this->connection->tableName( $tableName ), __METHOD__ );
	}

}
