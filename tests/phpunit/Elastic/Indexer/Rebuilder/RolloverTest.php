<?php

namespace SMW\Tests\Elastic\Indexer\Rebuilder;

use PHPUnit\Framework\TestCase;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Exception\NoConnectionException;
use SMW\Elastic\Indexer\Rebuilder\Rollover;

/**
 * @covers \SMW\Elastic\Indexer\Rebuilder\Rollover
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RolloverTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Client::class )
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

		$this->expectException( NoConnectionException::class );
		$instance->update( 'Foo' );
	}

	public function testDelete_OnNoConnectionThrowsException() {
		$this->connection->expects( $this->once() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Rollover(
			$this->connection
		);

		$this->expectException( NoConnectionException::class );
		$instance->delete( 'Foo' );
	}

}
