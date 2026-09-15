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
		yield 'update' => [ 'update', 'edit' ];
		yield 'check-query' => [ 'check-query', 'edit' ];
		yield 'run-joblist' => [ 'run-joblist', 'edit' ];
		yield 'run-entity-examiner' => [ 'run-entity-examiner', 'read' ];
		yield 'table-statistics' => [ 'table-statistics', 'smw-admin' ];
		yield 'duplicate-lookup' => [ 'duplicate-lookup', 'smw-admin' ];
		yield 'insert-job' => [ 'insert-job', 'smw-admin' ];
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
