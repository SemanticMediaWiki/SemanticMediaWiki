<?php

namespace SMW\Tests\MediaWiki;

use SMW\Tests\Util\Mock\MockDBConnectionProvider;
use SMW\MediaWiki\Database;

/**
 * @covers \SMW\MediaWiki\Database
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
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

		$this->assertInstanceOf( 'DatabaseBase', $instance->acquireReadConnection() );
		$this->assertInstanceOf( 'DatabaseBase', $instance->acquireWriteConnection() );
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

	public function testSelectFieldMethod() {

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$database->expects( $this->once() )
			->method( 'selectField' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Bar' ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals( 'Bar', $instance->selectField( 'Foo', 'Bar', '', __METHOD__, array() ) );
	}

	public function testQueryOnSQLite() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$readConnection = new MockDBConnectionProvider();
		$read = $readConnection->getMockDatabase();

		$read->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$writeConnection = new MockDBConnectionProvider();
		$write = $writeConnection->getMockDatabase();

		$write->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( 'TEMP' ) )
			->will( $this->returnValue( $resultWrapper ) );

		$instance = new Database( $readConnection, $writeConnection );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->query( 'TEMPORARY' )
		);
	}

	public function testSelectThrowsException() {

		$instance = new Database( new MockDBConnectionProvider );

		$this->setExpectedException( 'RuntimeException' );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testQueryThrowsException() {

		$connectionProvider = new MockDBConnectionProvider();
		$database = $connectionProvider->getMockDatabase();

		$databaseException = new \DBError( $database, 'foo' );

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->throwException( $databaseException ) );

		$instance = new Database( $connectionProvider );

		$this->setExpectedException( 'RuntimeException' );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->query( 'Foo', __METHOD__ )
		);
	}

	public function testMissingWriteConnectionThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$instance = new Database( new MockDBConnectionProvider );
		$this->assertInstanceOf( 'ResultWrapper', $instance->acquireWriteConnection() );

	}

	public function dbTypeProvider() {
		return array(
			array( 'mysql' ),
			array( 'sqlite' ),
			array( 'postgres' )
		);
	}

}
