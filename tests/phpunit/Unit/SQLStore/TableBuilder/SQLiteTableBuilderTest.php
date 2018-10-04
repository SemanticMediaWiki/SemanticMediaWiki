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

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$this->assertInstanceOf(
			SQLiteTableBuilder::class,
			SQLiteTableBuilder::factory( $connection )
		);
	}

	public function testCreateTableOnNewTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = SQLiteTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewField() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info("foo")' ) )
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' ) );

		$instance = SQLiteTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateTableWithNewFieldAndDefault() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA table_info("foo")' ) )
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' . " DEFAULT '0'" ) );

		$instance = SQLiteTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA index_list("foo")' ) )
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX "foo"_index0' ) );

		$instance = SQLiteTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE "foo"' ) );

		$instance = SQLiteTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testOptimizeTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE "foo"' ) );

		$instance = SQLiteTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
