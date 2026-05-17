<?php

namespace SMW\Tests\Unit\EventDispatcher\Listener;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\Listener\GenericCallbackEventListener;

/**
 * @covers \SMW\EventDispatcher\Listener\GenericCallbackEventListener
 *
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class GenericCallbackEventListenerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\EventDispatcher\EventListener',
			new GenericCallbackEventListener()
		);

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Listener\GenericCallbackEventListener',
			new GenericCallbackEventListener()
		);
	}

	public function testTryRegisterNonCallbackThrowsException() {
		$instance = new GenericCallbackEventListener();

		$this->expectException( 'RuntimeException' );
		$instance->registerCallback( 'foo' );
	}

	public function testRegisterClosure() {
		$instance = new GenericCallbackEventListener();

		$testClass = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runTest' ] )
			->getMock();

		$testClass->expects( $this->once() )
			->method( 'runTest' );

		$callback = static function () use( $testClass ) {
			$testClass->runTest();
		};

		$instance->registerCallback( $callback );
		$instance->execute();
	}

	public function testRegisterClosureViaConstrutor() {
		$testClass = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runTest' ] )
			->getMock();

		$testClass->expects( $this->once() )
			->method( 'runTest' );

		$callback = static function () use( $testClass ) {
			$testClass->runTest();
		};

		$instance = new GenericCallbackEventListener( $callback );
		$instance->execute();
	}

	public function testRegisterExecutableCallbackViaConstrutor() {
		$mockTester = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'runTest' ] )
			->getMock();

		$mockTester->expects( $this->once() )
			->method( 'runTest' );

		$instance = new GenericCallbackEventListener( [
			new FooMockTester( $mockTester ), 'invokedCallback' ]
		);

		$instance->execute();
	}

	public function testPropagationState() {
		$instance = new GenericCallbackEventListener();

		$this->assertFalse(
			$instance->isPropagationStopped()
		);

		$instance->setPropagationStopState( true );

		$this->assertTrue(
			$instance->isPropagationStopped()
		);
	}

}

class FooMockTester {

	private $mockTester;

	public function __construct( $mockTester ) {
		$this->mockTester = $mockTester;
	}

	public function invokedCallback() {
		$this->mockTester->runTest();
	}

}
