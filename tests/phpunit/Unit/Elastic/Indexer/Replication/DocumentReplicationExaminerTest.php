<?php

namespace SMW\Tests\Unit\Elastic\Indexer\Replication;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer;
use SMW\Elastic\Indexer\Replication\ReplicationError;
use SMW\Elastic\Indexer\Replication\ReplicationStatus;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @covers \SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DocumentReplicationExaminerTest extends TestCase {

	private const ALL_CHECKS = [
		DocumentReplicationExaminer::CHECK_DOCUMENT_EXISTS => true,
		DocumentReplicationExaminer::CHECK_MISSING_FILE_ATTACHMENT => true
	];

	/**
	 * @var int[] The number of each kind of read newExaminerOverDocuments() answered
	 */
	private array $requests = [];

	private $store;
	private $replicationStatus;
	private $elasticClient;
	private $idTable;

	protected function setUp(): void {
		$this->idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->replicationStatus = $this->getMockBuilder( ReplicationStatus::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( DummyClient::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DocumentReplicationExaminer::class,
			new DocumentReplicationExaminer( $this->store, $this->replicationStatus )
		);
	}

	public function testCheck_NoError() {
		$replicationStatus = [
			'modification_date' => Time::newFromTimestamp( 1272508900 ),
			'associated_revision' => 42
		];

		$dataItem = $this->getMockBuilder( DataItem::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItem->expects( $this->any() )
			->method( 'equals' )
			->willReturn( true );

		$this->idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 42 );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $dataItem ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$this->assertNull(
			$instance->check( WikiPage::newFromText( 'Foo' ) )
		);
	}

	public function testCheck_NotExists() {
		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$replicationStatus = [
			'modification_date' => false
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( WikiPage::newFromText( 'Foo' ) );

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
			->with(	'exists' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$params = [
			DocumentReplicationExaminer::CHECK_DOCUMENT_EXISTS => true,
		];

		$result = $instance->check( WikiPage::newFromText( 'Foo' ), $params );

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
			->willReturn( false );

		$time_es = Time::newFromTimestamp( 1272508900 );
		$time_store = Time::newFromTimestamp( 1272508903 );

		$replicationStatus = [
			'modification_date' => $time_es
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $time_store ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( WikiPage::newFromText( 'Foo' ) );

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
			->willReturn( false );

		$subject = WikiPage::newFromText( 'Foo' );
		$time = Time::newFromTimestamp( 1272508903 );

		$replicationStatus = [
			'modification_date' => $time,
			'associated_revision' => 99999
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 42 );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $time ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( WikiPage::newFromText( 'Foo' ) );

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
		$subject = WikiPage::newFromText( 'Foo', NS_FILE );
		$time = Time::newFromTimestamp( 1272508903 );

		$config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( true );

		$this->elasticClient->expects( $this->once() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$replicationStatus = [
			'modification_date' => $time,
			'associated_revision' => 99999
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 99999 );

		$getPropertyValuesCallCount = 0;
		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturnCallback( static function () use ( &$getPropertyValuesCallCount, $time ) {
				$getPropertyValuesCallCount++;
				return $getPropertyValuesCallCount === 1 ? [ $time ] : [];
			} );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

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
		$subject = WikiPage::newFromText( 'Foo', NS_FILE );
		$time = Time::newFromTimestamp( 1272508903 );

		$config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( true );

		$this->elasticClient->expects( $this->once() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$replicationStatus = [
			'modification_date' => $time,
			'associated_revision' => 99999
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 99999 );

		$getPropertyValuesCallCount = 0;
		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturnCallback( static function () use ( &$getPropertyValuesCallCount, $time ) {
				$getPropertyValuesCallCount++;
				return $getPropertyValuesCallCount === 1 ? [ $time ] : [ 'Foo' ];
			} );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

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
		$subject = WikiPage::newFromText( 'Foo', NS_FILE );
		$time = Time::newFromTimestamp( 1272508903 );

		$config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	'indexer.experimental.file.ingest' )
			->willReturn( false );

		$this->elasticClient->expects( $this->once() )
			->method( 'getConfig' )
			->willReturn( $config );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$replicationStatus = [
			'modification_date' => $time,
			'associated_revision' => 99999
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 99999 );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $time ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

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
		$subject = WikiPage::newFromText( 'Foo', NS_FILE );
		$time = Time::newFromTimestamp( 1272508903 );

		$this->elasticClient->expects( $this->any() )
			->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$replicationStatus = [
			'modification_date' => $time,
			'associated_revision' => 99999
		];

		$this->replicationStatus->expects( $this->once() )
			->method( 'get' )
			->with(	'modification_date_associated_revision' )
			->willReturn( $replicationStatus );

		$this->idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->willReturn( 99999 );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $time ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );

		$instance = new DocumentReplicationExaminer(
			$this->store,
			$this->replicationStatus
		);

		$result = $instance->check( $subject );

		$this->assertNull(
			$result
		);
	}

	public function testCheckAllReportsTheErrorOfEachSubject() {
		$instance = $this->newExaminerOverDocuments( false );

		$this->assertSame(
			[
				null,
				ReplicationError::TYPE_DOCUMENT_MISSING,
				ReplicationError::TYPE_MODIFICATION_DATE_DIFF,
				ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF,
				ReplicationError::TYPE_MODIFICATION_DATE_MISSING,
				ReplicationError::TYPE_FILE_ATTACHMENT_MISSING
			],
			$this->errorTypes( $instance->checkAll( $this->newSubjects(), self::ALL_CHECKS ) )
		);
	}

	public function testCheckAllFindsTheSameErrorsAsCheck() {
		$instance = $this->newExaminerOverDocuments( false );
		$subjects = $this->newSubjects();

		$this->assertSame(
			$this->errorTypes( array_map( static fn ( WikiPage $subject ) => $instance->check( $subject, self::ALL_CHECKS ), $subjects ) ),
			$this->errorTypes( $instance->checkAll( $subjects, self::ALL_CHECKS ) )
		);
	}

	public function testCheckAllChecksEachSubjectOnItsOwnWhenTheBatchRequestFails() {
		$instance = $this->newExaminerOverDocuments( true );
		$subjects = array_map( [ WikiPage::class, 'newFromText' ], [ 'Current', 'Missing', 'Outdated date' ] );

		$this->assertSame(
			[ null, ReplicationError::TYPE_DOCUMENT_MISSING, ReplicationError::TYPE_MODIFICATION_DATE_DIFF ],
			$this->errorTypes( $instance->checkAll( $subjects, self::ALL_CHECKS ) )
		);
	}

	public function testCheckAllReadsTheDocumentsDatesAndRevisionsInOneRequestEach() {
		$instance = $this->newExaminerOverDocuments( false );

		$instance->checkAll(
			array_map( [ WikiPage::class, 'newFromText' ], [ 'Current', 'Outdated date', 'Outdated revision' ] ),
			self::ALL_CHECKS
		);

		$this->assertSame( [ 'mget' => 1, 'prefetch dates' => 1, 'revisions' => 1 ], $this->requests );
	}

	/**
	 * @return WikiPage[] One subject per case of newExaminerOverDocuments()
	 */
	private function newSubjects(): array {
		return [
			WikiPage::newFromText( 'Current' ),
			WikiPage::newFromText( 'Missing' ),
			WikiPage::newFromText( 'Outdated date' ),
			WikiPage::newFromText( 'Outdated revision' ),
			WikiPage::newFromText( 'Undated' ),
			WikiPage::newFromText( 'Scan.pdf', NS_FILE )
		];
	}

	/**
	 * @param array<ReplicationError|null> $errors
	 *
	 * @return array<string|null>
	 */
	private function errorTypes( array $errors ): array {
		return array_map( static fn ( ?ReplicationError $error ) => $error?->getType(), $errors );
	}

	private function countRequest( string $request ): void {
		$this->requests[$request] = ( $this->requests[$request] ?? 0 ) + 1;
	}

	/**
	 * An examiner whose store and Elasticsearch documents hold six pages, by ID: 1 `Current` (up to date),
	 * 2 `Missing` (no document), 3 `Outdated date`, 4 `Outdated revision`, 5 `Undated` (a document without a
	 * modification date), 6 `File:Scan.pdf` (up to date, but without the file attachment that file ingest adds)
	 */
	private function newExaminerOverDocuments( bool $batchRequestFails ): DocumentReplicationExaminer {
		$date = Time::newFromTimestamp( 1272508900 );
		$olderDate = Time::newFromTimestamp( 1272500000 );
		$ids = [ 'Current' => 1, 'Missing' => 2, 'Outdated_date' => 3, 'Outdated_revision' => 4, 'Undated' => 5, 'Scan.pdf' => 6 ];
		$revisions = [ 1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => 50, 6 => 60 ];
		$storedDates = [ 1 => [ $date ], 2 => [ $date ], 3 => [ $date ], 4 => [ $date ], 5 => [], 6 => [ $date ] ];
		$documents = [
			1 => [ 'P:29' => [ 'datField' => [ $date->getJD() ] ], 'subject' => [ 'rev_id' => 10 ] ],
			3 => [ 'P:29' => [ 'datField' => [ $olderDate->getJD() ] ], 'subject' => [ 'rev_id' => 30 ] ],
			4 => [ 'P:29' => [ 'datField' => [ $date->getJD() ] ], 'subject' => [ 'rev_id' => 39 ] ],
			5 => [ 'subject' => [ 'rev_id' => 50 ] ],
			6 => [ 'P:29' => [ 'datField' => [ $date->getJD() ] ], 'subject' => [ 'rev_id' => 60 ] ]
		];
		$storedDatesOf = static fn ( WikiPage $subject ) => $storedDates[$ids[$subject->getDBkey()]];

		$client = $this->createMock( Client::class );
		$client->method( 'getIndexName' )->willReturn( 'smw-data' );
		$client->method( 'getConfig' )
			->willReturn( new Config( [ 'indexer' => [ 'experimental' => [ 'file.ingest' => true ] ] ] ) );
		$client->method( 'exists' )
			->willReturnCallback( static fn ( array $params ) => isset( $documents[$params['id']] ) );
		$client->method( 'get' )
			->willReturnCallback( static fn ( array $params ) => [ '_source' => $documents[$params['id']] ] );
		$client->method( 'mget' )
			->willReturnCallback( function ( array $params ) use ( $documents, $batchRequestFails ) {
				$this->countRequest( 'mget' );

				if ( $batchRequestFails ) {
					throw new RuntimeException( 'Gateway Timeout' );
				}

				return [ 'docs' => array_map(
					static fn ( int $id ) => isset( $documents[$id] )
						? [ '_id' => (string)$id, 'found' => true, '_source' => $documents[$id] ]
						: [ '_id' => (string)$id, 'found' => false ],
					$params['body']['ids']
				) ];
			} );

		$idTable = $this->createMock( EntityIdManager::class );
		$idTable->method( 'getSMWPageID' )
			->willReturnCallback( static fn ( string $dbKey ) => $ids[$dbKey] );
		$idTable->method( 'findAssociatedRev' )
			->willReturnCallback( function ( int $id ) use ( $revisions ) {
				$this->countRequest( 'revisions' );
				return $revisions[$id];
			} );
		$idTable->method( 'findAssociatedRevs' )
			->willReturnCallback( function ( array $idList ) use ( $revisions ) {
				$this->countRequest( 'revisions' );
				return array_intersect_key( $revisions, array_flip( $idList ) );
			} );

		$store = $this->createMock( SQLStore::class );
		$store->method( 'getObjectIds' )->willReturn( $idTable );
		$store->method( 'getConnection' )->willReturn( $client );
		$store->method( 'getPropertyValues' )
			->willReturnCallback( function ( WikiPage $subject, Property $property ) use ( $storedDatesOf ) {
				if ( $property->getKey() !== '_MDAT' ) {
					return [];
				}

				$this->countRequest( 'dates' );
				return $storedDatesOf( $subject );
			} );

		$prefetchItemLookup = $this->createMock( PrefetchItemLookup::class );
		$prefetchItemLookup->method( 'getPropertyValues' )
			->willReturnCallback( function ( array $subjects ) use ( $storedDatesOf ) {
				$this->countRequest( 'prefetch dates' );
				$values = [];

				foreach ( $subjects as $subject ) {
					$values[$subject->getId()] = $storedDatesOf( $subject );
				}

				return array_filter( $values );
			} );

		$store->method( 'service' )->willReturn( new PrefetchCache( $store, $prefetchItemLookup ) );

		return new DocumentReplicationExaminer( $store, new ReplicationStatus( $client ) );
	}

}
