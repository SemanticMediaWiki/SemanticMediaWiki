<?php

namespace SMW\Tests\Elastic\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\DIWikiPage;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Jobs\FileIngestJob;
use SMW\SQLStore\SQLStore;
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

	private $testEnvironment;
	private $fileIndexer;
	private $title;
	private $logger;
	private $elasticFactory;
	private $jobQueue;

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

		$this->testEnvironment->registerObject( 'ElasticFactory', $this->elasticFactory );
		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileIngestJob::class,
			new FileIngestJob( $this->title )
		);
	}

	public function testPushIngestJob() {
		$subject = DIWikiPage::newFromText( __METHOD__, NS_FILE );

		$checkJobParameterCallback = static function ( $job ) use( $subject ) {
			return DIWikiPage::newFromTitle( $job->getTitle() )->equals( $subject );
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

		$config = $this->getMockBuilder( Config::class )
			->disableOriginalConstructor()
			->getMock();

		$client = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $client );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_FILE );

		$instance = new FileIngestJob(
			$this->title
		);

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

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_FILE );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$instance = new FileIngestJob(
			$this->title
		);

		$instance->setLogger( $this->logger );
		$instance->runFileIndexer();
	}

}
