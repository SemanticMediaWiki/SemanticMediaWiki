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
	 * @var array
	 */
	protected $configurations = array();

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
			throw new RuntimeException( "Unknown or unsupported DB type " . $connection->getType() );
		}

		$instance->addConfiguration( 'wgDBname', $GLOBALS['wgDBname'] );
		$instance->addConfiguration( 'wgDBTableOptions', $GLOBALS['wgDBTableOptions'] );

		return $instance;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|integer $key
	 * @param mixed
	 */
	public function addConfiguration( $key, $value ) {
		$this->configurations[$key] = $value;
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

		$configuration = $table->getConfiguration();
		$tableName = $table->getName();

		$this->reportMessage( "Checking table $tableName ...\n" );

		if ( $this->connection->tableExists( $tableName ) === false ) { // create new table
			$this->reportMessage( "   Table not found, now creating...\n" );
			$this->doCreateTable( $tableName, $configuration );
		} else {
			$this->reportMessage( "   Table already exists, checking structure ...\n" );
			$this->doUpdateTable( $tableName, $configuration );
		}

		$this->reportMessage( "   ... done.\n" );

		if ( !isset( $configuration['indicies'] ) ) {
			return $this->reportMessage( "No index structures for table $tableName ...\n" );
		}

		$this->reportMessage( "Checking index structures for table $tableName ...\n" );
		$this->doCreateIndicies( $tableName, $configuration );

		$this->reportMessage( "   ... done.\n" );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function drop( Table $table ) {

		$tableName = $table->getName();

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

	// #1978
	// http://php.net/manual/en/function.array-search.php
	protected function recursive_array_search( $needle, $haystack ) {
		foreach( $haystack as $key => $value ) {
			$current_key = $key;

			if ( $needle === $value or ( is_array( $value ) && $this->recursive_array_search( $needle, $value ) !== false ) ) {
				return $current_key;
			}
		}

		return false;
	}

}
