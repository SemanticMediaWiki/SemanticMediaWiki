<?php

namespace SMW\SQLStore\TableBuilder;

use Onoi\MessageReporter\MessageReporter;
use DatabaseBase;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author Marcel Gsteiger
 * @author Jeroen De Dauw
 * @author mwjames
 */
abstract class RdbmsTableBuilder implements TableBuilder, MessageReporter {

	/**
	 * @var DatabaseBase
	 */
	protected $connection;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 2.5
	 *
	 * @param DatabaseBase $connection
	 */
	protected function __construct( DatabaseBase $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 2.5
	 *
	 * @param DatabaseBase $connection
	 *
	 * @return RdbmsTableBuilder
	 * @throws RuntimeException
	 */
	public static function factory( DatabaseBase $connection ) {

		$instance = null;

		switch ( $connection->getType() ) {
			case 'mysql':
				$instance = new MySQLRdbmsTableBuilder( $connection );
				break;
			case 'sqlite':
				$instance = new SQLiteRdbmsTableBuilder( $connection );
				break;
			case 'postgres':
				$instance = new PostgresRdbmsTableBuilder( $connection );
				break;
		}

		if ( $instance === null ) {
			throw new RuntimeException( "Unknown DB type " . $connection->getType() );
		}

		return $instance;
	}

	/**
	 * @see MessageReporterAware::setMessageReporter
	 *
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @see MessageReporter::reportMessage
	 *
	 * @since 2.5
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {

		if ( $this->messageReporter === null ) {
			return;
		}

		$this->messageReporter->reportMessage( $message );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getStandardFieldType( $input ) {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function createTable( $tableName, array $tableOptions = null ) {

		$this->reportMessage( "Checking table $tableName ...\n" );

		if ( $this->connection->tableExists( $tableName ) === false ) { // create new table
			$this->reportMessage( "   Table not found, now creating...\n" );
			$this->doCreateTable( $tableName, $tableOptions );
		} else {
			$this->reportMessage( "   Table already exists, checking structure ...\n" );
			$this->doUpdateTable( $tableName, $tableOptions );
		}

		$this->reportMessage( "   ... done.\n" );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function dropTable( $tableName ) {

		if ( $this->connection->tableExists( $tableName ) === false ) { // create new table
			return $this->reportMessage( " ... $tableName not found, skipping removal.\n" );
		}

		$this->doDropTable( $tableName );
		$this->reportMessage( " ... dropped table $tableName.\n" );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function createIndex( $tableName, array $indexOptions = null ) {

		$this->reportMessage( "Checking index structures for table $tableName ...\n" );
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

			$this->doCreateIndex( $tableName, $indexType, $indexName, $columns );
		}

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	/**
	 * @param array $tableOptions
	 */
	abstract protected function getSQLFromDBName( array $tableOptions );

	/**
	 * @param array $tableOptions
	 */
	abstract protected function getSQLFromDBTableOptions( array $tableOptions );

	/**
	 * Returns an array of fields (as keys) and their types (as values).
	 *
	 * @param string $tableName
	 */
	abstract protected function getCurrentFields( $tableName );

	/**
	 * Update a single field given it's name and type and an array of
	 * current fields.
	 *
	 * @param string $tableName
	 */
	abstract protected function doUpdateField( $tableName, $fieldName, $fieldType, $currentFields, $positon, array $tableOptions );

	/**
	 * @param string $tableName
	 * @param string $fieldName
	 */
	abstract protected function doDropTable( $tableName );

	/**
	 * @param string $tableName
	 * @param string $fieldName
	 */
	abstract protected function doDropField( $tableName, $fieldName );

	/**
	 * @param string $tableName
	 * @param array &$indicies
	 */
	abstract protected function doDropObsoleteIndicies( $tableName, array &$indicies );

	/**
	 * Create an index using the suitable SQL for various RDBMS
	 *
	 * @param string $tableName
	 * @param string $indexType
	 * @param string $indexName
	 * @param $columns
	 */
	abstract protected function doCreateIndex( $tableName, $indexType, $indexName, $columns );

	private function doCreateTable( $tableName, array $tableOptions = null ) {

		$tableName = $this->connection->tableName( $tableName );

		$sql = 'CREATE TABLE ' . $this->getSQLFromDBName( $tableOptions ) . $tableName . ' (';

		$fieldSql = array();
		$fields = $tableOptions['fields'];

		foreach ( $fields as $fieldName => $fieldType ) {
			$fieldSql[] = "$fieldName  $fieldType";
		}

		$sql .= implode( ',', $fieldSql ) . ') ';
		$sql .= $this->getSQLFromDBTableOptions( $tableOptions );

		$this->connection->query( $sql, __METHOD__ );
	}

	private function doUpdateTable( $tableName, array $tableOptions = null ) {

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
				$this->reportMessage( "   ... deleting obsolete field $fieldName ... " );
				$this->doDropField( $tableName, $fieldName );
				$this->reportMessage( "done.\n" );
			}
		}
	}

}
