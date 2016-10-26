<?php

namespace SMW\SQLStore\TableBuilder;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use SMW\SQLStore\TableBuilder as TableBuilderInterface;
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
abstract class TableBuilder implements TableBuilderInterface, MessageReporter {

	/**
	 * @var DatabaseBase
	 */
	protected $connection;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var string|integer
	 */
	protected $dbName;

	/**
	 * @var array
	 */
	protected $tableOptions;

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
	 * @return TableBuilder
	 * @throws RuntimeException
	 */
	public static function factory( DatabaseBase $connection ) {

		$instance = null;

		switch ( $connection->getType() ) {
			case 'mysql':
				$instance = new MySQLTableBuilder( $connection );
				break;
			case 'sqlite':
				$instance = new SQLiteTableBuilder( $connection );
				break;
			case 'postgres':
				$instance = new PostgresTableBuilder( $connection );
				break;
		}

		if ( $instance === null ) {
			throw new RuntimeException( "Unknown DB type " . $connection->getType() );
		}

		$instance->setDbName( $GLOBALS['wgDBname'] );
		$instance->setTableOptions( $GLOBALS['wgDBTableOptions'] );

		return $instance;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|integer $dbName
	 */
	public function setDbName( $dbName ) {
		$this->dbName = $dbName;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $tableOptions
	 */
	public function setTableOptions( $tableOptions ) {
		$this->tableOptions = $tableOptions;
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
	public function getStandardFieldType( $fieldType ) {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function create( Table $table ) {
		$this->createTable( $table->getName(), $table->getConfiguration() );
		$this->createIndex( $table->getName(), $table->getConfiguration() );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function drop( Table $table ) {
		$this->dropTable( $table->getName() );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
	 * @param array|null $tableOptions
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
	 * @param string $tableName
	 * @param array|null $indexOptions
	 */
	public function createIndex( $tableName, array $indexOptions = null ) {
		$this->reportMessage( "Checking index structures for table $tableName ...\n" );
		$this->doCreateIndicies( $tableName, $indexOptions );
		$this->reportMessage( "   ... done.\n" );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $tableName
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
	 * @param string $event
	 */
	public function checkOn( $event ) {
		return false;
	}

	/**
	 * @param string $tableName
	 * @param array $tableOptions
	 */
	abstract protected function doCreateTable( $tableName, array $tableOptions = null);

	/**
	 * @param string $tableName
	 * @param array $tableOptions
	 */
	abstract protected function doUpdateTable( $tableName, array $tableOptions = null );

	/**
	 * @param string $tableName
	 * @param array $indexOptions
	 */
	abstract protected function doCreateIndicies( $tableName, array $indexOptions = null );

	/**
	 * @param string $tableName
	 */
	abstract protected function doDropTable( $tableName );

}
