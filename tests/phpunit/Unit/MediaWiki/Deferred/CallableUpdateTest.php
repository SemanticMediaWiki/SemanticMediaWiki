<?php

namespace SMW\Tests\MediaWiki\Deferred;

use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Deferred\CallableUpdate
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CallableUpdateTest extends \PHPUnit_Framework_TestCase {

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
			CallableUpdate::class,
			new CallableUpdate( $callback )
		);
	}

	public function testUpdate() {

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new CallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateOnEmptyCallback() {

		$instance = new CallableUpdate();

		$instance->setLogger( $this->spyLogger );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->assertContains(
			'Empty callback',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testUpdateOnLateCallback() {

		$instance = new CallableUpdate();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
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
			'Added',
			$this->spyLogger->getMessagesAsString()
		);
	}

	public function testWaitableUpdate() {

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new CallableUpdate(
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
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new CallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->enabledDeferredUpdate( false );
		$instance->pushUpdate();
	}

	public function testOrigin() {

		$callback = function() {
		};

		$instance = new CallableUpdate(
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
			->setMethods( [ 'doTest' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doTest' );

		$callback = function() use ( $test ) {
			$test->doTest();
		};

		$instance = new CallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$instance = new CallableUpdate(
			$callback
		);

		$instance->setLogger( $this->spyLogger );

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testStage() {

		$instance = new CallableUpdate();

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
