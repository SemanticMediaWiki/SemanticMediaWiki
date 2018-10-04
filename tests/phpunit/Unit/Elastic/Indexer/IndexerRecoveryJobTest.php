<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\IndexerRecoveryJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\IndexerRecoveryJob
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

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IndexerRecoveryJob::class,
			new IndexerRecoveryJob( $this->title )
		);
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
			->method( 'fetch' );

		$instance = new IndexerRecoveryJob(
			$this->title,
			[ 'index' => 'Foo#0##']
		);

		$instance->run();
	}

}
