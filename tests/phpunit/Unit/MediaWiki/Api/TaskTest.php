<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;
use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Task;
use SMW\MediaWiki\Api\TaskFactory;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Jobs\NullJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Query\QueryResult;
use SMW\Settings;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Task
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TaskTest extends TestCase {

	private $apiFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$taskFactory = $this->getMockBuilder( TaskFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Task(
			$this->apiFactory->newApiMain( [] ),
			'smwtask',
			$taskFactory
		);

		$this->assertInstanceOf(
			Task::class,
			$instance
		);
	}

	public function testUpdateTask() {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->atLeastOnce() )
			->method( 'run' );

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$instance = new Task(
			$this->apiFactory->newApiMain( [
					'action'   => 'smwtask',
					'task'     => 'update',
					'params'   => json_encode( [ 'subject' => 'Foo#0##', 'ref' => [ 'Bar' ] ] ),
					'token'    => 'foo'
				]
			),
			'smwtask',
			$this->newRealTaskFactory( null, null, null, $jobFactory )
		);

		$instance->execute();
	}

	public function testDupLookupTask() {
		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$cache->expects( $this->once() )
			->method( 'save' );

		$entityTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityTable->expects( $this->atLeastOnce() )
			->method( 'findDuplicates' )
			->willReturn( [] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $entityTable );

		$instance = new Task(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwtask',
					'task'     => 'duplicate-lookup',
					'params'   => json_encode( [] ),
					'token'    => 'foo'
				]
			),
			'smwtask',
			$this->newRealTaskFactory( $store, null, $cache )
		);

		$instance->execute();
	}

	public function testGenericJobTask() {
		$nullJob = $this->getMockBuilder( NullJob::class )
			->disableOriginalConstructor()
			->getMock();

		$nullJob->expects( $this->atLeastOnce() )
			->method( 'insert' );

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newByType' )
			->with(
				'Foobar',
				$this->anything(),
				$this->anything() )
			->willReturn( $nullJob );

		$instance = new Task(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwtask',
					'task'     => 'insert-job',
					'params'   => json_encode(
						[
							'subject' => 'Foo#0##',
							'job' => 'Foobar'
						]
					),
					'token'    => 'foo'
				]
			),
			'smwtask',
			$this->newRealTaskFactory( null, null, null, $jobFactory )
		);

		$instance->execute();
	}

	public function testRunJobListTask() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->atLeastOnce() )
			->method( 'runFromQueue' )
			->with( [ 'FooJob' => 1 ] )
			->willReturn( [ '--job-done' ] );

		$instance = new Task(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwtask',
					'task'     => 'run-joblist',
					'params'   => json_encode(
						[
							'subject' => 'Foo#0##',
							'jobs' => [ 'FooJob' => 1 ]
						]
					),
					'token'    => 'foo'
				]
			),
			'smwtask',
			$this->newRealTaskFactory( null, $jobQueue )
		);

		$instance->execute();
	}

	public function testCheckQueryTask() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$instance = new Task(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwtask',
					'task'     => 'check-query',
					'params'   => json_encode(
						[
							'subject' => 'Foo#0##',
							'query' => [
								'query_hash_1#result_hash_2' => [
									'parameters' => [
										'limit' => 5,
										'offset' => 0,
										'querymode' => 1
									],
									'conditions' => ''
								]
							]
						]
					),
					'token'    => 'foo'
				]
			),
			'smwtask',
			$this->newRealTaskFactory( $store )
		);

		$instance->execute();
	}

	private function newRealTaskFactory(
		?Store $store = null,
		?JobQueue $jobQueue = null,
		?Cache $cache = null,
		?JobFactory $jobFactory = null
	): TaskFactory {
		if ( $store === null ) {
			$store = $this->getMockBuilder( Store::class )
				->disableOriginalConstructor()
				->getMockForAbstractClass();
		}

		if ( $jobQueue === null ) {
			$jobQueue = $this->getMockBuilder( JobQueue::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( $cache === null ) {
			$cache = $this->getMockBuilder( Cache::class )
				->disableOriginalConstructor()
				->getMock();
		}

		if ( $jobFactory === null ) {
			$jobFactory = $this->getMockBuilder( JobFactory::class )
				->disableOriginalConstructor()
				->getMock();
		}

		$settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();

		$settings->method( 'get' )
			->willReturnCallback( static fn ( string $key ) => $key === 'smwgCacheUsage' ? [] : null );

		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		return new TaskFactory( $store, $jobQueue, $cache, $settings, $jobFactory, $hookContainer );
	}

}
