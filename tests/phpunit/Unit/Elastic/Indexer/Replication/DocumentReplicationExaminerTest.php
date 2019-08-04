<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer;
use SMW\Tests\PHPUnitCompat;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDITime as DITime;

/**
 * @covers \SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DocumentReplicationExaminerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $replicationStatus;
	private $elasticClient;
	private $idTable;

	protected function setUp() {

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->idTable ) );

		$this->replicationStatus = $this->getMockBuilder( '\SMW\Elastic\Indexer\Replication\ReplicationStatus' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DocumentReplicationExaminer::class,
			new DocumentReplicationExaminer( $this->store, $this->replicationStatus )
		);
	}

	public function testCheck_NotExists() {

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

		$replicationStatus = [
			'modification_date' => false
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'modification_date_associated_revision' ) )
			->will( $this->returnValue( $replicationStatus ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( DIWikiPage::newFromText( 'Foo' ) );

		$this->assertEquals(
			[
				'modification_date_missing' => null
			],
			$result
		);
	}

	public function testCheck_ModificationDate() {

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

		$time_es = DITime::newFromTimestamp( 1272508900 );
		$time_store = DITime::newFromTimestamp( 1272508903 );

		$replicationStatus = [
			'modification_date' => $time_es
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'modification_date_associated_revision' ) )
			->will( $this->returnValue( $replicationStatus ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time_store ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( DIWikiPage::newFromText( 'Foo' ) );

		$this->assertEquals(
			[
				'modification_date_diff' => [
					'time_es' => '2010-04-29 02:41:40',
					'time_store' => '2010-04-29 02:41:43'
				]
			],
			$result
		);
	}

	public function testCheck_AssociateRev() {

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

		$subject = DIWikiPage::newFromText( 'Foo' );
		$time = DITime::newFromTimestamp( 1272508903 );

		$replicationStatus = [
			'modification_date' => $time,
			'associated_revision' => 99999
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'modification_date_associated_revision' ) )
			->will( $this->returnValue( $replicationStatus ) );

		$this->idTable->expects( $this->at( 1 ) )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( DIWikiPage::newFromText( 'Foo' ) );

		$this->assertEquals(
			[
				'associated_revision_diff' => [
					'rev_es' => 99999,
					'rev_store' => 42
				]
			],
			$result
		);
	}

}
