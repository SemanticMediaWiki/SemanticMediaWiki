<?php

namespace SMW\SQLStore\TableBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MySQLRdbmsTableBuilder extends RdbmsTableBuilder {

	/**
	 * @see RdbmsTableBuilder::getStandardFieldType
	 */
	public function getStandardFieldType( $input ) {

		switch ( $input ) {
			case 'id':
			return 'INT(8) UNSIGNED'; // like page_id in MW page table
			case 'id primary':
			return 'INT(8) UNSIGNED' . ' NOT NULL KEY AUTO_INCREMENT'; // like page_id in MW page table
			case 'namespace':
			return 'INT(11)'; // like page_namespace in MW page table
			case 'title':
			return 'VARBINARY(255)'; // like page_title in MW page table
			case 'iw':
			return 'VARBINARY(32)'; // like iw_prefix in MW interwiki table
			case 'blob':
			return 'MEDIUMBLOB'; // larger blobs of character data, usually not subject to SELECT conditions
			case 'boolean':
			return 'TINYINT(1)';
			case 'double':
			return 'DOUBLE';
			case 'integer':
			return 'INT(8)';
			case 'usage count':
			return 'INT(8) UNSIGNED';
			case 'integer unsigned':
			return 'INT(8) UNSIGNED';
			case 'sort':
			return 'VARCHAR(255)';
		}

		return false;
	}

	/**
	 * @see RdbmsTableBuilder::getSQLFromDBName
	 */
	protected function getSQLFromDBName( array $tableOptions ) {
		global $wgDBname;
		return "`$wgDBname`.";
	}

	/**
	 * @see RdbmsTableBuilder::getSQLFromDBTableOptions
	 */
	protected function getSQLFromDBTableOptions( array $tableOptions ) {
		// This replacement is needed for compatibility, see http://bugs.mysql.com/bug.php?id=17501
		return str_replace( 'TYPE', 'ENGINE', $GLOBALS['wgDBTableOptions'] );
	}

	/**
	 * @see RdbmsTableBuilder::getCurrentFields
	 */
	protected function getCurrentFields( $tableName ) {

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

	/**
	 * @see RdbmsTableBuilder::doDropTable
	 */
	protected function doDropTable( $tableName ) {
		$this->connection->query( 'DROP TABLE ' . $this->connection->tableName( $tableName ), __METHOD__ );
	}

	/**
	 * @see RdbmsTableBuilder::doUpdateField
	 */
	protected function doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $position, array $tableOptions ) {

		if ( !array_key_exists( $fieldName, $currentFields ) ) {
			$this->doCreateField( $tableName, $fieldName, $position, $fieldType );
		} elseif ( $currentFields[$fieldName] != $fieldType ) {
			$this->doUpdateFieldType( $tableName, $fieldName, $position, $currentFields[$fieldName], $fieldType );
		} else {
			$this->reportMessage( "   ... field $fieldName is fine.\n" );
		}
	}

	protected function doCreateField( $tableName, $fieldName, $position, $fieldType ) {
		$this->reportMessage( "   ... creating field $fieldName ... " );
		$this->connection->query( "ALTER TABLE $tableName ADD `$fieldName` $fieldType $position", __METHOD__ );
		$this->reportMessage( "done.\n" );
	}

	protected function doUpdateFieldType( $tableName, $fieldName, $position, $oldFieldType, $newFieldType ) {
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

	/**
	 * @see RdbmsTableBuilder::doDropField
	 */
	protected function doDropField( $tableName, $fieldName ) {
		$this->connection->query( "ALTER TABLE $tableName DROP COLUMN `$fieldName`", __METHOD__ );
	}

	/**
	 * @see RdbmsTableBuilder::doDropObsoleteIndicies
	 */
	protected function doDropObsoleteIndicies( $tableName, array &$indicies ) {

		$tableName = $this->connection->tableName( $tableName );
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
	protected function doCreateIndex( $tableName, $indexType, $indexName, $columns ) {

		$tableName = $this->connection->tableName( $tableName );

		$this->reportMessage( "   ... creating new index $columns ..." );
		$this->connection->query( "ALTER TABLE $tableName ADD $indexType ($columns)", __METHOD__ );
		$this->reportMessage( "done.\n" );
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

}
