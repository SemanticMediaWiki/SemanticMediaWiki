<?php

namespace SMW\Tests\Integration\EventDispatcher;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\DispatchContext;
use SMW\EventDispatcher\EventDispatcherFactory;
use SMW\EventDispatcher\EventListener;
use SMW\EventDispatcher\EventListenerCollection;

/**
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class EventRegistryDispatchTest extends TestCase {

	public function testDispatchSomeEventsFromCollectionOfListenersWithoutPropagationStop() {
		$mockTester = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'doSomething', 'doSomethingElse' ] )
			->getMock();

		$mockTester->expects( $this->once() )
			->method( 'doSomething' );

		$mockTester->expects( $this->once() )
			->method( 'doSomethingElse' );

		$eventDispatcherFactory = new EventDispatcherFactory();

		$listenerCollectionRegistery = new ListenerCollectionRegistery(
			$eventDispatcherFactory->newGenericEventListenerCollection()
		);

		$eventDispatcher = $eventDispatcherFactory->newGenericEventDispatcher();
		$eventDispatcher->addListenerCollection( $listenerCollectionRegistery );

		$dispatchContext = new DispatchContext();
		$dispatchContext->set( 'mock', $mockTester );

		$eventDispatcher->dispatch( 'do.something', $dispatchContext );
	}

	public function testDispatchSomeEventsFromCollectionOfListenersWithPropagationStop() {
		$mockTester = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'doSomething', 'doSomethingElse' ] )
			->getMock();

		$mockTester->expects( $this->once() )
			->method( 'doSomething' );

		$mockTester->expects( $this->never() ) // PropagationStop
			->method( 'doSomethingElse' );

		$eventDispatcherFactory = new EventDispatcherFactory();

		$listenerCollectionRegistery = new ListenerCollectionRegistery(
			$eventDispatcherFactory->newGenericEventListenerCollection()
		);

		$eventDispatcher = $eventDispatcherFactory->newGenericEventDispatcher();
		$eventDispatcher->addListenerCollection( $listenerCollectionRegistery );

		$dispatchContext = new DispatchContext();
		$dispatchContext->set( 'mock', $mockTester );
		$dispatchContext->set( 'propagationstop', true );

		$eventDispatcher->dispatch( 'do.something', $dispatchContext );
	}

	public function testDispatchSomeEventsThroughAdHocListener() {
		$mockTester = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'doSomething' ] )
			->getMock();

		$mockTester->expects( $this->once() )
			->method( 'doSomething' );

		$eventDispatcherFactory = new EventDispatcherFactory();
		$eventDispatcher = $eventDispatcherFactory->newGenericEventDispatcher();

		$eventDispatcher->addListener( 'notify.bar', new BarListener() );

		$dispatchContext = new DispatchContext();
		$dispatchContext->set( 'mock', $mockTester );

		$eventDispatcher->dispatch( 'notify.bar', $dispatchContext );
		$eventDispatcher->dispatch( 'try.notify.empty.listener' );
	}

}

/**
 * Example required for the test
 */
class ListenerCollectionRegistery implements EventListenerCollection {

	private $eventListenerCollection;

	public function __construct( EventListenerCollection $eventListenerCollection ) {
		$this->eventListenerCollection = $eventListenerCollection;
	}

	public function getCollection() {
		return $this->addToListenerCollection()->getCollection();
	}

	private function addToListenerCollection() {
		$this->eventListenerCollection->registerCallback( 'do.something', static function ( DispatchContext $dispatchContext ) {
			$dispatchContext->get( 'mock' )->doSomething();
		} );

		$this->eventListenerCollection->registerCallback( 'do.something', static function ( DispatchContext $dispatchContext ) {
			$dispatchContext->get( 'mock' )->doSomethingElse();
		} );

		return $this->eventListenerCollection;
	}

}

class BarListener implements EventListener {

	public function execute( ?DispatchContext $dispatchContext = null ) {
		$dispatchContext->get( 'mock' )->doSomething();
	}

	public function isPropagationStopped() {
		return false;
	}
}
