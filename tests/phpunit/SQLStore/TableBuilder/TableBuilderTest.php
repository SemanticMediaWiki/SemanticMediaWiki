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
class TableBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstructForMySQL() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\MySQLTableBuilder',
			TableBuilder::factory( $connection )
		);
	}

	public function testCanConstructForSQLite() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'sqlite' );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\SQLiteTableBuilder',
			TableBuilder::factory( $connection )
		);
	}

	public function testCanConstructForPostgres() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'postgres' );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableBuilder\PostgresTableBuilder',
			TableBuilder::factory( $connection )
		);
	}

	public function testConstructWithInvalidTypeThrowsException() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$this->expectException( 'RuntimeException' );
		TableBuilder::factory( $connection );
	}

	public function testConstructWithInvalidInstanceThrowsException() {
		$this->expectException( 'RuntimeException' );
		TableBuilder::factory( 'foo' );
	}

}
