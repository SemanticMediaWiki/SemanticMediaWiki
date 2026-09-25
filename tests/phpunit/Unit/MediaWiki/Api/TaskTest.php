<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Context\RequestContext;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\SimpleAuthority;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\UserIdentityValue;
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
use Wikimedia\ObjectCache\BagOStuff;

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

	use MockAuthorityTrait;

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

	public function testUpdateTaskRunsForCallerAuthorizedToEditSubject() {
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
			$this->newApiMainWithEditAuthority(
				[
					'action'   => 'smwtask',
					'task'     => 'update',
					'params'   => json_encode( [ 'subject' => 'Foo#0##', 'ref' => [ 'Bar' ] ] ),
					'token'    => 'foo'
				],
				true
			),
			'smwtask',
			$this->newRealTaskFactory( null, null, null, $jobFactory )
		);

		$instance->execute();
	}

	public function testUpdateTaskRefusedForCallerNotAuthorizedToEditSubject() {
		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		// The forced store update must never run for a title the caller may
		// not edit.
		$jobFactory->expects( $this->never() )
			->method( 'newUpdateJob' );

		$instance = new Task(
			$this->newApiMainWithEditAuthority(
				[
					'action'   => 'smwtask',
					'task'     => 'update',
					'params'   => json_encode( [ 'subject' => 'Foo#0##', 'ref' => [ 'Bar' ] ] ),
					'token'    => 'foo'
				],
				false
			),
			'smwtask',
			$this->newRealTaskFactory( null, null, null, $jobFactory )
		);

		$this->expectException( ApiUsageException::class );
		$instance->execute();
	}

	public function testDupLookupTask() {
		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'get' )
			->willReturn( false );

		$cache->expects( $this->once() )
			->method( 'set' );

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

	public function testRunJobListTaskRunsForCallerAuthorizedToEditSubject() {
		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->atLeastOnce() )
			->method( 'runFromQueue' )
			->with( [ 'FooJob' => 1 ] )
			->willReturn( [ '--job-done' ] );

		$instance = new Task(
			$this->newApiMainWithEditAuthority(
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
				],
				true
			),
			'smwtask',
			$this->newRealTaskFactory( null, $jobQueue )
		);

		$instance->execute();
	}

	public function testRunJobListTaskRefusedForCallerNotAuthorizedToEditSubject() {
		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();

		// A caller that may not edit the subject must not pop and run queued
		// jobs.
		$jobQueue->expects( $this->never() )
			->method( 'runFromQueue' );

		$instance = new Task(
			$this->newApiMainWithEditAuthority(
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
				],
				false
			),
			'smwtask',
			$this->newRealTaskFactory( null, $jobQueue )
		);

		$this->expectException( ApiUsageException::class );
		$instance->execute();
	}

	public function testRunJobListTaskRefusedForJobTypeOutsideThePostEditAllowlist() {
		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();

		// A job type the post-edit process never emits must not be run, even
		// for a caller authorized to edit the subject.
		$jobQueue->expects( $this->never() )
			->method( 'runFromQueue' );

		$instance = new Task(
			$this->newApiMainWithEditAuthority(
				[
					'action'   => 'smwtask',
					'task'     => 'run-joblist',
					'params'   => json_encode(
						[
							'subject' => 'Foo#0##',
							'jobs' => [ 'smw.parserCachePurgeJob' => 1 ]
						]
					),
					'token'    => 'foo'
				],
				true
			),
			'smwtask',
			$this->newRealTaskFactory( null, $jobQueue )
		);

		$this->expectException( ApiUsageException::class );
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

	public function testUnauthorizedUserCannotRunAdminTask() {
		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		// The task's side effect must never run for a user lacking the right.
		$jobFactory->expects( $this->never() )
			->method( 'newByType' );

		$context = new RequestContext();
		$context->setRequest( new FauxRequest(
			[
				'action' => 'smwtask',
				'task'   => 'insert-job',
				'params' => json_encode( [ 'subject' => 'Foo#0##', 'job' => 'Foobar' ] ),
				'token'  => 'foo'
			],
			true
		) );

		// A logged-in editor (not an anonymous user) still lacks `smw-admin`.
		$context->setAuthority(
			new SimpleAuthority( new UserIdentityValue( 42, 'Editor' ), [ 'read', 'edit' ] )
		);

		$instance = new Task(
			new ApiMain( $context, true ),
			'smwtask',
			$this->newRealTaskFactory( null, null, null, $jobFactory )
		);

		try {
			$instance->execute();
			$this->fail( 'Expected ApiUsageException for the missing permission' );
		} catch ( ApiUsageException $e ) {
			$this->assertSame( 'permissiondenied', $e->getMessageObject()->getApiCode() );
		}
	}

	private function newRealTaskFactory(
		?Store $store = null,
		?JobQueue $jobQueue = null,
		?BagOStuff $cache = null,
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
			$cache = $this->getMockBuilder( BagOStuff::class )
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
			->willReturnCallback( static fn ( string $key ) => match ( $key ) {
				'smwgCacheUsage' => [],
				'smwgPostEditUpdate' => [ 'run-jobs' => [ 'FooJob' => 1 ] ],
				default => null,
			} );

		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		return new TaskFactory( $store, $jobQueue, $cache, $settings, $jobFactory, $hookContainer );
	}

	/**
	 * Builds the module with a request context whose authority holds the
	 * wiki-wide `edit` right but can edit the requested page only when
	 * $canEditSubject is true, driving the object-level authorization the
	 * tasks depend on.
	 */
	private function newApiMainWithEditAuthority( array $params, bool $canEditSubject ): ApiMain {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );
		$context->setAuthority( $this->authorityAllowingEdit( $canEditSubject ) );

		return new ApiMain( $context, true );
	}

	/**
	 * The wiki-wide `edit` right is always held, as an anonymous caller does on
	 * a default wiki; only authority over the specific page varies. This is the
	 * boundary the fix enforces: holding `edit` globally must not authorize
	 * forcing work for a page the caller may not edit.
	 */
	private function authorityAllowingEdit( bool $canEditSubject ): Authority {
		return $this->mockAnonAuthority(
			static function ( string $permission, $target = null ) use ( $canEditSubject ) {
				if ( $permission !== 'edit' ) {
					return true;
				}

				return $target === null ? true : $canEditSubject;
			}
		);
	}

}
