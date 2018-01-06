<?php

namespace SMW\Tests\Updater;

use SMW\Updater\DeferredCallableUpdate;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Updater\DeferredCallableUpdate
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DeferredCallableUpdateTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $spyLogger;

	protected function setUp() {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();
	}

	protected function tearDown() {
		$this->testEnvironment->clearPendingDeferredUpdates();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$callback = function() {
			return null;
		};

		$this->assertInstanceOf(
			DeferredCallableUpdate::class,
			new DeferredCallableUpdate( $callback )
		);
	}

	public function testUpdate() {

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'doTest' ) )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateOnEmptyCallback() {

		$instance = new DeferredCallableUpdate();

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertContains(
			'Empty callback',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testUpdateOnLateCallback() {

		$instance = new DeferredCallableUpdate();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'doTest' ) )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance->setCallback( $callback );

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertContains(
			'[DeferrableUpdate] Added',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testWaitableUpdate() {

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'doTest' ) )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->markAsPending( true );
		$instance->pushUpdate();

		$instance->releasePendingUpdates();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateWithDisabledDeferredUpdate() {

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'doTest' ) )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->enabledDeferredUpdate( false );
		$instance->pushUpdate();
	}

	public function testOrigin() {

		$callback = function() {
		};

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setOrigin( 'Foo' );

		$this->assertContains(
			'Foo',
			$instance->getOrigin()
		);
	}

	public function testFilterDuplicateQueueEntryByFingerprint() {

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'doTest' ) )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testStage() {

		$instance = new DeferredCallableUpdate();

		$this->assertEquals(
			'post',
			$instance->getStage()
		);

		$instance->asPresend();

		$this->assertEquals(
			'pre',
			$instance->getStage()
		);
	}

}
