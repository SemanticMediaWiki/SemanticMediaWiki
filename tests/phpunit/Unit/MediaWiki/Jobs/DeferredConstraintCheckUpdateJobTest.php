<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredConstraintCheckUpdateJobTest extends TestCase {

	private $testEnvironment;
	private $jobQueue;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		// pushJob() reaches batchInsert() -> ApplicationFactory->getJobQueue();
		// keep the global registration so the static path stays covered.
		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DeferredConstraintCheckUpdateJob::class,
			new DeferredConstraintCheckUpdateJob( $title, [], $this->jobFactory )
		);
	}

	public function testPushJob() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		DeferredConstraintCheckUpdateJob::pushJob(
			$subject->getTitle()
		);
	}

	/**
	 * @dataProvider jobProvider
	 */
	public function testRun( $subject, $parameters ) {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->once() )
			->method( 'run' );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$instance = new DeferredConstraintCheckUpdateJob(
			$subject->getTitle(),
			$parameters,
			$this->jobFactory
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function jobProvider() {
		$provider[] = [
			WikiPage::newFromText( __METHOD__ ),
			[]
		];

		return $provider;
	}

}
