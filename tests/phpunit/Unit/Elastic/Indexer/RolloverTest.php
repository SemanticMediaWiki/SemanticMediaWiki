<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\Rollover;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\Rollover
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RolloverTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Rollover::class,
			new Rollover( $this->connection )
		);
	}

	public function testRollover() {

		$indices = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'delete', 'existsAlias', 'updateAliases' ] )
			->getMock();

		$indices->expects( $this->once() )
			->method( 'updateAliases' );

		$this->connection->expects( $this->any() )
			->method( 'indices' )
			->will( $this->returnValue( $indices ) );

		$this->connection->expects( $this->once() )
			->method( 'releaseLock' );

		$instance = new Rollover(
			$this->connection
		);

		$instance->rollover( 'Foo', 'v2' );
	}

	public function testUpdate() {

		$indices = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'delete', 'existsAlias', 'updateAliases' ] )
			->getMock();

		$indices->expects( $this->once() )
			->method( 'updateAliases' );

		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->any() )
			->method( 'indices' )
			->will( $this->returnValue( $indices ) );

		$instance = new Rollover(
			$this->connection
		);

		$instance->update( 'Foo' );
	}

	public function testDelete() {

		$indices = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'delete', 'existsAlias' ] )
			->getMock();

		$indices->expects( $this->exactly( 3 ) )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$indices->expects( $this->exactly( 3 ) )
			->method( 'delete' );

		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->any() )
			->method( 'indices' )
			->will( $this->returnValue( $indices ) );

		$instance = new Rollover(
			$this->connection
		);

		$instance->delete( 'Foo' );
	}

	public function testUpdate_OnNoConnectionThrowsException() {

		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( false ) );

		$instance = new Rollover(
			$this->connection
		);

		$this->setExpectedException( '\SMW\Elastic\Exception\NoConnectionException' );
		$instance->update( 'Foo' );
	}

	public function testDelete_OnNoConnectionThrowsException() {

		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( false ) );

		$instance = new Rollover(
			$this->connection
		);

		$this->setExpectedException( '\SMW\Elastic\Exception\NoConnectionException' );
		$instance->delete( 'Foo' );
	}

}
