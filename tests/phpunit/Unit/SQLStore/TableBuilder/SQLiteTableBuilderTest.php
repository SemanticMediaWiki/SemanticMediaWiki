<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\SQLiteTableBuilder;
use SMW\SQLStore\TableBuilder\Table;

/**
 * @covers \SMW\SQLStore\TableBuilder\SQLiteTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SQLiteTableBuilderTest extends \PHPUnit_Framework_TestCase {

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query', 'dbSchema', 'tablePrefix' ] )
			->getMockForAbstractClass();

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$this->connection->expects( $this->any() )
			->method( 'dbSchema' )
			->will( $this->returnValue( '' ) );

		$this->connection->expects( $this->any() )
			->method( 'tablePrefix' )
			->will( $this->returnValue( '' ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SQLiteTableBuilder::class,
			SQLiteTableBuilder::factory( $this->connection )
		);
	}

	public function testCreateTableOnNewTable() {

		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testCreateTableOnNewTable_132() {

		if ( version_compare( MW_VERSION, '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewField() {

		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info("foo")' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewField_132() {

		if ( version_compare( MW_VERSION, '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info("foo")' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault() {

		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info("foo")' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' . " DEFAULT '0'" ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault_132() {

		if ( version_compare( MW_VERSION, '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info("foo")' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' . " DEFAULT '0'" ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {

		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA index_list("foo")' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX "foo"_index0' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testCreateIndex_132() {

		if ( version_compare( MW_VERSION, '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->at( 7 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA index_list("foo")' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 10 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX "foo"_index0' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {

		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE "foo"' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testDropTable_132() {

		if ( version_compare( MW_VERSION, '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE "foo"' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testOptimizeTable() {

		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->at( 1 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE "foo"' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

	public function testOptimizeTable_132() {

		if ( version_compare( MW_VERSION, '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE "foo"' ) );

		$instance = SQLiteTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
