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

	public function testMethodsWithSimpleReturnCoverage() {

		$resultWrapper = $this->getMockBuilder( 'ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( 'DatabaseMysql' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$connection->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Fan' ) )
			->will( $this->returnValue( 'Fan' ) );

		$connection->expects( $this->once() )
			->method( 'numRows' )
			->with( $this->equalTo( 'Fuyu' ) )
			->will( $this->returnValue( 1 ) );

		$connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$provider = $this->getMockForAbstractClass( '\SMW\DBConnectionProvider' );

		$provider->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new Database( $provider );

		$this->assertEquals( 'Foo', $instance->tableName( 'Foo' ) );
		$this->assertEquals( 'Fan', $instance->addQuotes( 'Fan' ) );
		$this->assertEquals( 1, $instance->numRows( 'Fuyu' ) );
		$this->assertInstanceOf( 'ResultWrapper', $instance->select( 'Foo', 'Bar', '', __METHOD__ ) );

	}

	public function testSelectThrowsException() {

		$this->setExpectedException( 'UnexpectedValueException' );

		$connection = $this->getMockBuilder( 'DatabaseMysql' )
			->disableOriginalConstructor()
			->getMock();

		$provider = $this->getMockForAbstractClass( '\SMW\DBConnectionProvider' );

		$provider->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new Database( $provider );

		$this->assertInstanceOf( 'ResultWrapper', $instance->select( 'Foo', 'Bar', '', __METHOD__ ) );

	}

}
