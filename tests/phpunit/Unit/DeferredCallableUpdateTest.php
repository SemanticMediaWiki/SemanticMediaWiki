<?php

namespace SMW\Tests;

use SMW\DeferredCallableUpdate;

/**
 * @covers \SMW\DeferredCallableUpdate
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
			'\SMW\DeferredCallableUpdate',
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

		$this->assertEquals(
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

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$instance = new DeferredCallableUpdate(
			$callback
		);

		$instance->setFingerprint( __METHOD__ );
		$instance->markAsPending( true );
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateOnTransactionIdle() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			}
			) );

		$this->testEnvironment->clearPendingDeferredUpdates();

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
			$callback,
			$connection
		);

		$instance->waitOnTransactionIdle();
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testUpdateOnTransactionIdleWithMissingConnection() {

		$this->testEnvironment->clearPendingDeferredUpdates();

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

		$instance->waitOnTransactionIdle();
		$instance->pushUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

}
