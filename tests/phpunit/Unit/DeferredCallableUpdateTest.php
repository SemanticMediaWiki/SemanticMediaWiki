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

	protected function setUp() {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() {
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

		$instance->pushToDeferredUpdateList();

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testWaitableUpdate() {

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

		$instance->markAsPending( true );
		$instance->pushToDeferredUpdateList();

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
		$instance->pushToDeferredUpdateList();
	}

}
