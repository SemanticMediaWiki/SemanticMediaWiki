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
class SequenceTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {

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
		$this->setExpectedException( '\RuntimeException' );
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
			->will( $this->returnValue( 'foo' ) );

		$instance = new Sequence(
			$this->connection
		);

		$this->assertEquals(
			null,
			$instance->restart( 'Foo', 'bar')
		);
	}

	public function testPostgres() {

		$this->connection->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( 'postgres' ) );

		$this->connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) { return $callback(); } ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->equalTo( 'ALTER SEQUENCE Foo_bar_seq RESTART WITH 43' ) );

		$this->connection->expects( $this->once() )
			->method( 'selectField' )
			->will( $this->returnValue( 42 ) );

		$instance = new Sequence(
			$this->connection
		);

		$this->assertEquals(
			43,
			$instance->restart( 'Foo', 'bar' )
		);
	}

}
