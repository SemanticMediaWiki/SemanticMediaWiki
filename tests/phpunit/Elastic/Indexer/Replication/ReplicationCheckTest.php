<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\ReplicationCheck;
use SMW\Elastic\Indexer\Replication\ReplicationError;
use SMW\Tests\PHPUnitCompat;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDITime as DITime;

/**
 * @covers \SMW\Elastic\Indexer\Replication\ReplicationCheck
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ReplicationCheckTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $documentReplicationExaminer;
	private $entityCache;
	private $elasticClient;
	private $messageLocalizer;
	private $idTable;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->documentReplicationExaminer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ReplicationCheck::class,
			new ReplicationCheck( $this->store, $this->documentReplicationExaminer, $this->entityCache )
		);
	}

	public function testGetErrorTitle() {
		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getErrorTitle()
		);
	}

	public function testGetSeverityType() {
		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getSeverityType()
		);
	}

	public function testProcess_Invalid() {
		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$expected = [
			'done' => false
		];

		$this->assertEquals(
			$expected,
			$instance->process( [] )
		);
	}

	public function testProcess() {
		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$expected = [
			'done' => true,
			'html' => ''
		];

		$this->assertEquals(
			$expected,
			$instance->process( [ 'subject' => 'Foo#0##' ] )
		);
	}

	public function testCheckReplication_NotExists() {
		$error = new ReplicationError(
			ReplicationError::TYPE_MODIFICATION_DATE_MISSING,
			[ 'id' => 42 ]
		);

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$this->documentReplicationExaminer->expects( $this->once() )
			->method( 'check' )
			->willReturn( $error );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$html = $instance->checkReplication( DIWikiPage::newFromText( 'Foo' ), [] );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-missing-id"',
			$html
		);
	}

	public function testCheckReplication_NoConnection() {
		$elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$elasticClient->expects( $this->any() )
			->method( 'ping' )
			->willReturn( false );

		$elasticClient->expects( $this->never() )
			->method( 'hasMaintenanceLock' );

		$this->documentReplicationExaminer->expects( $this->never() )
			->method( 'check' );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$html = $instance->checkReplication( $subject );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-no-connection"',
			$html
		);
	}

	public function testCheckReplication_ConnectionHasMaintenanceLock() {
		$elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$elasticClient->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$elasticClient->expects( $this->once() )
			->method( 'hasMaintenanceLock' )
			->willReturn( true );

		$this->documentReplicationExaminer->expects( $this->never() )
			->method( 'check' );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$html = $instance->checkReplication( $subject );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-maintenance-mode"',
			$html
		);
	}

	public function testCheckReplication_Exception() {
		$error = new ReplicationError(
			ReplicationError::TYPE_EXCEPTION,
			[
				'id' => 42,
				'exception_error' => ''
			]
		);

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( true );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->documentReplicationExaminer->expects( $this->once() )
			->method( 'check' )
			->willReturn( $error );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$html = $instance->checkReplication( $subject );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-other-exception"',
			$html
		);
	}

	public function testCheckReplication_ModificationDate() {
		$error = new ReplicationError(
			ReplicationError::TYPE_MODIFICATION_DATE_DIFF,
			[
				'id' => 42,
				'time_es' => DITime::newFromTimestamp( 1272508900 )->asDateTime()->format( 'Y-m-d H:i:s' ),
				'time_store' => DITime::newFromTimestamp( 1272508903 )->asDateTime()->format( 'Y-m-d H:i:s' )
			]
		);

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( true );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->documentReplicationExaminer->expects( $this->once() )
			->method( 'check' )
			->willReturn( $error );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$html = $instance->checkReplication( $subject );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-divergent-date"',
			$html
		);

		$this->assertContains(
			'2010-04-29 02:41:43',
			$html
		);
	}

	public function testCheckReplication_AssociateRev() {
		$error = new ReplicationError(
			ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF,
			[
				'id' => 42,
				'rev_es' => 42,
				'rev_store' => 99999
			]
		);

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( true );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->documentReplicationExaminer->expects( $this->once() )
			->method( 'check' )
			->willReturn( $error );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$html = $instance->checkReplication( $subject, [] );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-divergent-revision"',
			$html
		);

		$this->assertContains(
			'99999',
			$html
		);
	}

	public function testCheckReplication_FileAttachment() {
		$error = new ReplicationError(
			ReplicationError::TYPE_FILE_ATTACHMENT_MISSING,
			[
				'id' => 42
			]
		);

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( true );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->documentReplicationExaminer->expects( $this->once() )
			->method( 'check' )
			->willReturn( $error );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$html = $instance->checkReplication( $subject, [] );

		$this->assertContains(
			'data-error-code="smw-es-replication-error-file-ingest-missing-file-attachment"',
			$html
		);
	}

	public function testMakeCacheKey() {
		$subject = DIWikiPage::newFromText( 'Foo', NS_MAIN );

		$this->assertSame(
			ReplicationCheck::makeCacheKey( $subject->getHash() ),
			ReplicationCheck::makeCacheKey( $subject )
		);
	}

	public function testGetReplicationFailures() {
		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->stringContains( 'smw:entity:1ce32bc49b4f8bc82a53098238ded208' ) );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->getReplicationFailures();
	}

	public function testDeleteReplicationTrail_OnTitle() {
		$subject = DIWikiPage::newFromText( 'Foo', NS_MAIN );

		$this->entityCache->expects( $this->once() )
			->method( 'deleteSub' )
			->with(
				$this->stringContains( 'smw:entity:1ce32bc49b4f8bc82a53098238ded208' ),
				$this->stringContains( 'smw:entity:b94628b92d22cd315ccf7abb5b1df3c0' ) );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->deleteReplicationTrail( $subject->getTitle() );
	}

	public function testDeleteReplicationTrail_OnSubject() {
		$subject = DIWikiPage::newFromText( 'Foo', NS_MAIN );

		$this->entityCache->expects( $this->once() )
			->method( 'deleteSub' )
			->with(
				$this->stringContains( 'smw:entity:1ce32bc49b4f8bc82a53098238ded208' ),
				$this->stringContains( 'smw:entity:b94628b92d22cd315ccf7abb5b1df3c0' ) );

		$instance = new ReplicationCheck(
			$this->store,
			$this->documentReplicationExaminer,
			$this->entityCache
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->deleteReplicationTrail( $subject );
	}

}
