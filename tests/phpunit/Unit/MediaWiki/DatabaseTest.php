<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\Database;

/**
 * @covers \SMW\MediaWiki\Database
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Database',
			new Database( $connectionProvider )
		);
	}

	public function testNumRowsMethod() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'numRows' ) )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'numRows' )
			->with( $this->equalTo( 'Fuyu' ) )
			->will( $this->returnValue( 1 ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals(
			1,
			$instance->numRows( 'Fuyu' )
		);
	}

	public function testAddQuotesMethod() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'addQuotes' ) )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Fan' ) )
			->will( $this->returnValue( 'Fan' ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals(
			'Fan',
			$instance->addQuotes( 'Fan' )
		);
	}

	/**
	 * @dataProvider dbTypeProvider
	 */
	public function testTableNameMethod( $type ) {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'tableName', 'getType' ) )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$database->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( $type ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );
		$instance->setDBPrefix( 'bar_' );

		$expected = $type === 'sqlite' ? 'bar_Foo' : 'Foo';

		$this->assertEquals(
			$expected,
			$instance->tableName( 'Foo' )
		);
	}

	public function testSelectMethod() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'select' ) )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testSelectFieldMethod() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'selectField' ) )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'selectField' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Bar' ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );

		$this->assertEquals(
			'Bar',
			$instance->selectField( 'Foo', 'Bar', '', __METHOD__, array() )
		);
	}

	/**
	 * @dataProvider querySqliteProvider
	 */
	public function testQueryOnSQLite( $query, $expected ) {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$read = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'getType' ) )
			->getMockForAbstractClass();

		$read->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$readConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $read ) );

		$write = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'query' ) )
			->getMockForAbstractClass();

		$write->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( $expected ) )
			->will( $this->returnValue( $resultWrapper ) );

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $write ) );

		$instance = new Database( $readConnectionProvider, $writeConnectionProvider );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->query( $query )
		);
	}

	public function querySqliteProvider() {

		$provider = array(
			array( 'TEMPORARY', 'TEMP' ),
			array( 'RAND', 'RANDOM' ),
			array( 'ENGINE=MEMORY', '' ),
			array( 'DROP TEMP', 'DROP' )
		);

		return $provider;
	}

	public function testSelectThrowsException() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'select' ) )
			->getMockForAbstractClass();

		$database->expects( $this->once() )
			->method( 'select' );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );

		$this->setExpectedException( 'RuntimeException' );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->select( 'Foo', 'Bar', '', __METHOD__ )
		);
	}

	public function testQueryThrowsException() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'query' ) )
			->getMockForAbstractClass();

		$databaseException = new \DBError( $database, 'foo' );

		$database->expects( $this->any() )
			->method( 'query' )
			->will( $this->throwException( $databaseException ) );

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database( $connectionProvider );

		$this->setExpectedException( 'RuntimeException' );

		$this->assertInstanceOf(
			'ResultWrapper',
			$instance->query( 'Foo', __METHOD__ )
		);
	}

	public function testBeginTransaction() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$readConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			$readConnectionProvider,
			$writeConnectionProvider
		);

		// Can't reach the `DatabaseBase::begin` with a mock because
		// it is declared as final
		$instance->beginTransaction( __METHOD__ );
	}

	public function testCommitTransaction() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'isOpen' )
			->will( $this->returnValue( true ) );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			$readConnectionProvider,
			$writeConnectionProvider
		);

		// Can't reach the `DatabaseBase::begin`/`DatabaseBase::commit`
		// with a mock because it is declared as final
		$instance->beginTransaction( __METHOD__ );
		$instance->commitTransaction( __METHOD__ );
	}

	public function testDisableEnableTransactions() {

		$database = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'getFlag', 'clearFlag', 'setFlag' ) )
			->getMockForAbstractClass();

		$database->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$database->expects( $this->any() )
			->method( 'getFlag' )
			->will( $this->returnValue( true ) );

		$database->expects( $this->once() )
			->method( 'clearFlag' );

		$database->expects( $this->once() )
			->method( 'setFlag' );

		$readConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$writeConnectionProvider->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new Database(
			$readConnectionProvider,
			$writeConnectionProvider
		);

		$instance->disableTransactions();
		$instance->enableTransactions();
	}

	public function testMissingWriteConnectionThrowsException() {

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Database( $connectionProvider );

		$this->setExpectedException( 'RuntimeException' );
		$instance->tableExists( 'Foo' );
	}

	public function dbTypeProvider() {
		return array(
			array( 'mysql' ),
			array( 'sqlite' ),
			array( 'postgres' )
		);
	}

}
