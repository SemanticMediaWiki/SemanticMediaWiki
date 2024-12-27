<?php

namespace SMW\Tests\Elastic\Jobs;

use SMW\Elastic\Jobs\FileIngestJob;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Jobs\FileIngestJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileIngestJobTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $fileIndexer;
	private $title;
	private $logger;
	private $elasticFactory;
	private $jobQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileIndexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\FileIndexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->any() )
			->method( 'newIndexer' )
			->willReturn( $indexer );

		$this->logger = $this->getMockBuilder( '\Psr\Log\NullLogger' )
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

		$checkJobParameterCallback = function ( $job ) use( $subject ) {
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

		$config = $this->getMockBuilder( '\SMW\Elastic\Config' )
			->disableOriginalConstructor()
			->getMock();

		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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

		$config = $this->getMockBuilder( '\SMW\Elastic\Config' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->once() )
			->method( 'dotGet' )
			->with( 'indexer.job.file.ingest.retries' )
			->willReturn( 1 );

		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->atLeastOnce() )
			->method( 'getConfig' )
			->willReturn( $config );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
