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
class MySQLTableBuilder extends TableBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getStandardFieldType( $fieldType ) {

		$charLongLength = FieldType::CHAR_LONG_LENGTH;

		$fieldTypes = array(
			 // like page_id in MW page table
			'id'         => 'INT(8) UNSIGNED',
			 // like page_id in MW page table
			'id primary' => 'INT(8) UNSIGNED NOT NULL KEY AUTO_INCREMENT',
			 // like page_namespace in MW page table
			'namespace'  => 'INT(11)',
			 // like page_title in MW page table
			'title'      => 'VARBINARY(255)',
			 // like iw_prefix in MW interwiki table
			'interwiki'  => 'VARBINARY(32)',
			'iw'         => 'VARBINARY(32)',
			 // larger blobs of character data, usually not subject to SELECT conditions
			'blob'       => 'MEDIUMBLOB',
			'text'       => 'TEXT',
			'boolean'    => 'TINYINT(1)',
			'double'     => 'DOUBLE',
			'integer'    => 'INT(8)',
			'char long'  => "VARBINARY($charLongLength)",
			'char nocase'      => 'VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci',
			'char long nocase' => "VARCHAR($charLongLength) CHARSET utf8 COLLATE utf8_general_ci",
			'usage count'      => 'INT(8) UNSIGNED',
			'integer unsigned' => 'INT(8) UNSIGNED'
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

		$tableName = $this->connection->tableName( $tableName );
		$sql = '';

		$fieldSql = array();
		$fields = $tableOptions['fields'];

		foreach ( $fields as $fieldName => $fieldType ) {
			$fieldSql[] = "$fieldName " . $this->getStandardFieldType( $fieldType );
		}

		// @see $wgDBname
		$dbName = isset( $this->configurations['wgDBname'] ) ? "`". $this->configurations['wgDBname'] . "`." : '';

		$sql .= 'CREATE TABLE ' . $dbName . $tableName . ' (' . implode( ',', $fieldSql ) . ') ';
		$sql .= $this->createExtraSQLFromTableOptions( $tableOptions );

		$this->connection->query( $sql, __METHOD__ );
	}

	private function createExtraSQLFromTableOptions( array $tableOptions ) {

		// $smwgFulltextSearchTableOptions can define:
		// - 'mysql' => array( 'ENGINE=MyISAM, DEFAULT CHARSET=utf8' )
		// - 'mysql' => array( 'ENGINE=MyISAM, DEFAULT CHARSET=utf8', 'WITH PARSER ngram' )
		if ( isset( $tableOptions['fulltextSearchTableOptions']['mysql'] ) ) {

			$tableOption = $tableOptions['fulltextSearchTableOptions']['mysql'];

			// By convention the first index has table specific relevance
			if ( is_array( $tableOption ) ) {
				$tableOption = isset( $tableOption[0] ) ? $tableOption[0] : '';
			}

			return $tableOption;
		}

		// @see $wgDBTableOptions, This replacement is needed for compatibility,
		// http://bugs.mysql.com/bug.php?id=17501
		if ( isset( $this->configurations['wgDBTableOptions'] ) ) {
			return str_replace( 'TYPE', 'ENGINE', $this->configurations['wgDBTableOptions'] );
		}
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

		// Loop through all the field definitions, and handle each definition
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

		$sql = 'DESCRIBE ' . $tableName;

		$res = $this->connection->query( $sql, __METHOD__ );
		$currentFields = array();

		foreach ( $res as $row ) {
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

			$currentFields[$row->Field] = $type;
		}

		return $currentFields;
	}

