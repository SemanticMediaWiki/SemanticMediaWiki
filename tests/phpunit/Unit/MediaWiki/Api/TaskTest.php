<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\Task;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Task
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TaskTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new Task(
			$this->apiFactory->newApiMain( [] ),
			'smwtask'
		);

		$this->assertInstanceOf(
			Task::class,
			$instance
		);
	}

	public function testUpdateTask() {

		$updateJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\UpdateJob' )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->atLeastOnce() )
			->method( 'run' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->will( $this->returnValue( $updateJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$instance = new Task(
			$this->apiFactory->newApiMain( [
					'action'   => 'smwtask',
					'task'     => 'update',
					'params'   => json_encode( [ 'subject' => 'Foo#0##', 'ref' => [ 'Bar' ] ] ),
					'token'    => 'foo'
				]
			),
			'smwtask'
		);

		$instance->execute();
	}

	public function testDupLookupTask() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$cache->expects( $this->once() )
			->method( 'save' );

		$entityTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityTable->expects( $this->atLeastOnce() )
			->method( 'findDuplicates' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );
		$this->testEnvironment->registerObject( 'Cache', $cache );

		$instance = new Task(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwtask',
					'task'     => 'duplicate-lookup',
					'params'   => json_encode( [] ),
					'token'    => 'foo'
				]
			),
			'smwtask'
		);

		$instance->execute();
	}

	public function testGenericJobTask() {

		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$nullJob->expects( $this->atLeastOnce() )
			->method( 'insert' );

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newByType' )
			->with(
				$this->equalTo( 'Foobar' ),
				$this->anything(),
				$this->anything() )
			->will( $this->returnValue( $nullJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

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
			'smwtask'
		);

		$instance->execute();
	}

	public function testRunJobListTask() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->atLeastOnce() )
			->method( 'runFromQueue' )
			->with( $this->equalTo( [ 'FooJob' => 1 ] ) )
			->will( $this->returnValue( '--job-done' ) );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

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
			'smwtask'
		);

		$instance->execute();
	}

	public function testCheckQueryTask() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$this->testEnvironment->registerObject( 'Store', $store );

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
			'smwtask'
		);

		$instance->execute();
	}

}
