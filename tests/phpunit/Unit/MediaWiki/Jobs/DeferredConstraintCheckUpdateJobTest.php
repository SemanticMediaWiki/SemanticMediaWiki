<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DeferredConstraintCheckUpdateJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $jobQueue;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DeferredConstraintCheckUpdateJob::class,
			new DeferredConstraintCheckUpdateJob( $title )
		);
	}

	public function testPushJob() {

		$subject = DIWikiPage::newFromText( 'Foo' );

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
			DIWikiPage::newFromText( __METHOD__ ),
			[]
		];

		return $provider;
	}

}
