<?php

namespace SMW\Tests\Unit\Elastic\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Jobs\FileIngestJob;
use SMW\MediaWiki\JobFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Jobs\FileIngestJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FileIngestJobTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private $fileIndexer;
	private $title;
	private $logger;
	private $elasticFactory;
	private $jobQueue;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$indexer = $this->getMockBuilder( Indexer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fileIndexer = $this->getMockBuilder( FileIndexer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory = $this->getMockBuilder( ElasticFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->any() )
			->method( 'newIndexer' )
			->willReturn( $indexer );

		$this->logger = $this->getMockBuilder( NullLogger::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		// pushIngestJob() goes through MediaWiki's JobFactory which resolves
		// ElasticFactory from the global container; keep the test override
		// so the lazyPush path is exercised against the same mock.
		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newStore(): Store {
		return $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newJob(
		Title $title,
		array $params = [],
		?Store $store = null,
		?ElasticFactory $elasticFactory = null,
		?JobFactory $jobFactory = null
	): FileIngestJob {
		return new FileIngestJob(
			$title,
			$params,
			$store ?? $this->newStore(),
			$elasticFactory ?? $this->elasticFactory,
			$jobFactory ?? $this->jobFactory
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileIngestJob::class,
			$this->newJob( $this->title )
		);
	}

	public function testPushIngestJob() {
		$subject = WikiPage::newFromText( __METHOD__, NS_FILE );

		$checkJobParameterCallback = static function ( $job ) use( $subject ) {
			return WikiPage::newFromTitle( $job->getTitle() )->equals( $subject );
		};

		$this->jobQueue->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->callback( $checkJobParameterCallback ) );

		FileIngestJob::pushIngestJob( $subject->getTitle() );
	}

	public function testRunFileIndexer() {
		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileIndexer->expects( $this->once() )
			->method( 'findFile' )
			->willReturn( $file );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newFileIndexer' )
			->willReturn( $this->fileIndexer );

		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $client );

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_FILE );

		$instance = $this->newJob( $this->title, [], $store );

		$instance->setLogger( $this->logger );
		$instance->runFileIndexer();
	}

	public function testRunFileIndexer_NoFile_RequeueRetry() {
		$this->fileIndexer->expects( $this->once() )
			->method( 'findFile' )
			->willReturn( null );

		$this->elasticFactory->expects( $this->once() )
			->method( 'newFileIndexer' )
			->willReturn( $this->fileIndexer );

		$config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->once() )
			->method( 'dotGet' )
			->with( 'indexer.job.file.ingest.retries' )
			->willReturn( 1 );

		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->atLeastOnce() )
			->method( 'getConfig' )
			->willReturn( $config );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $client );

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_FILE );

		// Retry job is constructed via the injected JobFactory and inserted.
		$retryJob = $this->getMockBuilder( FileIngestJob::class )
			->disableOriginalConstructor()
			->getMock();

		$retryJob->expects( $this->once() )
			->method( 'insert' );

		$this->jobFactory->expects( $this->once() )
			->method( 'newFileIngestJob' )
			->willReturn( $retryJob );

		$instance = $this->newJob( $this->title, [], $store );

		$instance->setLogger( $this->logger );
		$instance->runFileIndexer();
	}

}
