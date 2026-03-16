<?php

namespace SMW\Tests\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\CleanUpTables;
use Wikimedia\Rdbms\Database;

/**
 * @covers \SMW\MediaWiki\Connection\CleanUpTables
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class CleanUpTablesTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
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
		$this->expectException( '\RuntimeException' );
		new CleanUpTables( 'Foo' );
	}

	public function testNonPostgres() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->setMethods( [ 'listTables', 'query', 'tableExists' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'tableExists' )
			->willReturn( true );

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->willReturn( [ 'abcsmw_foo' ] );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->with( 'DROP TABLE abcsmw_foo' );

		$instance = new CleanUpTables(
			$connection
		);

		$instance->dropTables( 'abcsmw_' );
	}

	public function testPostgres() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->setMethods( [ 'listTables', 'query', 'getType', 'tableExists' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'tableExists' )
			->willReturn( true );

		$connection->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->willReturn( 'postgres' );

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->willReturn( [ 'abcsmw_foo' ] );

		$connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->with( 'DROP TABLE IF EXISTS abcsmw_foo CASCADE' );

		$instance = new CleanUpTables(
			$connection
		);

		$instance->dropTables( 'abcsmw_' );
	}

}
