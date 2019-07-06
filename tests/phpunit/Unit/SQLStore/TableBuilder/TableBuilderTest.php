<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstructForMySQL() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\MySQLTableBuilder',
			TableBuilder::factory( $connection )
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
			'\SMW\SQLStore\TableBuilder\SQLiteTableBuilder',
			TableBuilder::factory( $connection )
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
			'\SMW\SQLStore\TableBuilder\PostgresTableBuilder',
			TableBuilder::factory( $connection )
		);
	}

	public function testConstructWithInvalidTypeThrowsException() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'foo' ) );

		$this->setExpectedException( 'RuntimeException' );
		TableBuilder::factory( $connection );
	}

	public function testConstructWithInvalidInstanceThrowsException() {
		$this->setExpectedException( 'RuntimeException' );
		TableBuilder::factory( 'foo' );
	}

}
