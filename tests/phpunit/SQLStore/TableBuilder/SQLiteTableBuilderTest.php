<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\SQLiteTableBuilder;
use SMW\SQLStore\TableBuilder\Table;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \SMW\SQLStore\TableBuilder\SQLiteTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SQLiteTableBuilderTest extends \PHPUnit\Framework\TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->createMock( IMaintainableDatabase::class );

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'sqlite' );

		$this->connection->expects( $this->any() )
			->method( 'dbSchema' )
			->willReturn( '' );

		$this->connection->expects( $this->any() )
			->method( 'tablePrefix' )
			->willReturn( '' );
		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SQLiteTableBuilder::class,
			SQLiteTableBuilder::factory( $this->connection )
		);
	}

	public function testCreateTableOnNewTable() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( false );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewField() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info(foo)' ) )
			->willReturn( [] );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE foo ADD `bar` text' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info(foo)' ) )
			->willReturn( [] );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE foo ADD `bar` text' . " DEFAULT '0'" ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( false );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA index_list(foo)' ) )
			->willReturn( [] );

		$this->connection->expects( $this->at( 7 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX foo_index0' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {
		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE foo' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testOptimizeTable() {
		$this->connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE foo' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
