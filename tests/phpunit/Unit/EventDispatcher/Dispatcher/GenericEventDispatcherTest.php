<?php

namespace SMW\Tests\Unit\EventDispatcher\Dispatcher;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\DispatchContext;
use SMW\EventDispatcher\Dispatcher\GenericEventDispatcher;
use SMW\EventDispatcher\EventDispatcher;
use SMW\EventDispatcher\EventListener;
use SMW\EventDispatcher\EventListenerCollection;
use SMW\EventDispatcher\Exception\EventNotDispatchableException;
use SMW\EventDispatcher\Listener\GenericCallbackEventListener;
use stdClass;

/**
 * @covers \SMW\EventDispatcher\Dispatcher\GenericEventDispatcher
 *
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class GenericEventDispatcherTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EventDispatcher::class,
			new GenericEventDispatcher()
		);

		$this->assertInstanceOf(
			GenericEventDispatcher::class,
			new GenericEventDispatcher()
		);
	}

	public function testTryAddingListenerUsingInvalidEventIdentifierThrowsException() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$this->expectException( 'InvalidArgumentException' );
		$instance->addListener( new stdClass, $eventListener );
	}

	public function testAddListener() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->addListener( 'foo', $eventListener );

		$this->assertTrue(
			$instance->hasEvent( 'FOO' )
		);
	}

	public function testRemoveSpecificListener() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$genericCallbackEventListener = $this->getMockBuilder( GenericCallbackEventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->addListener( 'foo', $eventListener );
		$instance->addListener( 'foo', $genericCallbackEventListener );

		$this->assertTrue(
			$instance->hasEvent( 'FOO' )
		);

		$instance->removeListener( 'foo', $eventListener );

		$this->assertTrue(
			$instance->hasEvent( 'FOO' )
		);

		$instance->removeListener( 'foo', $genericCallbackEventListener );

		$this->assertFalse(
			$instance->hasEvent( 'FOO' )
		);
	}

	public function testRemoveAllListenerForSpecificEvent() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$genericCallbackEventListener = $this->getMockBuilder( GenericCallbackEventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->addListener( 'foo', $eventListener );
		$instance->addListener( 'foo', $genericCallbackEventListener );

		$this->assertTrue(
			$instance->hasEvent( 'FOO' )
		);

		$instance->removeListener( 'foo' );

		$this->assertFalse(
			$instance->hasEvent( 'FOO' )
		);
	}

	public function testTryRemovalOfListenersForUnknownEvent() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->addListener( 'foo', $eventListener );

		$instance->removeListener( 'bar', $eventListener );

		$this->assertTrue(
			$instance->hasEvent( 'FOO' )
		);
	}

	public function testDispatchEvent() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListener->expects( $this->once() )
			->method( 'execute' );

		$eventListener->expects( $this->once() )
			->method( 'isPropagationStopped' );

		$instance->addListener( 'foo', $eventListener );
		$instance->dispatch( 'foo' );
	}

	public function testDispatchEventWithContextToOverrideListenerPropagationStopState() {
		$instance = new GenericEventDispatcher();

		$dispatchContext = $this->getMockBuilder( DispatchContext::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchContext->expects( $this->once() )
			->method( 'isPropagationStopped' )
			->willReturn( true );

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListener->expects( $this->once() )
			->method( 'execute' )
			->with( $this->identicalTo( $dispatchContext ) );

		$eventListener->expects( $this->once() )
			->method( 'isPropagationStopped' )
			->willReturn( false );

		$instance->addListener( 'foo', $eventListener );

		$instance->dispatch( 'foo', $dispatchContext );
	}

	public function testDispatchFromListenerCollection() {
		$instance = new GenericEventDispatcher();

		$dispatchContext = $this->getMockBuilder( DispatchContext::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListener->expects( $this->once() )
			->method( 'execute' )
			->with( $this->identicalTo( $dispatchContext ) );

		$eventListenerCollection = $this->getMockBuilder( EventListenerCollection::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListenerCollection->expects( $this->once() )
			->method( 'getCollection' )
			->willReturn(
				[
					'foo' => [ $eventListener ],
					'bar' => [ $eventListener ] ] );

		$instance->addListenerCollection( $eventListenerCollection );

		$this->assertTrue(
			$instance->hasEvent( 'FOO' )
		);

		$this->assertTrue(
			$instance->hasEvent( 'bAr' )
		);

		$instance->dispatch( 'foo', $dispatchContext );
	}

	public function testDispatchListenerWithArrayContext() {
		$instance = new GenericEventDispatcher();

		$eventListener = $this->getMockBuilder( EventListener::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListener->expects( $this->once() )
			->method( 'execute' )
			->with( $this->callback( static function ( $dispatchContext ){ return $dispatchContext->get( 'Bar' ) == 123;
			} ) );

		$instance->addListener( 'FOO', $eventListener );
		$instance->dispatch( 'foo', [ 'Bar' => 123 ] );
	}

	public function testMissingListenerForEventThrowsException() {
		$instance = new GenericEventDispatcher();
		$instance->throwOnMissingEvent( true );

		$this->expectException( EventNotDispatchableException::class );
		$instance->dispatch( 'foo' );
	}

	public function testRegisterNonTraversableCollectionThrowsException() {
		$instance = new GenericEventDispatcher();

		$eventListenerCollection = $this->getMockBuilder( EventListenerCollection::class )
			->disableOriginalConstructor()
			->getMock();

		$eventListenerCollection->expects( $this->once() )
			->method( 'getCollection' )
			->willReturn( false );

		$this->expectException( 'RuntimeException' );
		$instance->addListenerCollection( $eventListenerCollection );
	}

}
