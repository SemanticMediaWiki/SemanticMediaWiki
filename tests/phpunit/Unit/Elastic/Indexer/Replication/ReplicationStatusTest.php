<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\ReplicationStatus;
use SMW\Tests\PHPUnitCompat;
use SMWDITime as DITime;

/**
 * @covers \SMW\Elastic\Indexer\Replication\ReplicationStatus
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

	public function testGetAssociatedRev() {

		$doc = [
			'_source' => [
				'subject' =>[
					'rev_id' => 1001
				]
			]
		];

		$params = [
			'index' => 'FOO',
			'type' => 'data',
			'id' => 42,
			'_source_include' => [ 'subject.rev_id' ]
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->will( $this->returnValue( "FOO" ) );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( $params ) )
			->will( $this->returnValue( $doc ) );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			1001,
			$instance->getAssociatedRev( 42 )
		);
	}

	public function testGet_associated_revision() {

		$doc = [
			'_source' => [
				'subject' =>[
					'rev_id' => 1001
				]
			]
		];

		$params = [
			'index' => 'FOO',
			'type' => 'data',
			'id' => 42,
			'_source_include' => [ 'subject.rev_id' ]
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->will( $this->returnValue( "FOO" ) );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( $params ) )
			->will( $this->returnValue( $doc ) );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			1001,
			$instance->get( 'associated_revision', 42 )
		);
	}

	public function testGet_modification_date_associated_revision() {

		$doc = [
			'_source' => [
				'subject' => [
					'rev_id' => 1001
				],
				'P:29' => [
					'datField' => [ 2458322.0910764 ]
				]
			]
		];

		$params = [
			'index' => 'FOO',
			'type' => 'data',
			'id' => 42,
			'_source_include' => [ 'P:29.datField', 'subject.rev_id' ]
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->will( $this->returnValue( "FOO" ) );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( $params ) )
			->will( $this->returnValue( $doc ) );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			[
				'modification_date' => DITime::newFromJD( '2458322.0910764', DITime::CM_GREGORIAN, DITime::PREC_YMDT ),
				'associated_revision' => 1001
			],
			$instance->get( 'modification_date_associated_revision', 42 )
		);
	}

}
