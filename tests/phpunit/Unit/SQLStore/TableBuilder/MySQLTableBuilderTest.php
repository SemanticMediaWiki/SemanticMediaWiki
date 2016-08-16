<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\MySQLTableBuilder;

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

	public function testCreateTableOnNewTable() {

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
			->with( $this->stringContains( 'CREATE TABLE' ) );

		$instance = MySQLTableBuilder::factory( $connection );

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
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SHOW INDEX' ) )
			->will( $this->returnValue( array() ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD INDEX (bar)' ) );

		$instance = MySQLTableBuilder::factory( $connection );

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
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE "foo"' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$instance->dropTable( 'foo' );
	}

}
