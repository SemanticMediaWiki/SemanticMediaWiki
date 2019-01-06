<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\JobQueue;

/**
 * @covers \SMW\MediaWiki\JobQueue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class JobQueueTest extends \PHPUnit_Framework_TestCase {

	private $jobQueueGroup;

	protected function setup() {

		$this->jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			JobQueue::class,
			new JobQueue( $this->jobQueueGroup )
		);
	}

	public function testRunFromQueue() {

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'FakeJob' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );

		// MediaWiki's JobQueue::pop !!!
		try {
			$log = $instance->runFromQueue( [ 'FakeJob' => 2 ] );
		} catch ( \Exception $e ) {
			// Do nothing
		}
	}

	public function testPop() {

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'FakeJob' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );

		// MediaWiki's JobQueue::pop !!!
		try {
			$instance->pop( 'FakeJob' );
		} catch ( \Exception $e ) {
			// Do nothing
		}
	}

	public function testAck() {

		$job = $this->getMockBuilder( '\Job' )
			->disableOriginalConstructor()
			->setMethods( [ 'getType', 'run' ] )
			->getMock();

		$job->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->will( $this->returnValue( 'FakeJob' ) );

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'FakeJob' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );

		// MediaWiki's JobQueue::ack !!!
		try {
			$instance->ack( $job );
		} catch ( \Exception $e ) {
			// Do nothing
		}
	}

	public function testDeleteWithDisabledCache() {

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->setMethods( [ 'assertNotReadOnly', 'doDelete', 'doFlushCaches' ] )
			->getMockForAbstractClass();

		$jobQueue->expects( $this->any() )
			->method( 'assertNotReadOnly' )
			->will( $this->returnValue( false ) );

		$jobQueue->expects( $this->once() )
			->method( 'doDelete' );

		$jobQueue->expects( $this->once() )
			->method( 'doFlushCaches' );

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'FakeJob' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );
		$instance->disableCache( true );

		$instance->delete( 'FakeJob' );
	}

	public function testPush() {

		$fakeJob = $this->getMockBuilder( '\Job' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'push' );

		$instance = new JobQueue( $this->jobQueueGroup );
		$instance->push( $fakeJob );
	}

	public function testLazyPush() {

		if ( !method_exists( $this->jobQueueGroup, 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$fakeJob = $this->getMockBuilder( '\Job' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'lazyPush' );

		$instance = new JobQueue( $this->jobQueueGroup );
		$instance->lazyPush( $fakeJob );
	}

	public function testGetQueueSizes() {

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'getQueueSizes' );

		$instance = new JobQueue( $this->jobQueueGroup );
		$instance->getQueueSizes();
	}

	public function testGetQueueSize() {

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->setMethods( [ 'doGetSize', 'doFlushCaches' ] )
			->getMockForAbstractClass();

		$jobQueue->expects( $this->once() )
			->method( 'doGetSize' );

		$jobQueue->expects( $this->once() )
			->method( 'doFlushCaches' );

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'FakeJob' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );
		$instance->disableCache( true );

		$instance->getQueueSize( 'FakeJob' );
	}

	public function testHasPendingJob() {

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->setMethods( [ 'doGetSize' ] )
			->getMockForAbstractClass();

		$jobQueue->expects( $this->once() )
			->method( 'doGetSize' )
			->will( $this->returnValue( 1 ) );

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'FakeJob' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );

		$this->assertTrue(
			$instance->hasPendingJob( 'FakeJob' )
		);
	}

	public function testHasPendingJobWithLegacyName() {

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->setMethods( [ 'doGetSize' ] )
			->getMockForAbstractClass();

		$jobQueue->expects( $this->once() )
			->method( 'doGetSize' )
			->will( $this->returnValue( 1 ) );

		$this->jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'smw.fake' ) )
			->will( $this->returnValue( $jobQueue ) );

		$instance = new JobQueue( $this->jobQueueGroup );

		$this->assertTrue(
			$instance->hasPendingJob( 'SMW\FakeJob' )
		);
	}

}
