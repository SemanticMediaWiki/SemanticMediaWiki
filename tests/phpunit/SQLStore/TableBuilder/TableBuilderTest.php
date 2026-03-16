<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\TableBuilder\MySQLTableBuilder;
use SMW\SQLStore\TableBuilder\PostgresTableBuilder;
use SMW\SQLStore\TableBuilder\SQLiteTableBuilder;
use SMW\SQLStore\TableBuilder\TableBuilder;
use Wikimedia\Rdbms\Database;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TableBuilderTest extends TestCase {

	public function testCanConstructForMySQL() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$this->assertInstanceOf(
			MySQLTableBuilder::class,
			TableBuilder::factory( $connection )
		);
	}

	public function testCanConstructForSQLite() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'sqlite' );

		$this->assertInstanceOf(
			SQLiteTableBuilder::class,
			TableBuilder::factory( $connection )
		);
	}

	public function testCanConstructForPostgres() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'postgres' );

		$this->assertInstanceOf(
			PostgresTableBuilder::class,
			TableBuilder::factory( $connection )
		);
	}

	public function testConstructWithInvalidTypeThrowsException() {
		$connection = $this->getMockBuilder( Database::class )
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
