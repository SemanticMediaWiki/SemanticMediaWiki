<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\ReplicationStatus;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\ReplicationStatus
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ReplicationStatusTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ReplicationStatus::class,
			new ReplicationStatus( $this->connection )
		);
	}

	public function testGet_OnUnknownKeyThrowsException() {

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->get( 'Foo' );
	}

	public function testGet_refresh_interval() {

		$settings = [
			'Foo' => [
				'settings' => [
					'index' => [
						'refresh_interval' => 42
					]
				]
			]
		];

		$this->connection->expects( $this->once() )
			->method( 'getSettings' )
			->will( $this->returnValue( $settings ) );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			42,
			$instance->get( 'refresh_interval' )
		);
	}

	public function testGet_last_update() {

		$res = [
			'hits' => [
				'hits' => [
					[
						'_source' => [
							'P:29' => [
								'datField' => [ 2458322.0910764 ]
							]
						]
					]
				]
			]
		];

		$this->connection->expects( $this->once() )
			->method( 'search' )
			->will( $this->returnValue( [ $res, [] ] ) );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			'2018-07-22 14:11:09',
			$instance->get( 'last_update' )
		);
	}

}
