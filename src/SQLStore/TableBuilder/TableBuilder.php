<?php

namespace SMW\SQLStore\TableBuilder;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use RuntimeException;
use SMW\SQLStore\TableBuilder as TableBuilderInterface;
use SMW\Utils\CliMsgFormatter;
use Wikimedia\Rdbms\IDatabase;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
abstract class TableBuilder implements TableBuilderInterface, MessageReporterAware, MessageReporter {

	private ?MessageReporter $messageReporter = null;

	/**
	 * @var array
	 */
	protected $config = [];

	/**
	 * @var array
	 */
	protected $activityLog = [];

	protected ?array $droppedTables;

	/**
	 * @since 2.5
	 */
	protected function __construct( protected $connection ) {
	}

	/**
	 * @since 2.5
	 *
	 * @param IDatabase $connection
	 *
	 * @return TableBuilder
	 * @throws RuntimeException
	 */
	public static function factory( $connection ): TableBuilder {
		if ( !$connection instanceof IDatabase ) {
			throw new RuntimeException( "Invalid connection instance!" );
		}

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

		if ( !is_a( $instance, static::class ) ) {
			throw new RuntimeException( get_class( $instance ) . " instance doesn't match " . static::class );
		}

		$instance->setConfig( 'wgDBname', $GLOBALS['wgDBname'] );
		$instance->setConfig( 'wgDBTableOptions', $GLOBALS['wgDBTableOptions'] );

		return $instance;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|int $key
	 * @param mixed $value
	 */
	public function setConfig( $key, $value ): void {
		$this->config[$key] = $value;
	}

	/**
	 * @see MessageReporterAware::setMessageReporter
	 *
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ): void {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @see MessageReporter::reportMessage
	 *
	 * @since 2.5
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ): void {
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
	public function getStandardFieldType( $fieldType ): string|false {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function create( Table $table ): void {
		$attributes = $table->getAttributes();
		$tableName = $table->getName();

		$this->reportMessage( "Checking table $tableName ...\n" );

		if ( $this->connection->tableExists( $tableName, __METHOD__ ) === false ) { // create new table
			$this->reportMessage( "   Table not found, now creating...\n" );
			$this->doCreateTable( $tableName, $attributes );
		} else {
			$this->reportMessage( "   Table already exists, checking structure ...\n" );
			$this->doUpdateTable( $tableName, $attributes );
		}

		$this->reportMessage( "   ... done.\n" );

		if ( !isset( $attributes['indices'] ) ) {
			$this->reportMessage( "No index structures for table $tableName ...\n" );
			return;
		}

		$this->reportMessage( "Checking index structures for table $tableName ...\n" );
		$this->doCreateIndices( $tableName, $attributes );

		$this->reportMessage( "   ... done.\n" );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function drop( Table $table ): void {
		$cliMsgFormatter = new CliMsgFormatter();

		if ( !isset( $this->droppedTables ) ) {
			$this->droppedTables = [];
		}

		$tableName = $table->getName();

		if ( isset( $this->droppedTables[$tableName] ) ) {
			return;
		}

		$this->droppedTables[$tableName] = true;

		if ( $this->connection->tableExists( $tableName, __METHOD__ ) === false ) { // create new table
			$this->reportMessage(
				$cliMsgFormatter->twoCols( "... $tableName (not found) ...", 'SKIPPED', 3 )
			);
			return;
		}

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... $tableName ...", 3 )
		);

		$this->doDropTable( $tableName );

		$this->reportMessage(
			$cliMsgFormatter->secondCol( 'REMOVED' )
		);
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function optimize( Table $table ): void {
		$this->doOptimize( $table->getName() );
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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getLog(): array {
		return $this->activityLog;
	}

	/**
	 * @param string $tableName
	 * @param array $tableOptions
	 */
	abstract protected function doCreateTable( $tableName, array $tableOptions ): void;

	/**
	 * @param string $tableName
	 * @param array $tableOptions
	 */
	abstract protected function doUpdateTable( $tableName, array $tableOptions ): void;

	/**
	 * @param string $tableName
	 * @param array $indexOptions
	 */
	abstract protected function doCreateIndices( $tableName, array $indexOptions ): void;

	/**
	 * @param string $tableName
	 */
	abstract protected function doDropTable( $tableName ): void;

	/**
	 * @param string $tableName
	 */
	abstract protected function doOptimize( $tableName ): void;

	// #1978
	// http://php.net/manual/en/function.array-search.php
	protected function recursive_array_search( $needle, $haystack ) {
		foreach ( $haystack as $key => $value ) {
			$current_key = $key;

			if ( $needle === $value || ( is_array( $value ) && $this->recursive_array_search( $needle, $value ) !== false ) ) {
				return $current_key;
			}
		}

		return false;
	}

}
