<?php

namespace SMW\Tests\Elastic\Indexer\Rebuilder;

use SMW\Elastic\Indexer\Rebuilder\Rollover;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\Rebuilder\Rollover
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RolloverTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp(): void {
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
		$this->connection->expects( $this->once() )
			->method( 'updateAliases' );

		$this->connection->expects( $this->once() )
			->method( 'releaseLock' );

		$instance = new Rollover(
			$this->connection
		);

		$instance->rollover( 'Foo', 'v2' );
	}

	public function testUpdate() {
		$this->connection->expects( $this->once() )
			->method( 'updateAliases' );

		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$instance = new Rollover(
			$this->connection
		);

		$instance->update( 'Foo' );
	}

	public function testDelete() {
		$this->connection->expects( $this->exactly( 3 ) )
			->method( 'indexExists' )
			->willReturn( true );

		$this->connection->expects( $this->exactly( 3 ) )
			->method( 'deleteIndex' );

		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$instance = new Rollover(
			$this->connection
		);

		$instance->delete( 'Foo' );
	}

	public function testUpdate_OnNoConnectionThrowsException() {
		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Rollover(
			$this->connection
		);

		$this->expectException( '\SMW\Elastic\Exception\NoConnectionException' );
		$instance->update( 'Foo' );
	}

	public function testDelete_OnNoConnectionThrowsException() {
		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Rollover(
			$this->connection
		);

		$this->expectException( '\SMW\Elastic\Exception\NoConnectionException' );
		$instance->delete( 'Foo' );
	}

}
