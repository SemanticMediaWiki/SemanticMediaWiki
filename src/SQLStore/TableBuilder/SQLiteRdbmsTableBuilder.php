<?php

namespace SMW\SQLStore\TableBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SQLiteRdbmsTableBuilder extends MySQLRdbmsTableBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getStandardFieldType( $input ) {

		switch ( $input ) {
			case 'id':
			return 'INTEGER'; // like page_id in MW page table
			case 'id primary':
			return 'INTEGER' . ' NOT NULL PRIMARY KEY AUTOINCREMENT'; // like page_id in MW page table
			case 'namespace':
			return 'INT(11)'; // like page_namespace in MW page table
			case 'title':
			return 'VARBINARY(255)'; // like page_title in MW page table
			case 'iw':
			return 'TEXT'; // like iw_prefix in MW interwiki table
			case 'blob':
			return 'MEDIUMBLOB'; // larger blobs of character data, usually not subject to SELECT conditions
			case 'boolean':
			return 'TINYINT(1)';
			case 'double':
			return 'DOUBLE';
			case 'integer':
			return 'INT(8)';
			case 'usage count':
			return 'INT(8)';
			case 'integer unsigned':
			return 'INTEGER';
			case 'sort':
			return 'VARCHAR(255)';
		}

		return false;
	}

	/**
	 * @see MySQLRdbmsTableBuilder::getSQLFromDBName
	 */
	protected function getSQLFromDBName( array $tableOptions ) {
		return '';
	}

	/**
	 * @see MySQLRdbmsTableBuilder::getSQLFromDBTableOptions
	 */
	protected function getSQLFromDBTableOptions( array $tableOptions ) {
		return '';
	}

	/**
	 * @see MySQLRdbmsTableBuilder::getCurrentFields
	 */
	protected function getCurrentFields( $tableName ) {

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

	/**
	 * @see MySQLRdbmsTableBuilder::doCreateField
	 */
	protected function doCreateField( $tableName, $fieldName, $position, $fieldType ) {
		$this->reportMessage( "   ... creating field $fieldName ... " );
		$this->connection->query( "ALTER TABLE $tableName ADD `$fieldName` $fieldType", __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	/**
	 * @see MySQLRdbmsTableBuilder::doUpdateFieldType
	 */
	protected function doUpdateFieldType( $tableName, $fieldName, $position, $oldFieldType, $newFieldType ) {
		$this->reportMessage( "   ... changing field type is not supported in SQLite (http://www.sqlite.org/omitted.html) \n" );
		$this->reportMessage( "       Please delete and reinitialize the tables to remove obsolete data, or just keep it.\n" );
	}

	/**
	 * @see MySQLRdbmsTableBuilder::doDropField
	 */
	protected function doDropField( $tableName, $fieldName ) {
		$this->reportMessage( "   ... deleting obsolete field $fieldName not possible in SQLite.\n" );
		$this->reportMessage( "       Please could delete and reinitialize the tables to remove obsolete data, or just keep it.\n" );
	}

	/**
	 * @see MySQLRdbmsTableBuilder::doDropObsoleteIndicies
	 */
	protected function doDropObsoleteIndicies( $tableName, array &$indicies ) {

		$currentIndicies = $this->getIndexInfo( $tableName );

		// TODO We do not currently get the right column definitions in
		// SQLite; hence we can only drop all indexes. Wasteful.
		foreach ( $currentIndicies as $indexName => $indexColumn ) {
			$this->doDropIndex( $tableName, $indexName, $indexColumn );
		}
	}

	/**
	 * @see MySQLRdbmsTableBuilder::doCreateIndex
	 */
	protected function doCreateIndex( $tableName, $indexType, $indexName, $columns, array $indexOptions ) {

		if ( $indexType === 'FULLTEXT' ) {
			return $this->reportMessage( "   ... skipping the fulltext index creation ..." );
		}

		$tableName = $this->connection->tableName( $tableName );
		$indexName = "{$tableName}_index{$indexName}";

		$this->reportMessage( "   ... creating new index $columns ..." );
		$this->connection->query( "CREATE $indexType $indexName ON $tableName ($columns)", __METHOD__ );
		$this->reportMessage( "done.\n" );
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

}
