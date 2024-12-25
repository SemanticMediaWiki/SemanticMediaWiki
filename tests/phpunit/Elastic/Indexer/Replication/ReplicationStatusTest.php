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
class ReplicationStatusTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp(): void {
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

		$this->expectException( '\RuntimeException' );
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
			->willReturn( $settings );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			42,
			$instance->get( 'refresh_interval' )
		);
	}

	public function testGet_exists() {
		$params = [
			'index' => 'FOO',
			'id' => 1001
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->willReturn( "FOO" );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->with(	$params )
			->willReturn( true );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertTrue(
			$instance->get( 'exists', 1001 )
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
			->willReturn( [ $res, [] ] );

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
				'subject' => [
					'rev_id' => 1001
				]
			]
		];

		$params = [
			'index' => 'FOO',
			'id' => 42,
			'_source_includes' => [ 'subject.rev_id' ]
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->willReturn( "FOO" );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->with(	$params )
			->willReturn( $doc );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			1001,
			$instance->getAssociatedRev( 42 )
		);
	}

	public function testGetAssociatedRev_NotExists() {
		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( false );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertSame(
			0,
			$instance->getAssociatedRev( 42 )
		);
	}

	public function testGetAssociatedRev_NoMatch() {
		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertSame(
			0,
			$instance->getAssociatedRev( 42 )
		);
	}

	public function testGet_associated_revision() {
		$doc = [
			'_source' => [
				'subject' => [
					'rev_id' => 1001
				]
			]
		];

		$params = [
			'index' => 'FOO',
			'id' => 42,
			'_source_includes' => [ 'subject.rev_id' ]
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->willReturn( "FOO" );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->with(	$params )
			->willReturn( $doc );

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
			'id' => 42,
			'_source_includes' => [ 'P:29.datField', 'subject.rev_id' ]
		];

		$this->connection->expects( $this->once() )
			->method( 'getIndexName' )
			->willReturn( "FOO" );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->with(	$params )
			->willReturn( $doc );

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

	public function testGet_modification_date_associated_revision_not_exists() {
		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( false );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			[
				'modification_date' => false,
				'associated_revision' => 0
			],
			$instance->get( 'modification_date_associated_revision', 42 )
		);
	}

	public function testGet_modification_date_associated_revision_no_match() {
		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			[
				'modification_date' => false,
				'associated_revision' => 0
			],
			$instance->get( 'modification_date_associated_revision', 42 )
		);
	}

	public function testGetModificationDate_NoMatch() {
		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertFalse(
						$instance->getModificationDate( 42 )
		);
	}

	public function testGetModificationDate() {
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

		$this->connection->expects( $this->once() )
			->method( 'get' )
			->willReturn( $doc );

		$this->connection->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new ReplicationStatus(
			$this->connection
		);

		$this->assertEquals(
			DITime::newFromJD( '2458322.0910764', DITime::CM_GREGORIAN, DITime::PREC_YMDT ),
			$instance->getModificationDate( 42 )
		);
	}

}
