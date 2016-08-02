<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\SQLiteRdbmsTableBuilder;

/**
 * @covers \SMW\SQLStore\TableBuilder\SQLiteRdbmsTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SQLiteRdbmsTableBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\SQLiteRdbmsTableBuilder',
			SQLiteRdbmsTableBuilder::factory( $connection )
		);
	}

	public function testCreateTableOnNewTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
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

		$instance = SQLiteRdbmsTableBuilder::factory( $connection );

		$tableOptions = array(
			'fields' => array( 'bar' => 'text' )
		);

		$instance->createTable( 'foo', $tableOptions );
	}

	public function testUpdateTableOnOldTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
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
			->will( $this->returnValue( array() ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' ) );

		$instance = SQLiteRdbmsTableBuilder::factory( $connection );

		$tableOptions = array(
			'fields' => array( 'bar' => 'text' )
		);

		$instance->createTable( 'foo', $tableOptions );
	}

	public function testCreateIndex() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'query' )
			->with( $this->stringContains( 'PRAGMA index_list("foo")' ) )
			->will( $this->returnValue( array() ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE INDEX "foo"_index0' ) );

		$instance = SQLiteRdbmsTableBuilder::factory( $connection );

		$indexOptions = array(
			'indicies' => array( 'bar' )
		);

		$instance->createIndex( 'foo', $indexOptions );
	}

	public function testDropTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
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

		$instance = SQLiteRdbmsTableBuilder::factory( $connection );

		$instance->dropTable( 'foo' );
	}

}
