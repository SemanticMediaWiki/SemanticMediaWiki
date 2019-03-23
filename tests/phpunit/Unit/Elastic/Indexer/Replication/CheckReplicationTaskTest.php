<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\CheckReplicationTask;
use SMW\Tests\PHPUnitCompat;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDITime as DITime;

/**
 * @covers \SMW\Elastic\Indexer\Replication\CheckReplicationTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CheckReplicationTaskTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $replicationStatus;
	private $entityCache;
	private $elasticClient;
	private $idTable;

	protected function setUp() {

		$this->idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
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

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CheckReplicationTask::class,
			new CheckReplicationTask( $this->store, $this->replicationStatus, $this->entityCache )
		);
	}

	public function testCheckReplication_NotExists() {

		$this->replicationStatus->expects( $this->once() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$html = $instance->checkReplication( DIWikiPage::newFromText( 'Foo' ), [] );

		$this->assertContains(
			'smw-highlighter',
			$html
		);
	}

	public function testCheckReplication_ModificationDate() {

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( true ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$subject = DIWikiPage::newFromText( 'Foo' );
		$time_es = DITime::newFromTimestamp( 1272508900 );
		$time_store = DITime::newFromTimestamp( 1272508903 );

		$this->replicationStatus->expects( $this->once() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( $time_es ) );

		$this->store->expects( $this->at( 2 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time_store ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$html = $instance->checkReplication( $subject, [] );

		$this->assertContains(
			'smw-highlighter',
			$html
		);

		$this->assertContains(
			'2010-04-29 02:41:43',
			$html
		);
	}

	public function testCheckReplication_AssociateRev() {

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( true ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$subject = DIWikiPage::newFromText( 'Foo' );
		$time = DITime::newFromTimestamp( 1272508903 );

		$this->replicationStatus->expects( $this->once() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( $time ) );

		$this->replicationStatus->expects( $this->once() )
			->method( 'getAssociatedRev' )
			->will( $this->returnValue( 99999 ) );

		$this->idTable->expects( $this->at( 1 ) )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->at( 2 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$html = $instance->checkReplication( $subject, [] );

		$this->assertContains(
			'smw-highlighter',
			$html
		);

		$this->assertContains(
			'99999',
			$html
		);
	}

	public function testCheckReplication_File() {

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( true ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$subject = DIWikiPage::newFromText( 'Foo', NS_FILE );
		$time = DITime::newFromTimestamp( 1272508903 );

		$this->replicationStatus->expects( $this->once() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( $time ) );

		$this->store->expects( $this->at( 2 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time ] ) );

		$this->store->expects( $this->at( 4 ) )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject ),
				$this->equalTo( new DIProperty( '_FILE_ATTCH' ) ) )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$html = $instance->checkReplication( $subject, [] );

		$this->assertContains(
			'smw-highlighter',
			$html
		);
	}

	public function testMakeCacheKey() {

		$subject = DIWikiPage::newFromText( 'Foo', NS_MAIN );

		$this->assertSame(
			CheckReplicationTask::makeCacheKey( $subject->getHash() ),
			CheckReplicationTask::makeCacheKey( $subject )
		);
	}

	public function testGetReplicationFailures() {

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->stringContains( 'smw:entity:1ce32bc49b4f8bc82a53098238ded208' ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$instance->getReplicationFailures();
	}

	public function testDeleteReplicationTrail() {

		$subject = DIWikiPage::newFromText( 'Foo', NS_MAIN );

		$this->entityCache->expects( $this->once() )
			->method( 'deleteSub' )
			->with(
				$this->stringContains( 'smw:entity:1ce32bc49b4f8bc82a53098238ded208' ),
				$this->stringContains( 'smw:entity:b94628b92d22cd315ccf7abb5b1df3c0' ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$instance->deleteReplicationTrail( $subject->getTitle() );
	}

}
