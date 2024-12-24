<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\Sequence;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\Sequence
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SequenceTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Sequence::class,
			new Sequence( $this->connection )
		);
	}

	public function testConstructWithInvalidConnectionThrowsException() {
		$this->expectException( '\RuntimeException' );
		new Sequence( 'Foo' );
	}

	public function testMakeSequence() {
		$this->assertEquals(
			'Foo_bar_seq',
			Sequence::makeSequence( 'Foo', 'bar' )
		);
	}

	public function testNonPostgres() {
		$this->connection->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$instance = new Sequence(
			$this->connection
		);

		$this->assertNull(
						$instance->restart( 'Foo', 'bar' )
		);
	}

	public function testPostgres() {
		$this->connection->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'postgres' );

		$this->connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) { return $callback();
			} );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( 'ALTER SEQUENCE Foo_bar_seq RESTART WITH 43' );

		$this->connection->expects( $this->once() )
			->method( 'selectField' )
			->willReturn( 42 );

		$instance = new Sequence(
			$this->connection
		);

		$this->assertEquals(
			43,
			$instance->restart( 'Foo', 'bar' )
		);
	}

}
