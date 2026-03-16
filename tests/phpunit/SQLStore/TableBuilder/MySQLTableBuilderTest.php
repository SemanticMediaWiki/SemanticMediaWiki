<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\TableBuilder\MySQLTableBuilder;
use SMW\SQLStore\TableBuilder\Table;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \SMW\SQLStore\TableBuilder\MySQLTableBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class MySQLTableBuilderTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->createMock( IMaintainableDatabase::class );
		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$this->connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$this->connection->expects( $this->any() )
			->method( 'dbSchema' )
			->willReturn( '' );

		$this->connection->expects( $this->any() )
			->method( 'tablePrefix' )
			->willReturn( '' );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MySQLTableBuilder::class,
			MySQLTableBuilder::factory( $this->connection )
		);
	}

	public function testFactoryWithWrongTypeThrowsException() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->willReturn( 'sqlite' );

		$this->expectException( '\RuntimeException' );
		MySQLTableBuilder::factory( $connection );
	}

	public function testCreateNewTable() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( false );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( 'CREATE TABLE `xyz`.foo (bar TEXT) tableoptions_foobar' );

		$instance = MySQLTableBuilder::factory( $this->connection );
		$instance->setConfig( 'wgDBname', 'xyz' );
		$instance->setConfig( 'wgDBTableOptions', 'tableoptions_foobar' );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateExistingTableWithNewField() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'DESCRIBE' ) !== false ) {
					return [];
				}
				if ( strpos( $sql, 'ALTER TABLE foo ADD `bar` text  FIRST' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );

		$instance->create( $table );
	}

	public function testUpdateExistingTableWithNewFieldAndDefault() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'DESCRIBE' ) !== false ) {
					return [];
				}
				if ( strpos( $sql, 'ALTER TABLE foo ADD `bar` text' . " DEFAULT '0'" . ' FIRST' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addDefault( 'bar', 0 );

		$instance->create( $table );
	}

	public function testCreateIndex() {
		$this->connection->expects( $this->any() )
			->method( 'tableExists' )
			->willReturn( false );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'SHOW INDEX' ) !== false ) {
					return [];
				}
				if ( strpos( $sql, 'ALTER TABLE foo ADD INDEX (bar)' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$table->addColumn( 'bar', 'text' );
		$table->addIndex( 'bar' );

		$instance->create( $table );
	}

	public function testDropTable() {
		$this->connection->expects( $this->once() )
			->method( 'tableExists' )
			->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DROP TABLE foo' ) )
			->willReturn( new FakeResultWrapper( [] ) );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->drop( $table );
	}

	public function testOptimizeTable() {
		$this->connection->expects( $this->exactly( 2 ) )
			->method( 'query' )
			->willReturnCallback( static function ( $sql ) {
				if ( strpos( $sql, 'ANALYZE TABLE foo' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
				if ( strpos( $sql, 'OPTIMIZE TABLE foo' ) !== false ) {
					return new FakeResultWrapper( [] );
				}
			} );

		$instance = MySQLTableBuilder::factory( $this->connection );

		$table = new Table( 'foo' );
		$instance->optimize( $table );
	}

}
