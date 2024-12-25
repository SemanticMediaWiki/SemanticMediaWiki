<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\PostgresTableBuilder;
use SMW\SQLStore\TableBuilder\Table;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\TableBuilder\PostgresTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PostgresTableBuilderTest extends \PHPUnit\Framework\TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->createMock( Database::class );

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'postgres' );

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
			PostgresTableBuilder::class,
			PostgresTableBuilder::factory( $this->connection )
		);
	}

	public function testCreateTableOnNewTable() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( false );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

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
			->with( $this->stringContains( 'SELECT a.attname as' ) )
			->willReturn( [] );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE foo ADD "bar" TEXT' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

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
			->with( $this->stringContains( 'SELECT a.attname as' ) )
			->willReturn( [] );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE foo ADD "bar" TEXT' . " DEFAULT '0'" ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( false );

		$this->connection->expects( $this->any() )
			->method( 'indexInfo' )
			->willReturn( false );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT  i.relname AS indexname' ) )
			->willReturn( [] );

		$this->connection->expects( $this->at( 8 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX foo_idx_bar ON foo (bar)' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

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
			->with( $this->stringContains( 'DROP TABLE IF EXISTS foo' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testDoCheckOnAfterCreate() {
		$this->markTestSkipped( 'SUT needs refactoring - onTransactionCommitOrIdle cannot be mocked' );
		$this->connection->expects( $this->any() )
			->method( 'selectField' )
			->willReturn( 42 );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$instance->checkOn( $instance::POST_CREATION );
	}

	public function testOptimizeTable() {
		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'postgres' );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE foo' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
