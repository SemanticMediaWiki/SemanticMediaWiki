<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\PostgresTableBuilder;
use SMW\SQLStore\TableBuilder\Table;

/**
 * @covers \SMW\SQLStore\TableBuilder\PostgresTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PostgresTableBuilderTest extends \PHPUnit_Framework_TestCase {

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query', 'dbSchema', 'tablePrefix', 'onTransactionIdle' ] )
			->getMockForAbstractClass();

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'postgres' ) );

		$this->connection->expects( $this->any() )
			->method( 'dbSchema' )
			->will( $this->returnValue( '' ) );

		$this->connection->expects( $this->any() )
			->method( 'tablePrefix' )
			->will( $this->returnValue( '' ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PostgresTableBuilder::class,
			PostgresTableBuilder::factory( $this->connection )
		);
	}

	public function testCreateTableOnNewTable() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testCreateTableOnNewTable_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewField() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT a.attname as' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD "bar" TEXT' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewField_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT a.attname as' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD "bar" TEXT' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT a.attname as' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD "bar" TEXT'. " DEFAULT '0'" ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT a.attname as' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD "bar" TEXT'. " DEFAULT '0'" ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->any() )
			->method( 'indexInfo' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT  i.relname AS indexname' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX foo_idx_bar ON foo (bar)' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testCreateIndex_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->any() )
			->method( 'indexInfo' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->at( 7 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SELECT  i.relname AS indexname' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 11 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX foo_idx_bar ON foo (bar)' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE IF EXISTS "foo"' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testDropTable_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE IF EXISTS "foo"' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testDoCheckOnAfterCreate() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) { return $callback(); } ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER SEQUENCE' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$instance->checkOn( $instance::POST_CREATION );
	}

	public function testDoCheckOnAfterCreate_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) { return $callback(); } ) );

		$this->connection->expects( $this->at( 6 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER SEQUENCE' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$instance->checkOn( $instance::POST_CREATION );
	}

	public function testOptimizeTable() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'postgres' ) );

		$this->connection->expects( $this->at( 1 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE "foo"' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

	public function testOptimizeTable_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'postgres' ) );

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE "foo"' ) );

		$instance = PostgresTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
