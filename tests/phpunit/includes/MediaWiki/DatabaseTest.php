<?php

namespace SMW\Tests\MediaWiki;

use SMW\Tests\MockDBConnectionProvider;
use SMW\MediaWiki\Database;

/**
 * @covers \SMW\MediaWiki\Database
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\MediaWiki\Database';
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), new Database( new MockDBConnectionProvider ) );
	}

	public function testAquireConnection() {

		$instance = new Database( new MockDBConnectionProvider, new MockDBConnectionProvider );

		$this->assertInstanceOf( 'DatabaseBase', $instance->aquireReadConnection() );
		$this->assertInstanceOf( 'DatabaseBase', $instance->aquireWriteConnection() );
	}

	public function testNumRowsMethod() {

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->once() )
			->method( 'numRows' )
			->with( $this->equalTo( 'Fuyu' ) )
			->will( $this->returnValue( 1 ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals( 1, $instance->numRows( 'Fuyu' ) );

	}

	public function testAddQuotesMethod() {

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Fan' ) )
			->will( $this->returnValue( 'Fan' ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals( 'Fan', $instance->addQuotes( 'Fan' ) );

	}

	/**
	 * @dataProvider dbTypeProvider
	 */
	public function testTableNameMethod( $type ) {

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->any() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$database->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( $type ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals( 'Foo', $instance->tableName( 'Foo' ) );

	}

	public function testSelectMethod() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$instance = new Database( $connectionProvider );

		$this->assertInstanceOf( 'ResultWrapper', $instance->select( 'Foo', 'Bar', '', __METHOD__ ) );

	}

	public function testQueryOnSQLite() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$database->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( 'TEMP' ) )
			->will( $this->returnValue( $resultWrapper ) );

		$instance = new Database( $connectionProvider );
		$this->assertInstanceOf( 'ResultWrapper', $instance->query( 'TEMPORARY' ) );

	}

	public function testSelectThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$instance = new Database( new MockDBConnectionProvider );
		$this->assertInstanceOf( 'ResultWrapper', $instance->select( 'Foo', 'Bar', '', __METHOD__ ) );

	}

	public function testQueryThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$DBError = $this->getMockBuilder( 'DBError' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->throwException( $DBError ) );

		$instance = new Database( $connectionProvider );
		$this->assertInstanceOf( 'ResultWrapper', $instance->query( 'Foo', __METHOD__ ) );

	}

	public function testMissingWriteConnectionThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$instance = new Database( new MockDBConnectionProvider );
		$this->assertInstanceOf( 'ResultWrapper', $instance->aquireWriteConnection() );

	}

	public function dbTypeProvider() {
		return array(
			array( 'mysql' ),
			array( 'sqlite' ),
			array( 'postgres' )
		);
	}

}
