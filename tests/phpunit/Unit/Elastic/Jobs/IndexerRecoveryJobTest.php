<?php

namespace SMW\Tests\Elastic\Jobs;

use SMW\Elastic\Jobs\IndexerRecoveryJob;
use SMW\Tests\TestEnvironment;
use SMW\Elastic\Indexer\Document;

/**
 * @covers \SMW\Elastic\Jobs\IndexerRecoveryJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IndexerRecoveryJobTest extends \PHPUnit_Framework_TestCase {

	private $connection;
	private $title;
	private $cache;
	private $store;
	private $config;
	private $jobQueue;
	private $indexer;
	private $elasticFactory;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->config = $this->getMockBuilder( '\SMW\Elastic\Config' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Cache', $this->cache );

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );

		$this->indexer = $this->getMockBuilder( '\SMW\Elastic\Indexer\Indexer' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory = $this->getMockBuilder( '\SMW\Elastic\ElasticFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticFactory->expects( $this->any() )
			->method( 'newIndexer' )
			->will( $this->returnValue( $this->indexer ) );

		$this->testEnvironment->registerObject( 'ElasticFactory', $this->elasticFactory );
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IndexerRecoveryJob::class,
			new IndexerRecoveryJob( $this->title )
		);
	}

	public function testAllowRetries() {

		$instance = new IndexerRecoveryJob( $this->title );

		$this->assertFalse(
			$instance->allowRetries()
		);
	}

	public function testRun_Create() {

		$this->indexer->expects( $this->once() )
			->method( 'create' );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( ( new \SMW\Options() ) ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );

		$instance = new IndexerRecoveryJob(
			$this->title,
			[ 'create' => 'Foo#0##']
		);

		$instance->run();
	}

	public function testRun_Delete() {

		$this->indexer->expects( $this->once() )
			->method( 'delete' );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( ( new \SMW\Options() ) ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );

		$instance = new IndexerRecoveryJob(
			$this->title,
			[ 'delete' => [ 'Foo' ] ]
		);

		$instance->run();
	}

	public function testRun_Index() {

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( ( new \SMW\Options() ) ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->stringContains( 'smw:elastic:document:d1ea1c1728561f9d6aeed8c28b9b7617' ) );

		$instance = new IndexerRecoveryJob(
			$this->title,
			[ 'index' => 'Foo#0##']
		);

		$instance->run();
	}

	public function testRun_Index_Retry() {

		$this->config->expects( $this->atLeastOnce() )
			->method( 'dotGet' )
			->with( $this->stringContains( 'indexer.job.recovery.retries' ) )
			->will( $this->returnValue( 5 ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'ping' )
			->will( $this->returnValue( false ) );

		$this->connection->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $this->config ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		// Check insert with next retry
		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$this->testEnvironment->registerObject( 'Store', $this->store );

		$instance = new IndexerRecoveryJob(
			$this->title,
			[ 'index' => 'Foo#0##']
		);

		$instance->run();
	}

	public function testPushFromDocument() {

		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'push' );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:elastic:document:d1ea1c1728561f9d6aeed8c28b9b7617' ) );

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