	private function doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, array $tableOptions ) {

		if ( !isset( $this->processLog[$tableName] ) ) {
			$this->processLog[$tableName] = array();
		}

		$fieldType = $this->getStandardFieldType( $fieldType );
		$default = '';

		if ( isset( $tableOptions['defaults'][$fieldName] ) ) {
			$default = "DEFAULT '" . $tableOptions['defaults'][$fieldName] . "'";
		}

		if ( !array_key_exists( $fieldName, $currentFields ) ) {
			$this->doCreateField( $tableName, $fieldName, $position, $fieldType, $default );
		} elseif ( $currentFields[$fieldName] != $fieldType ) {
			$this->doUpdateFieldType( $tableName, $fieldName, $position, $currentFields[$fieldName], $fieldType );
		} else {
			$this->reportMessage( "   ... field $fieldName is fine.\n" );
		}
	}

	private function doCreateField( $tableName, $fieldName, $position, $fieldType, $default ) {

		$this->processLog[$tableName][$fieldName] = self::PROC_FIELD_NEW;

		$this->reportMessage( "   ... creating field $fieldName ... " );
		$this->connection->query( "ALTER TABLE $tableName ADD `$fieldName` $fieldType $default $position", __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	private function doUpdateFieldType( $tableName, $fieldName, $position, $oldFieldType, $newFieldType ) {
		$this->reportMessage( "   ... changing type of field $fieldName from '$oldFieldType' to '$newFieldType' ... " );

		// To avoid Error: 1068 Multiple primary key defined when a PRIMARY is involved
		if ( strpos( $newFieldType, 'AUTO_INCREMENT' ) !== false ) {
			$this->connection->query( "ALTER TABLE $tableName DROP PRIMARY KEY", __METHOD__ );
		}

		$this->connection->query( "ALTER TABLE $tableName CHANGE `$fieldName` `$fieldName` $newFieldType $position", __METHOD__ );

		// http://stackoverflow.com/questions/1873085/how-to-convert-from-varbinary-to-char-varchar-in-mysql
		// http://bugs.mysql.com/bug.php?id=34564
		if ( strpos( $oldFieldType, 'VARBINARY' ) !== false && strpos( $newFieldType, 'VARCHAR' ) !== false ) {
		//	$this->connection->query( "SELECT CAST($fieldName AS CHAR) from $tableName", __METHOD__ );
		}

		$this->reportMessage( "done.\n" );
	}

	private function doDropField( $tableName, $fieldName ) {
		$this->reportMessage( "   ... deleting obsolete field $fieldName ... " );
		$this->connection->query( "ALTER TABLE $tableName DROP COLUMN `$fieldName`", __METHOD__ );
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

		$tableName = $this->connection->tableName( $tableName );
		$currentIndices = $this->getIndexInfo( $tableName );

		$idx = [];

		// #2717
		// The index info doesn't return length information (...idx1(200),idx2...)
		// for an index hence to avoid a constant remove/create cycle we eliminate
		// the length information from the temporary mirror when comparing new and
		// old; of course we won't detect length changes!
		foreach ( $indices as $k => $columns ) {
			$idx[$k] = preg_replace("/\([^)]+\)/", "", $columns );
		}

		foreach ( $currentIndices as $indexName => $indexColumn ) {
			// Indices may contain something like array( 'id', 'UNIQUE INDEX' )
			$id = $this->recursive_array_search( $indexColumn, $idx );
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

	/**
	 * Get the information about all indexes of a table. The result is an
	 * array of format indexname => indexcolumns. The latter is a comma
	 * separated list.
	 *
	 * @return array indexname => columns
	 */
	private function getIndexInfo( $tableName ) {

		$indices = array();

		$res = $this->connection->query( 'SHOW INDEX FROM ' . $tableName, __METHOD__ );

		if ( !$res ) {
			return $indices;
		}

		foreach ( $res as $row ) {
			if ( !array_key_exists( $row->Key_name, $indices ) ) {
				$indices[$row->Key_name] = $row->Column_name;
			} else {
				$indices[$row->Key_name] .= ',' . $row->Column_name;
			}
		}

		return $indices;
	}

	private function doDropIndex( $tableName, $indexName, $columns ) {
		$this->reportMessage( "   ... removing index $columns ..." );
		$this->connection->query( 'DROP INDEX ' . $indexName . ' ON ' . $tableName, __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	private function doCreateIndex( $tableName, $indexType, $indexName, $columns, array $indexOptions ) {

		$tableName = $this->connection->tableName( $tableName );
		$indexOption = '';

		$this->reportMessage( "   ... creating new index $columns ..." );

		// @see MySQLTableBuilder::createExtraSQLFromTableOptions
		// @see https://dev.mysql.com/doc/refman/5.7/en/fulltext-search-ngram.html
		if ( isset( $indexOptions['fulltextSearchTableOptions']['mysql'] ) ) {
			$indexOption = $indexOptions['fulltextSearchTableOptions']['mysql'];

			// By convention the second index has index specific relevance
			if ( is_array( $indexOption ) ) {
				$indexOption = isset( $indexOption[1] ) ? $indexOption[1] : '';
			}
		}

		if ( $indexType === 'FULLTEXT' ) {
			$this->connection->query( "ALTER TABLE $tableName ADD $indexType $columns ($columns) $indexOption", __METHOD__ );
		} else {
			$this->connection->query( "ALTER TABLE $tableName ADD $indexType ($columns)", __METHOD__ );
		}

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

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	protected function doOptimize( $tableName ) {

		$this->reportMessage( "   Table $tableName ...\n" );

		// https://dev.mysql.com/doc/refman/5.7/en/analyze-table.html
		// Performs a key distribution analysis and stores the distribution for
		// the named table or tables
		$this->reportMessage( "   ... analyze" );
		$this->connection->query( 'ANALYZE TABLE ' . $this->connection->tableName( $tableName ), __METHOD__ );

		// https://dev.mysql.com/doc/refman/5.7/en/optimize-table.html
		// Reorganizes the physical storage of table data and associated index data,
		// to reduce storage space and improve I/O efficiency
		$this->reportMessage( ", optimize " );
		$this->connection->query( 'OPTIMIZE TABLE ' . $this->connection->tableName( $tableName ), __METHOD__ );

		$this->reportMessage( "done.\n" );
	}

}
