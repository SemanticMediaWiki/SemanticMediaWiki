<?php

namespace SMW\Tests\MediaWiki\Deferred;

use SMW\MediaWiki\Deferred\ChangeTitleUpdate;
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
class ChangeTitleUpdateTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
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
		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $nullJob );

		$oldTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$newTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ChangeTitleUpdate(
			$oldTitle,
			$newTitle
		);

		$instance->doUpdate();
	}

}
