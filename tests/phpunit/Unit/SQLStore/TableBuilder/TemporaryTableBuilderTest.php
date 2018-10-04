<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\MediaWiki\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;

/**
 * @covers \SMW\SQLStore\TableBuilder\TemporaryTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TemporaryTableBuilderTest extends \PHPUnit_Framework_TestCase {

	private $connection;

	protected function setUp() {
		parent::setUp();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
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
			->with( $this->equalTo( 'postgres' ) )
			->will( $this->returnValue( true ) );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->create( 'Foo' );
	}

	public function testCreateWithAutoCommitFlag() {

		$this->connection->expects( $this->once() )
			->method( 'setFlag' )
			->with( $this->equalTo( Database::AUTO_COMMIT ) );

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
			->with( $this->equalTo( Database::AUTO_COMMIT ) );

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

}
