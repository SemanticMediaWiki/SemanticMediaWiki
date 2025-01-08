<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\UpdateTask;
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
class UpdateTaskTest extends \PHPUnit\Framework\TestCase {

	private $jobFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
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
		$updateJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\UpdateJob' )
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
