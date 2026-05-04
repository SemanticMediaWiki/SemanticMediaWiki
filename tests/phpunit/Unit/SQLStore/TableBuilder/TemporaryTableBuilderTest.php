<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;

/**
 * @covers \SMW\SQLStore\TableBuilder\TemporaryTableBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TemporaryTableBuilderTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TemporaryTableBuilder::class,
			new TemporaryTableBuilder( $this->connection )
		);
	}

	public function testCreateWithoutAutoCommit() {
		$this->connection->expects( $this->once() )
			->method( 'query' );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->create( 'Foo' );
	}

	public function testCreateWithoutAutoCommitOnPostgres() {
		$this->connection->expects( $this->never() )
			->method( 'setFlag' );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything() );

		$this->connection->expects( $this->once() )
			->method( 'isType' )
			->with( 'postgres' )
			->willReturn( true );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->create( 'Foo' );
	}

	public function testCreateWithAutoCommitFlag() {
		$this->connection->expects( $this->once() )
			->method( 'setFlag' )
			->with( Database::AUTO_COMMIT );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything() );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->setAutoCommitFlag( true );
		$instance->create( 'Foo' );
	}

	public function testDropWithoutAutoCommit() {
		$this->connection->expects( $this->never() )
			->method( 'setFlag' );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything() );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->drop( 'Foo' );
	}

	public function testDropWithAutoCommitFlag() {
		$this->connection->expects( $this->once() )
			->method( 'setFlag' )
			->with( Database::AUTO_COMMIT );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything() );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->setAutoCommitFlag( true );
		$instance->drop( 'Foo' );
	}

	public function testCreateOnSQLite(): void {
		$this->connection->method( 'isType' )
			->willReturnCallback( static fn ( $type ) => $type === 'sqlite' );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->stringContains( 'CREATE TEMP TABLE IF NOT EXISTS Foo' ),
				$this->anything(),
				$this->anything()
			);

		$instance = new TemporaryTableBuilder( $this->connection );
		$instance->create( 'Foo' );
	}

	public function testDropOnSQLite(): void {
		$this->connection->method( 'isType' )
			->willReturnCallback( static fn ( $type ) => $type === 'sqlite' );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				'DROP TABLE Foo',
				$this->anything(),
				$this->anything()
			);

		$instance = new TemporaryTableBuilder( $this->connection );
		$instance->drop( 'Foo' );
	}

	public function testDropOnPostgres(): void {
		$this->connection->method( 'isType' )
			->willReturnCallback( static fn ( $type ) => $type === 'postgres' );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				'DROP TABLE IF EXISTS Foo',
				$this->anything(),
				$this->anything()
			);

		$instance = new TemporaryTableBuilder( $this->connection );
		$instance->drop( 'Foo' );
	}

	public function testDropOnMySQL(): void {
		$this->connection->method( 'isType' )
			->willReturn( false );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				'DROP TEMPORARY TABLE Foo',
				$this->anything(),
				$this->anything()
			);

		$instance = new TemporaryTableBuilder( $this->connection );
		$instance->drop( 'Foo' );
	}

}
