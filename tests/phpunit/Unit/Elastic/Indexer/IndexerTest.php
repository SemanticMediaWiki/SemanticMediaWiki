<?php

namespace SMW\Tests\Unit\Elastic\Indexer;

use ArrayObject;
use MediaWiki\Content\Content;
use MediaWiki\Content\TextContent;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Bulk;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\Indexer;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\Indexer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class IndexerTest extends TestCase {

	private $store;
	private $bulk;
	private Client $connection;
	private $logger;
	private $jobQueue;
	private $testEnvironment;
	private TitleFactory $titleFactory;
	private RevisionLookup $revisionLookup;
	private string $entityCollation;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$options = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->bulk = $this->getMockBuilder( Bulk::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $options );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->logger = $this->getMockBuilder( NullLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );

		$this->titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionLookup = $this->getMockBuilder( RevisionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		// The indexed sort key depends on the collation, so pin it instead of
		// inheriting whatever the wiki running the suite happens to configure
		$this->entityCollation = $GLOBALS['smwgEntityCollation'];
		$GLOBALS['smwgEntityCollation'] = 'identity';
	}

	protected function tearDown(): void {
		$GLOBALS['smwgEntityCollation'] = $this->entityCollation;
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Indexer::class,
			new Indexer( $this->store, $this->bulk, $this->titleFactory, $this->revisionLookup )
		);
	}

	public function testCreate() {
		$expected = [
			'index' => '_index_abc',
			'id' => 42,
			'body' => [ 'subject' => [
				'title' => 'Foo',
				'subobject' => '',
				'namespace' => 0,
				'interwiki' => '',
				'sortkey' => 'Foo'
			] ]
		];

		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setId( 42 );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$this->connection->expects( $this->any() )
			->method( 'getIndexName' )
			->with( 'data' )
			->willReturn( '_index_abc' );

		$this->connection->expects( $this->once() )
			->method( 'index' )
			->with( $expected )
			->willReturn( true );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->create( $subject, [] );
	}

	/**
	 * `create` replaces the whole document, so it has to derive the sort key the
	 * same way `DocumentCreator` does. Preferring the already collated `sort`
	 * option set by the SQLStore read path made the two disagree. See #7079.
	 */
	public function testCreateIgnoresTheCollatedSortOptionSetByTheSqlReadPath() {
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setId( 42 );
		$subject->setOption( 'sort', 'CKT5Aq4n135JBpy445EK11040HC1tC3S2m' );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$this->connection->expects( $this->any() )
			->method( 'getIndexName' )
			->with( 'data' )
			->willReturn( '_index_abc' );

		$this->connection->expects( $this->once() )
			->method( 'index' )
			->with( $this->callback(
				static fn ( array $params ) => $params['body']['subject']['sortkey'] === 'Foo'
			) )
			->willReturn( true );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->create( $subject, [] );
	}

	public function testCreate_FailedConnection_PushJob() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->create( $subject, [] );
	}

	public function testDelete() {
		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$this->bulk->expects( $this->once() )
			->method( 'clear' );

		$this->bulk->expects( $this->once() )
			->method( 'head' );

		$this->bulk->expects( $this->exactly( 2 ) )
			->method( 'delete' );

		$this->bulk->expects( $this->once() )
			->method( 'execute' );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->delete( [ 42, 1001 ] );
	}

	public function testDelete_FailedConnection_PushJob() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( false );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->titleFactory->method( 'newFromText' )->willReturn( $title );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->delete( [ 42, 1001 ] );
	}

	public function testIndexDocument() {
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setId( 42 );

		$document = $this->getMockBuilder( Document::class )
			->disableOriginalConstructor()
			->getMock();

		$document->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->bulk->expects( $this->once() )
			->method( 'head' );

		$this->bulk->expects( $this->once() )
			->method( 'infuseDocument' );

		$this->bulk->expects( $this->once() )
			->method( 'clear' );

		$this->bulk->expects( $this->once() )
			->method( 'execute' );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->indexDocument( $document, false );
	}

	public function testIndexDocumentsSendsTheDocumentsInOneRequest() {
		$requests = new ArrayObject();

		$this->newIndexerRecordingRequests( $requests, static fn () => [] )
			->indexDocuments( [ new Document( 1 ), new Document( 2 ), new Document( 3 ) ] );

		$this->assertSame( [ [ 1, 2, 3 ] ], $requests->getArrayCopy() );
	}

	public function testDocumentsOfAFailedRequestAreSentAgainOneAtATime() {
		$requests = new ArrayObject();
		$failWhenDocumentOneIsSent = static fn ( array $sentRequests ) =>
			in_array( 1, end( $sentRequests ), true ) ? 'Request Entity Too Large' : [];

		$this->newIndexerRecordingRequests( $requests, $failWhenDocumentOneIsSent )
			->indexDocuments( [ new Document( 1 ), new Document( 2 ), new Document( 3 ) ] );

		$this->assertSame( [ [ 1, 2, 3 ], [ 1 ], [ 2 ], [ 3 ] ], $requests->getArrayCopy() );
	}

	public function testIndexDocument_FailedConnection_PushJob() {
		$subject = WikiPage::newFromText( 'Foo' );

		$document = $this->getMockBuilder( Document::class )
			->disableOriginalConstructor()
			->getMock();

		$document->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Indexer(
			$this->store,
			$this->bulk,
			$this->titleFactory,
			$this->revisionLookup
		);

		$instance->setLogger( $this->logger );
		$instance->indexDocument( $document, Indexer::REQUIRE_SAFE_REPLICATION );
	}

	/**
	 * @param ArrayObject $requests Receives the document IDs of each bulk request
	 * @param callable $respond Returns the client's response, given the IDs of the requests sent so far
	 */
	private function newIndexerRecordingRequests( ArrayObject $requests, callable $respond ): Indexer {
		$this->connection->method( 'getIndexName' )
			->willReturn( 'smw-data' );

		$this->connection->method( 'bulk' )
			->willReturnCallback( static function ( array $params ) use ( $requests, $respond ) {
				$requests[] = array_column( array_column( $params['body'], 'index' ), '_id' );
				return $respond( $requests->getArrayCopy() );
			} );

		$indexer = new Indexer( $this->store, new Bulk( $this->connection ), $this->titleFactory, $this->revisionLookup );
		$indexer->setLogger( $this->logger );

		return $indexer;
	}

	public function testFetchNativeDataReturnsTheRevisionText() {
		$instance = $this->newIndexerForRevisionContent( new TextContent( 'Some page text' ) );

		$this->assertSame(
			'Some page text',
			$instance->fetchNativeData( 42 )
		);
	}

	public function testFetchNativeDataIsEmptyForNonTextContent() {
		$instance = $this->newIndexerForRevisionContent( $this->createStub( Content::class ) );

		$this->assertSame(
			'',
			$instance->fetchNativeData( 42 )
		);
	}

	private function newIndexerForRevisionContent( Content $content ): Indexer {
		$revision = $this->createStub( RevisionRecord::class );
		$revision->method( 'getContent' )->willReturn( $content );

		$this->revisionLookup->method( 'getRevisionById' )->willReturn( $revision );

		return new Indexer( $this->store, $this->bulk, $this->titleFactory, $this->revisionLookup );
	}

}
