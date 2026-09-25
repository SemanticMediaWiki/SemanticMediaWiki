<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use MediaWiki\HookContainer\HookContainer;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\TaskFactory;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\Settings;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @covers \SMW\MediaWiki\Api\TaskFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TaskFactoryTest extends TestCase {

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
		$instance = $this->newTaskFactory();

		$this->assertInstanceOf(
			TaskFactory::class,
			$instance
		);
	}

	public function testGetAllowedTypes() {
		$instance = $this->newTaskFactory();

		$this->assertIsArray(

			$instance->getAllowedTypes()
		);
	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testNewByType( $type ) {
		$instance = $this->newTaskFactory();

		$this->assertInstanceOf(
			Task::class,
			$instance->newByType( $type )
		);
	}

	/**
	 * @dataProvider requiredPermissionProvider
	 */
	public function testNewByTypeDeclaresRequiredPermission( string $type, string $expectedPermission ) {
		$instance = $this->newTaskFactory();

		$this->assertSame(
			$expectedPermission,
			$instance->newByType( $type )->getRequiredPermission()
		);
	}

	public function requiredPermissionProvider() {
		// `update` and `run-joblist` authorize against the caller-supplied
		// subject (see testNewByTypeDeclaresAuthorizationSubject), so they no
		// longer opt down to the wiki-wide `edit` right and inherit the safe
		// `smw-admin` default for the global-right path they never take.
		yield 'update' => [ 'update', 'smw-admin' ];
		yield 'check-query' => [ 'check-query', 'edit' ];
		yield 'run-joblist' => [ 'run-joblist', 'smw-admin' ];
		yield 'run-entity-examiner' => [ 'run-entity-examiner', 'read' ];
		yield 'table-statistics' => [ 'table-statistics', 'smw-admin' ];
		yield 'duplicate-lookup' => [ 'duplicate-lookup', 'smw-admin' ];
		yield 'insert-job' => [ 'insert-job', 'smw-admin' ];
	}

	/**
	 * @dataProvider authorizationSubjectProvider
	 */
	public function testNewByTypeDeclaresAuthorizationSubject( string $type, bool $authorizesSubject ) {
		$instance = $this->newTaskFactory();

		$subject = $instance->newByType( $type )
			->getAuthorizationSubject( [ 'subject' => 'Foo#0##' ] );

		$this->assertSame(
			$authorizesSubject,
			$subject !== null
		);
	}

	public function authorizationSubjectProvider() {
		// Tasks that act on a caller-supplied page authorize against that page;
		// every other task gates on its global getRequiredPermission() right.
		yield 'update' => [ 'update', true ];
		yield 'run-joblist' => [ 'run-joblist', true ];
		yield 'check-query' => [ 'check-query', false ];
		yield 'table-statistics' => [ 'table-statistics', false ];
		yield 'insert-job' => [ 'insert-job', false ];
	}

	public function testNewByTypeOnUnknownTypeThrowsException() {
		$instance = $this->newTaskFactory();

		$this->expectException( '\RuntimeException' );
		$instance->newByType( '__foo__' );
	}

	public function typeProvider() {
		$taskFactory = $this->newTaskFactory();

		yield $taskFactory->getAllowedTypes();
	}

	private function newTaskFactory(): TaskFactory {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();

		$settings->method( 'get' )
			->willReturnCallback( static fn ( string $key ) => $key === 'smwgCacheUsage' ? [] : null );

		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		return new TaskFactory( $store, $jobQueue, $cache, $settings, $jobFactory, $hookContainer );
	}

}
