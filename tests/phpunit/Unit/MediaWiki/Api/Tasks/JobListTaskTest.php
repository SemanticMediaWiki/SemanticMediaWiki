<?php

namespace SMW\Tests\Unit\MediaWiki\Api\Tasks;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Tasks\JobListTask;
use SMW\MediaWiki\JobQueue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\JobListTask
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class JobListTaskTest extends TestCase {

	private $jobQueue;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new JobListTask( $this->jobQueue, [] );

		$this->assertInstanceOf(
			JobListTask::class,
			$instance
		);
	}

	public function testProcess() {
		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'runFromQueue' )
			->with( [ 'smw.fulltextSearchTableUpdate' => 1 ] )
			->willReturn( [ '--job-done' ] );

		$instance = new JobListTask(
			$this->jobQueue,
			[ 'smw.fulltextSearchTableUpdate' => 1 ]
		);

		$instance->process( [
			'subject' => 'Foo#0##',
			'jobs' => [ 'smw.fulltextSearchTableUpdate' => 1 ]
		] );
	}

	public function testAllowlistedPostEditJobIsPermitted() {
		$instance = new JobListTask(
			$this->jobQueue,
			[ 'smw.fulltextSearchTableUpdate' => 1 ]
		);

		$this->assertTrue(
			$instance->requestedWorkIsPermitted( [
				'jobs' => [ 'smw.fulltextSearchTableUpdate' => 1 ]
			] )
		);
	}

	public function testJobOutsidePostEditAllowlistIsNotPermitted() {
		$instance = new JobListTask(
			$this->jobQueue,
			[ 'smw.fulltextSearchTableUpdate' => 1 ]
		);

		$this->assertFalse(
			$instance->requestedWorkIsPermitted( [
				'jobs' => [ 'smw.parserCachePurgeJob' => 1 ]
			] )
		);
	}

	public function testListContainingAJobOutsideTheAllowlistIsNotPermitted() {
		$instance = new JobListTask(
			$this->jobQueue,
			[ 'smw.fulltextSearchTableUpdate' => 1 ]
		);

		$this->assertFalse(
			$instance->requestedWorkIsPermitted( [
				'jobs' => [
					'smw.fulltextSearchTableUpdate' => 1,
					'smw.parserCachePurgeJob' => 1
				]
			] )
		);
	}

}
