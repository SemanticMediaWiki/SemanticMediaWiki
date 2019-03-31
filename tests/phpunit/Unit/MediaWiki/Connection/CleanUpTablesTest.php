<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\CleanUpTables;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\CleanUpTables
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CleanUpTablesTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CleanUpTables::class,
			new CleanUpTables( $this->connection )
		);
	}

	public function testConstructWithInvalidConnectionThrowsException() {
		$this->setExpectedException( '\RuntimeException' );
		new CleanUpTables( 'Foo' );
	}

	public function testNonPostgres() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'listTables', 'query', 'tableExists' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->will( $this->returnValue( [ 'abcsmw_foo' ] ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->with( $this->equalTo( 'DROP TABLE abcsmw_foo' ) );

		$instance = new CleanUpTables(
			$connection
		);

		$instance->dropTables( 'abcsmw_' );
	}

	public function testPostgres() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'listTables', 'query', 'getType', 'tableExists' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'tableExists' )
			->will( $this->returnValue( true ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->will( $this->returnValue( 'postgres' ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->will( $this->returnValue( [ 'abcsmw_foo' ] ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->with( $this->equalTo( 'DROP TABLE IF EXISTS abcsmw_foo CASCADE' ) );

		$instance = new CleanUpTables(
			$connection
		);

		$instance->dropTables( 'abcsmw_' );
	}

}
