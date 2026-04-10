<?php

namespace SMW\Tests\Unit\MediaWiki\Deferred;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Deferred\ChangeTitleUpdate;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Deferred\ChangeTitleUpdate
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangeTitleUpdateTest extends TestCase {

	private $testEnvironment;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->setMethods( [ 'newUpdateJob' ] )
			->getMock();

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->clearPendingDeferredUpdates();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ChangeTitleUpdate::class,
			new ChangeTitleUpdate()
		);
	}

	public function testDoUpdate() {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$oldTitle = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$newTitle = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ChangeTitleUpdate(
			$oldTitle,
			$newTitle
		);

		$instance->doUpdate();
	}

}
