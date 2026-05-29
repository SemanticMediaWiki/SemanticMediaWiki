<?php

namespace SMW\Tests\Unit\Elastic\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\ElasticStore;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Jobs\IndexerRecoveryJob;
use SMW\MediaWiki\JobFactory;
use SMW\Tests\TestEnvironment;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @covers \SMW\Elastic\Jobs\IndexerRecoveryJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class IndexerRecoveryJobTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private $connection;
	private $title;
	private $cache;
	private ElasticStore $store;
	private $config;
	private $jobQueue;
	private $jobFactory;
	private $indexer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->createMock( ElasticStore::class );

		$this->config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$this->title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$elasticFactory = $this->getMockBuilder( ElasticFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$elasticFactory->expects( $this->any() )
			->method( 'newIndexer' )
			->willReturn( $this->indexer );

		$this->store->expects( $this->any() )
			->method( 'getElasticFactory' )
			->willReturn( $elasticFactory );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newJob( array $params = [], ?ElasticStore $store = null, ?BagOStuff $cache = null ): IndexerRecoveryJob {
		return new IndexerRecoveryJob(
			$this->title,
			$params,
			$store ?? $this->store,
			$cache ?? $this->cache,
			$this->jobFactory
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IndexerRecoveryJob::class,
			$this->newJob()
		);
	}

	public function testAllowRetries() {
		$instance = $this->newJob();

		$this->assertFalse(
			$instance->allowRetries()
		);
	}

	public function testRun_Create() {
		$this->indexer->expects( $this->once() )
			->method( 'create' );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->willReturn( true );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( ( new Config() ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = $this->newJob( [ 'create' => 'Foo#0##' ] );

		$instance->run();
	}

	public function testRun_Delete() {
		$this->indexer->expects( $this->once() )
			->method( 'delete' );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->willReturn( true );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( ( new Config() ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = $this->newJob( [ 'delete' => [ 'Foo' ] ] );

		$instance->run();
	}

	public function testRun_Index() {
		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->willReturn( true );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( ( new Config() ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->cache->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'smw:elastic:document:d1ea1c1728561f9d6aeed8c28b9b7617' ) );

		$instance = $this->newJob( [ 'index' => 'Foo#0##' ] );

		$instance->run();
	}

	public function testRun_Index_Retry() {
		$this->config->expects( $this->atLeastOnce() )
			->method( 'dotGet' )
			->with( $this->stringContains( 'indexer.job.recovery.retries' ) )
			->willReturn( 5 );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->willReturn( false );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->willReturn( $this->config );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		// Retry job is constructed via the injected JobFactory and inserted.
		$retryJob = $this->getMockBuilder( IndexerRecoveryJob::class )
			->disableOriginalConstructor()
			->getMock();

		$retryJob->expects( $this->once() )
			->method( 'insert' );

		$this->jobFactory->expects( $this->once() )
			->method( 'newIndexerRecoveryJob' )
			->willReturn( $retryJob );

		$instance = $this->newJob( [ 'index' => 'Foo#0##' ] );

		$instance->run();
	}

	public function testPushFromDocument() {
		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'push' );

		$data = [
			'subject' => [ 'serialization' => 'Foo#0##' ]
		];

		$document = new Document( 42, $data );

		IndexerRecoveryJob::pushFromDocument( $document );
	}

	public function testPushFromParams() {
		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'push' );

		IndexerRecoveryJob::pushFromParams( $this->title, [] );
	}

}
