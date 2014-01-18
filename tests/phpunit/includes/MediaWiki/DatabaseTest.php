<?php

namespace SMW\Tests\MediaWiki;

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
		$this->assertInstanceOf(
			$this->getClass(),
			new Database( $this->getMockForAbstractClass( '\SMW\DBConnectionProvider' ) )
		);
	}

	public function testNumRowsMethod() {

		$mock = new MockDBConnectionProvider( $this );
		$connection = $mock->getConnection();

		$connection->expects( $this->once() )
			->method( 'numRows' )
			->with( $this->equalTo( 'Fuyu' ) )
			->will( $this->returnValue( 1 ) );

		$instance = new Database( $mock->getConnectionProvider() );

		$this->assertEquals( 1, $instance->numRows( 'Fuyu' ) );

	}

	public function testAddQuotesMethod() {

		$mock = new MockDBConnectionProvider( $this );
		$connection = $mock->getConnection();

		$connection->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Fan' ) )
			->will( $this->returnValue( 'Fan' ) );

		$instance = new Database( $mock->getConnectionProvider() );

		$this->assertEquals( 'Fan', $instance->addQuotes( 'Fan' ) );

	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testTableNameMethod( $type ) {

		$mock = new MockDBConnectionProvider( $this );
		$connection = $mock->getConnection();

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$connection->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( $type ) );

		$instance = new Database( $mock->getConnectionProvider() );

		$this->assertEquals( 'Foo', $instance->tableName( 'Foo' ) );

	}

	public function testSelectMethod() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$mock = new MockDBConnectionProvider( $this );
		$connection = $mock->getConnection();

		$connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$instance = new Database( $mock->getConnectionProvider() );

		$this->assertInstanceOf( 'ResultWrapper', $instance->select( 'Foo', 'Bar', '', __METHOD__ ) );

	}

	public function testSelectThrowsException() {

		$this->setExpectedException( 'UnexpectedValueException' );

		$mock = new MockDBConnectionProvider( $this );
		$instance = new Database( $mock->getConnectionProvider() );

		$this->assertInstanceOf( 'ResultWrapper', $instance->select( 'Foo', 'Bar', '', __METHOD__ ) );

	}

	public function typeProvider() {
		return array(
			array( 'mysql' ),
			array( 'sqlite' )
		);
	}

}

class MockDBConnectionProvider {

	public function __construct( \PHPUnit_Framework_TestCase $framework ) {
		$this->framework = $framework;
	}

	public function getConnection() {

		if ( !isset( $this->connection ) ) {
			$this->connection = $this->framework->getMockBuilder( 'DatabaseMysql' )
				->disableOriginalConstructor()
				->getMock();
		}

		return $this->connection;
	}

	public function getConnectionProvider() {

		$provider = $this->framework->getMockForAbstractClass( '\SMW\DBConnectionProvider' );

		$provider->expects( $this->framework->any() )
			->method( 'getConnection' )
			->will( $this->framework->returnValue( $this->getConnection() ) );

		return $provider;
	}

}
