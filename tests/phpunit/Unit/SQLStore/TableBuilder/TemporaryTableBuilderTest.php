<?php

namespace SMW\Tests\SQLStore\TableBuilder;

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
			'\SMW\SQLStore\TableBuilder\TemporaryTableBuilder',
			new TemporaryTableBuilder( $this->connection )
		);
	}

	public function testcreateWithoutAutoCommit() {

		$this->connection->expects( $this->once() )
			->method( 'query' );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->create( 'Foo' );
	}

	public function testcreateWithoutAutoCommitOnPostgres() {

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo( false ) );

		$this->connection->expects( $this->once() )
			->method( 'isType' )
			->with( $this->equalTo( 'postgres' ) )
			->will( $this->returnValue( true ) );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->create( 'Foo' );
	}

	public function testCreateWithAutoCommit() {

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo( true ) );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->WithAutoCommit( true );
		$instance->create( 'Foo' );
	}

	public function testDropWithoutAutoCommit() {

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo( false ) );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->drop( 'Foo' );
	}

	public function testDropWithAutoCommit() {

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo( true ) );

		$instance = new TemporaryTableBuilder(
			$this->connection
		);

		$instance->WithAutoCommit( true );
		$instance->drop( 'Foo' );
	}

}
