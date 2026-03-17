<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Tasks\UpdateTask;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\UpdateTask
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateTaskTest extends TestCase {

	private $jobFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new UpdateTask( $this->jobFactory );

		$this->assertInstanceOf(
			UpdateTask::class,
			$instance
		);
	}

	public function testProcess() {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->atLeastOnce() )
			->method( 'run' );

		$this->jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$instance = new UpdateTask(
			$this->jobFactory
		);

		$instance->process( [ 'subject' => 'Foo#0##', 'ref' => [ 'Bar' ] ] );
	}

}
