<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\JobListTask;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\JobListTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class JobListTaskTest extends \PHPUnit_Framework_TestCase {

	private $jobQueue;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new JobListTask( $this->jobQueue );

		$this->assertInstanceOf(
			JobListTask::class,
			$instance
		);
	}

	public function testProcess() {

		$this->jobQueue->expects( $this->atLeastOnce() )
			->method( 'runFromQueue' )
			->with( $this->equalTo( [ 'FooJob' => 1 ] ) )
			->will( $this->returnValue( '--job-done' ) );

		$instance = new JobListTask(
			$this->jobQueue
		);

		$instance->process( [ 'subject' => 'Foo#0##', 'jobs' => [ 'FooJob' => 1 ] ]);
	}

}
