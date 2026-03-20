<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob;
use SMW\SQLStore\SQLStore;
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

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
		$this->testEnvironment->registerObject( 'Store', $store );
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
			new DeferredConstraintCheckUpdateJob( $title )
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
		$instance = new DeferredConstraintCheckUpdateJob(
			$subject->getTitle(),
			$parameters
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
