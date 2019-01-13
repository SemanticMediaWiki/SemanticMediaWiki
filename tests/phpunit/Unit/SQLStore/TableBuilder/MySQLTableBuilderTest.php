<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\MySQLTableBuilder;
use SMW\SQLStore\TableBuilder\Table;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query', 'dbSchema', 'tablePrefix' ] )
			->getMockForAbstractClass();

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$this->connection->expects( $this->any() )
			->method( 'dbSchema' )
			->will( $this->returnValue( '' ) );

		$this->connection->expects( $this->any() )
			->method( 'tablePrefix' )
			->will( $this->returnValue( '' ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MySQLTableBuilder::class,
			MySQLTableBuilder::factory( $this->connection )
		);
	}

	public function testFactoryWithWrongTypeThrowsException() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$this->setExpectedException( '\RuntimeException' );
		MySQLTableBuilder::factory( $connection );
	}

	public function testCreateNewTable() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( 'CREATE TABLE `xyz`."foo" (bar TEXT) tableoptions_foobar' ) );

		$instance = MySQLTableBuilder::factory( $connection );
		$instance->setConfig( 'wgDBname', 'xyz' );
		$instance->setConfig( 'wgDBTableOptions', 'tableoptions_foobar' );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testCreateNewTable_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( 'CREATE TABLE `xyz`."foo" (bar TEXT) tableoptions_foobar' ) );

		$instance = MySQLTableBuilder::factory( $this->connection );
		$instance->setConfig( 'wgDBname', 'xyz' );
		$instance->setConfig( 'wgDBTableOptions', 'tableoptions_foobar' );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateExistingTableWithNewField() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
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
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text  FIRST' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateExistingTableWithNewField_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'DESCRIBE' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text  FIRST' ) );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateExistingTableWithNewFieldAndDefault() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
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
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' . " DEFAULT '0'" . ' FIRST' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testUpdateExistingTableWithNewFieldAndDefault_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'DESCRIBE' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 5 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD `bar` text' . " DEFAULT '0'" . ' FIRST' ) );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
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
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->at( 4 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD INDEX (bar)' ) );

		$instance = MySQLTableBuilder::factory( $connection );

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

		$this->connection->expects( $this->at( 7 ) )
			->method( 'query' )
			->with( $this->stringContains( 'SHOW INDEX' ) )
			->will( $this->returnValue( [] ) );

		$this->connection->expects( $this->at( 10 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE "foo" ADD INDEX (bar)' ) );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'tableExists', 'query' ] )
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

	public function testDropTable_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE "foo"' ) );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testOptimizeTable() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '>=' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'query' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE TABLE "foo"' ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'query' )
			->with( $this->stringContains( 'OPTIMIZE TABLE "foo"' ) );

		$instance = MySQLTableBuilder::factory( $connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

	public function testOptimizeTable_132() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.32', '<' ) ) {
			$this->markTestSkipped( 'MediaWiki changed the Database signature!' );
		}

		$this->connection->expects( $this->at( 3 ) )
			->method( 'query' )
			->with( $this->stringContains( 'ANALYZE TABLE "foo"' ) );

		$this->connection->expects( $this->at( 6 ) )
			->method( 'query' )
			->with( $this->stringContains( 'OPTIMIZE TABLE "foo"' ) );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
