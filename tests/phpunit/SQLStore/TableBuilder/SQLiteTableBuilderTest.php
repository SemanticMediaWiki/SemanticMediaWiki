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
			->will( $this->returnValue( 'sqlite' ) );

		$this->connection->expects( $this->any() )
			->method( 'dbSchema' )
			->will( $this->returnValue( '' ) );

		$this->connection->expects( $this->any() )
			->method( 'tablePrefix' )
			->will( $this->returnValue( '' ) );
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
			->will( $this->returnValue( false ) );

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
	
		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( function( $query ) {
				if ( strpos( $query, 'PRAGMA table_info(foo)' ) !== false ) {
					return [];
				} elseif ( strpos($query, 'ALTER TABLE foo ADD `bar` text') !== false ) {
					return new FakeResultWrapper( [] );
				}
			});
	
		$instance = SQLiteTableBuilder::factory( $this->connection );
	
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
			->willReturnCallback( function( $query ) {
				if ( strpos( $query, 'PRAGMA table_info(foo)' ) !== false ) {
					return []; 
				} elseif ( strpos( $query, 'ALTER TABLE foo ADD `bar` text' ) !== false && strpos( $query, "DEFAULT '0'" ) !== false ) {
					return new FakeResultWrapper([]);
				}
			});
	
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

		$this->connection->expects( $this->exactly( 3 ) )
			->method( 'query' )
			->willReturnCallback( function( $query ) {
				if ( strpos( $query, 'CREATE TABLE foo(bar TEXT)' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
				if ( strpos( $query, 'PRAGMA index_list(foo)' ) !== false ) {
					return [];
				}
				if ( strpos( $query, 'CREATE INDEX foo_index0' ) !== false ) {
					return new FakeResultWrapper( [] );
				}

				throw new \Exception( "Unexpected query: " . $query );
			});
	
		$instance = SQLiteTableBuilder::factory( $this->connection );
	
		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}	

	public function testDropTable() {
		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE foo' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testOptimizeTable() {
		$this->connection->expects( $this->exactly( 1 ) )
			->method( 'query' )
			->willReturnCallback( function( $query ) {
				if ( strpos( $query, 'ANALYZE foo' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
	
				throw new \Exception( "Unexpected query: " . $query );
			});
	
		$instance = SQLiteTableBuilder::factory( $this->connection );
		$table = new Table( 'foo' );

		$instance->optimize( $table );
	}	

}
