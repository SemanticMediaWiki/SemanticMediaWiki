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
class SQLiteTableColumnRemovalTest extends SMWIntegrationTestCase {

	private const TABLE_NAME = 'column_removal_test';

	private DatabaseSqlite $connection;
	private TableBuilder $tableBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->tableBuilder = TableBuilder::factory( $this->connection );
	}

	public function testObsoleteColumnsAreRemoved() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete_one', 'obsolete_two' ] ) );

		$this->tableBuilder->create( $this->newTable( [ 's_id', 'o_blob' ] ) );

		$this->assertSame( [ 's_id', 'o_blob' ], $this->getColumns( self::TABLE_NAME ) );
	}

	public function testValuesOfRemainingColumnsAreKept() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete' ] ) );
		$this->connection->insert( self::TABLE_NAME, [ 's_id' => 42, 'obsolete' => 43 ], __METHOD__ );

		$this->tableBuilder->create( $this->newTable( [ 's_id' ] ) );

		$this->assertSame(
			'42',
			(string)$this->connection->selectField( self::TABLE_NAME, 's_id', [], __METHOD__ )
		);
	}

	public function testNoIntermediateTableIsLeftBehind() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete' ] ) );

		$this->tableBuilder->create( $this->newTable( [ 's_id' ] ) );

		$this->assertSame( [ self::TABLE_NAME ], $this->getTableNames() );
	}

	public function testDeclaredIndexIsKept() {
		$this->tableBuilder->create( $this->newTable( [ 's_id', 'obsolete' ] ) );

		$table = $this->newTable( [ 's_id' ] );
		$table->addIndex( 's_id' );
		$this->tableBuilder->create( $table );

		$this->assertCount( 1, $this->query( 'PRAGMA index_list(' . self::TABLE_NAME . ')' ) );
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
	 * @return string[]
	 */
	private function getColumns( string $tableName ): array {
		return array_column( $this->query( "PRAGMA table_info($tableName)" ), 'name' );
	}

	/**
	 * @return string[]
	 */
	private function getTableNames(): array {
		return array_column( $this->query( "SELECT name FROM sqlite_master WHERE type = 'table'" ), 'name' );
	}

	/**
	 * @return array[]
	 */
	private function query( string $sql ): array {
		$rows = [];

		foreach ( $this->connection->query( $sql, __METHOD__ ) as $row ) {
			$rows[] = (array)$row;
		}

		return $rows;
	}

}
