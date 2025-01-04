<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\CleanUpTables;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\CleanUpTables
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CleanUpTablesTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
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
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'listTables', 'query', 'tableExists' ] )
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
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'listTables', 'query', 'getType', 'tableExists' ] )
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
