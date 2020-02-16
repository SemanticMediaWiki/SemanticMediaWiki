<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer;
use SMW\Elastic\Indexer\Replication\ReplicationError;
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

	protected function setUp() : void {

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

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

	public function testCheck_NoError() {

		$replicationStatus = [
			'modification_date' => DITime::newFromTimestamp( 1272508900 ),
			'associated_revision' => 42
		];

		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItem->expects( $this->any() )
			->method( 'equals' )
			->will( $this->returnValue( true ) );

		$this->idTable->expects( $this->at( 1 ) )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'modification_date_associated_revision' ) )
			->will( $this->returnValue( $replicationStatus ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $dataItem ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$this->assertNull(
			$instance->check( DIWikiPage::newFromText( 'Foo' ) )
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

		$this->assertInstanceOf(
			ReplicationError::class,
			$result
		);

		$this->assertEquals(
			ReplicationError::TYPE_MODIFICATION_DATE_MISSING,
			$result->getType()
		);

		$this->assertEquals(
			[
				'id' => 42
			],
			$result->getData()
		);
	}

	public function testCheck_DocumentExists() {

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'exists' ) )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$params = [
			DocumentReplicationExaminer::CHECK_DOCUMENT_EXISTS => true,
		];

		$result = $instance->check( DIWikiPage::newFromText( 'Foo' ), $params );

		$this->assertInstanceOf(
			ReplicationError::class,
			$result
		);

		$this->assertEquals(
			ReplicationError::TYPE_DOCUMENT_MISSING,
			$result->getType()
		);

		$this->assertEquals(
			[
				'id' => 42
			],
			$result->getData()
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

		$this->assertInstanceOf(
			ReplicationError::class,
			$result
		);

		$this->assertEquals(
			ReplicationError::TYPE_MODIFICATION_DATE_DIFF,
			$result->getType()
		);

		$this->assertEquals(
			[
				'id' => 42,
				'time_es' => '2010-04-29 02:41:40',
				'time_store' => '2010-04-29 02:41:43'
			],
			$result->getData()
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

		$this->assertInstanceOf(
			ReplicationError::class,
			$result
		);

		$this->assertEquals(
			ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF,
			$result->getType()
		);

		$this->assertEquals(
			[
				'id' => 42,
				'rev_es' => 99999,
				'rev_store' => 42
			],
			$result->getData()
		);
	}

	public function testCheck_MissingFileAttachment() {

		$subject = DIWikiPage::newFromText( 'Foo', NS_FILE );
		$time = DITime::newFromTimestamp( 1272508903 );

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( true ) );

		$this->elasticClient->expects( $this->once() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( 99999 ) );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time ] ) );

		$this->store->expects( $this->at( 4 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$params = [
			DocumentReplicationExaminer::CHECK_MISSING_FILE_ATTACHMENT => true
		];

		$result = $instance->check( $subject, $params );

		$this->assertInstanceOf(
			ReplicationError::class,
			$result
		);

		$this->assertEquals(
			ReplicationError::TYPE_FILE_ATTACHMENT_MISSING,
			$result->getType()
		);

		$this->assertEquals(
			[
				'id' => 42
			],
			$result->getData()
		);
	}

	public function testCheck_NoMissingFileAttachment() {

		$subject = DIWikiPage::newFromText( 'Foo', NS_FILE );
		$time = DITime::newFromTimestamp( 1272508903 );

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( true ) );

		$this->elasticClient->expects( $this->once() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( 99999 ) );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $time ] ) );

		$this->store->expects( $this->at( 4 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$params = [
			DocumentReplicationExaminer::CHECK_MISSING_FILE_ATTACHMENT => true
		];

		$result = $instance->check( $subject, $params );

		$this->assertNull(
			$result
		);
	}

	public function testCheck_FileAttachment_Disabled() {

		$subject = DIWikiPage::newFromText( 'Foo', NS_FILE );
		$time = DITime::newFromTimestamp( 1272508903 );

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( false ) );

		$this->elasticClient->expects( $this->once() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( 99999 ) );

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

		$params = [
			DocumentReplicationExaminer::CHECK_MISSING_FILE_ATTACHMENT => true
		];

		$result = $instance->check( $subject, $params );

		$this->assertNull(
			$result
		);
	}

	public function testCheck_FileAttachment_NoCheck() {

		$subject = DIWikiPage::newFromText( 'Foo', NS_FILE );
		$time = DITime::newFromTimestamp( 1272508903 );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( 99999 ) );

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

		$result = $instance->check( $subject );

		$this->assertNull(
			$result
		);
	}

}
