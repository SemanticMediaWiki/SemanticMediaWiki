<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\MySQLTableBuilder;
use SMW\SQLStore\TableBuilder\Table;

/**
 * @covers \SMW\SQLStore\TableBuilder\MySQLTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MySQLTableBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\MySQLTableBuilder',
			MySQLTableBuilder::factory( $connection )
		);
	}

	public function testCreateNewTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'CREATE TABLE `xyz`."foo"' ) );

		$instance = MySQLTableBuilder::factory( $connection );
		$instance->addConfiguration( 'wgDBname', 'xyz' );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateExistingTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'DESCRIBE' ) )
			->will( $this->returnValue( array() ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text FIRST' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testCreateIndex() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SHOW INDEX' ) )
			->will( $this->returnValue( array() ) );

		$connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD INDEX (bar)' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableExists', 'query' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE "foo"' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

}
