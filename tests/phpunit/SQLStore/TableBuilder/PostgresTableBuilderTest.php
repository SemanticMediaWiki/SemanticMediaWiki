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
 * @license GPL-2.0-or-later
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

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'SELECT a.attname as' ) !== false ) {
					return [];
				}
				if ( strpos( $sql, 'ALTER TABLE foo ADD "bar" TEXT' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'SELECT a.attname as' ) !== false ) {
					return [];
				}
				if ( strpos( $sql, 'ALTER TABLE foo ADD "bar" TEXT' . " DEFAULT '0'" ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

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

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'SELECT  i.relname AS indexname' ) !== false ) {
					return [];
				}
				if ( strpos( $sql, 'CREATE INDEX foo_idx_bar ON foo (bar)' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

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

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'ANALYZE foo' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
				if ( strpos( $sql, 'VACUUM (ANALYZE) foo' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
