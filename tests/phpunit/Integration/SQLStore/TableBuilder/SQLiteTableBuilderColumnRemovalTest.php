<?php

namespace SMW\Tests\Integration\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\DatabaseSqlite;

/**
 * Needs a real SQLite database, as a mocked connection does not quote table names
 * the way SQLite does and the wiki database of the test run may not be SQLite.
 *
 * @covers \SMW\SQLStore\TableBuilder\SQLiteTableBuilder
 * @requires extension pdo_sqlite
 *
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.4.0
 */
class SQLiteTableBuilderColumnRemovalTest extends SMWIntegrationTestCase {

	private const TABLE_NAME = 'column_removal_test';
	private const FULLTEXT_TABLE_NAME = 'smw_ft_search_column_removal_test';

	private DatabaseSqlite $connection;
	private TableBuilder $tableBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->tableBuilder = TableBuilder::factory( $this->connection );
	}

	public function testObsoleteColumnsAreRemoved() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete_one', 'obsolete_two' ] ) );

		$this->tableBuilder->create( $this->newTable( [ 's_id' ] ) );

		$this->assertSame( [ 's_id' ], $this->getColumns() );
	}

	public function testValuesStayInTheirColumns() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete', 'o_id' ] ) );
		$this->connection->insert( self::TABLE_NAME, [ 's_id' => 42, 'obsolete' => 43, 'o_id' => 44 ], __METHOD__ );

		$this->tableBuilder->create( $this->newTable( [ 's_id', 'o_id' ] ) );

		$this->assertSame(
			'44',
			(string)$this->connection->selectField( self::TABLE_NAME, 'o_id', [ 's_id' => 42 ], __METHOD__ )
		);
	}

	public function testNoIntermediateTableIsLeftBehind() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete' ] ) );

		$this->tableBuilder->create( $this->newTable( [ 's_id' ] ) );

		$this->assertSame( [ self::TABLE_NAME ], $this->connection->listTables() );
	}

	public function testDeclaredIndexIsKept() {
		$this->tableBuilder->create( $this->newIndexedTable( [ 's_id', 'obsolete' ] ) );

		$this->tableBuilder->create( $this->newIndexedTable( [ 's_id' ] ) );

		$this->assertCount(
			1,
			$this->connection->query( 'PRAGMA index_list(' . self::TABLE_NAME . ')', __METHOD__ )
		);
	}

	public function testPrimaryKeyStillAssignsIds() {
		$this->tableBuilder->create( $this->newTableWithPrimaryKey( [ 'obsolete', 'o_id' ] ) );

		$this->tableBuilder->create( $this->newTableWithPrimaryKey( [ 'o_id' ] ) );
		$this->connection->insert( self::TABLE_NAME, [ 'o_id' => 44 ], __METHOD__ );

		$this->assertNotNull(
			$this->connection->selectField( self::TABLE_NAME, 's_id', [ 'o_id' => 44 ], __METHOD__ )
		);
	}

	public function testTableWithoutObsoleteColumnIsNotRebuilt() {
		$this->tableBuilder->create( $this->newTable( [ 's_id' ] ) );

		$this->tableBuilder->create( $this->newTable( [ 's_id' ] ) );

		$this->assertNotContains(
			TableBuilder::PROC_FIELD_DROP,
			array_merge( ...array_values( $this->tableBuilder->getLog() ) )
		);
	}

	public function testFulltextTableStaysVirtual() {
		$this->tableBuilder->create( $this->newFulltextTable( [ 's_id', 'obsolete' ] ) );

		$this->tableBuilder->create( $this->newFulltextTable( [ 's_id' ] ) );

		$this->assertStringContainsString(
			'VIRTUAL',
			$this->connection->selectField(
				'sqlite_master',
				'sql',
				[ 'name' => self::FULLTEXT_TABLE_NAME ],
				__METHOD__
			)
		);
	}

	/**
	 * @param string[] $columns
	 */
	private function newTable( array $columns ): Table {
		$table = new Table( self::TABLE_NAME );

		foreach ( $columns as $column ) {
			$table->addColumn( $column, FieldType::FIELD_ID );
		}

		return $table;
	}

	/**
	 * @param string[] $columns
	 */
	private function newIndexedTable( array $columns ): Table {
		$table = $this->newTable( $columns );
		$table->addIndex( 's_id' );

		return $table;
	}

	/**
	 * @param string[] $columns
	 */
	private function newTableWithPrimaryKey( array $columns ): Table {
		$table = new Table( self::TABLE_NAME );
		$table->addColumn( 's_id', FieldType::FIELD_ID_PRIMARY );

		foreach ( $columns as $column ) {
			$table->addColumn( $column, FieldType::FIELD_ID );
		}

		return $table;
	}

	/**
	 * @param string[] $columns
	 */
	private function newFulltextTable( array $columns ): Table {
		$table = new Table( self::FULLTEXT_TABLE_NAME );
		$table->addOption( 'fulltextSearchTableOptions', [ 'sqlite' => 'FTS4' ] );

		foreach ( $columns as $column ) {
			$table->addColumn( $column, FieldType::FIELD_ID );
		}

		return $table;
	}

	/**
	 * @return string[]
	 */
	private function getColumns(): array {
		$result = $this->connection->query( 'PRAGMA table_info(' . self::TABLE_NAME . ')', __METHOD__ );

		return array_column( iterator_to_array( $result ), 'name' );
	}

}
