<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\RdbmsTableBuilder;

/**
 * @covers \SMW\SQLStore\TableBuilder\RdbmsTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RdbmsTableBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructForMySQL() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\MySQLRdbmsTableBuilder',
			RdbmsTableBuilder::factory( $connection )
		);
	}

	public function testCanConstructForSQLite() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'sqlite' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\SQLiteRdbmsTableBuilder',
			RdbmsTableBuilder::factory( $connection )
		);
	}

	public function testCanConstructForPostgres() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'postgres' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\PostgresRdbmsTableBuilder',
			RdbmsTableBuilder::factory( $connection )
		);
	}

	public function testTryToConstructOnInvalidTypeThrowsException() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'foo' ) );

		$this->setExpectedException( 'RuntimeException' );
		RdbmsTableBuilder::factory( $connection );
	}

}
