<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Indexer;
use SMW\Services\ServicesContainer;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\Indexer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IndexerTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $bulk;
	private Client $connection;
	private $logger;
	private $jobQueue;
	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->bulk = $this->getMockBuilder( '\SMW\Elastic\Indexer\Bulk' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $options );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->logger = $this->getMockBuilder( '\Psr\Log\NullLogger' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Indexer::class,
			new Indexer( $this->store, $this->bulk )
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

		$subject = DIWikiPage::newFromText( 'Foo' );
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
			$this->bulk
		);

		$instance->setLogger( $this->logger );
		$instance->create( $subject, [] );
	}

	public function testCreate_FailedConnection_PushJob() {
		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Indexer(
			$this->store,
			$this->bulk
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
			$this->bulk
		);

		$instance->setLogger( $this->logger );
		$instance->delete( [ 42, 1001 ] );
	}

	public function testDelete_FailedConnection_PushJob() {
		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$this->connection->expects( $this->any() )
			->method( 'ping' )
			->willReturn( false );

		$instance = new Indexer(
			$this->store,
			$this->bulk
		);

		$instance->setLogger( $this->logger );
		$instance->delete( [ 42, 1001 ] );
	}

	public function testIndexDocument() {
		$subject = DIWikiPage::newFromText( 'Foo' );
		$subject->setId( 42 );

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
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
			$this->bulk
		);

		$instance->setLogger( $this->logger );
		$instance->indexDocument( $document, false );
	}

	public function testIndexDocument_FailedConnection_PushJob() {
		$subject = DIWikiPage::newFromText( 'Foo' );

		$document = $this->getMockBuilder( '\SMW\Elastic\Indexer\Document' )
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
			$this->bulk
		);

		$instance->setLogger( $this->logger );
		$instance->indexDocument( $document, Indexer::REQUIRE_SAFE_REPLICATION );
	}

}
