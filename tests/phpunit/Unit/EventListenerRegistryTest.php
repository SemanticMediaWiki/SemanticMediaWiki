<?php

namespace SMW\Tests;

use Onoi\EventDispatcher\EventDispatcherFactory;
use Onoi\EventDispatcher\EventListenerCollection;
use SMW\EventListenerRegistry;

/**
 * @covers \SMW\EventListenerRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EventListenerRegistryTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $eventDispatcherFactory;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->eventDispatcherFactory = EventDispatcherFactory::getInstance();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$eventListenerCollection = $this->getMockBuilder( '\Onoi\EventDispatcher\EventListenerCollection' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EventListenerRegistry::class,
			new EventListenerRegistry( $eventListenerCollection )
		);
	}

	public function testListenerCollection() {

		$eventListenerCollection = $this->getMockBuilder( '\Onoi\EventDispatcher\EventListenerCollection' )
			->disableOriginalConstructor()
			->setMethods( [ 'registerCallback' ] )
			->getMockForAbstractClass();

		$eventListenerCollection->expects( $this->any() )
			->method( 'registerCallback' );

		$instance = new EventListenerRegistry( $eventListenerCollection );

		$this->assertInstanceOf(
			'\Onoi\EventDispatcher\EventListenerCollection',
			$instance
		);
	}

	public function testCanExecuteRegisteredListeners() {

		$instance = new EventListenerRegistry(
			$this->eventDispatcherFactory->newGenericEventListenerCollection()
		);

		$this->verifyExporterResetEvent( $instance );
		$this->verifyCachedPrefetcherResetEvent( $instance );
		$this->verifyCachedUpdateMarkerDeleteEvent( $instance );
	}

	public function verifyExporterResetEvent( EventListenerCollection $instance ) {
		$this->assertListenerExecuteFor( 'exporter.reset', $instance, null );
	}

	public function verifyQueryComparatorResetEvent( EventListenerCollection $instance ) {
		$this->assertListenerExecuteFor( 'query.comparator.reset', $instance, null );
	}

	public function verifyCachedPrefetcherResetEvent( EventListenerCollection $instance ) {

		$dispatchContext = $this->eventDispatcherFactory->newDispatchContext();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$dispatchContext->set(
			'title',
			$title
		);

		$this->assertListenerExecuteFor(
			'cached.prefetcher.reset',
			$instance,
			$dispatchContext
		);
	}

	public function verifyCachedUpdateMarkerDeleteEvent( EventListenerCollection $instance ) {

		$dispatchContext = $this->eventDispatcherFactory->newDispatchContext();

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->atLeastOnce() )
			->method( 'getHash' );

		$dispatchContext->set(
			'subject',
			$subject
		);

		$this->assertListenerExecuteFor(
			'cached.update.marker.delete',
			$instance,
			$dispatchContext
		);
	}

	private function assertListenerExecuteFor( $eventName, $instance, $dispatchContext = null ) {

		$executed = false;

		foreach ( $instance->getCollection() as $event => $listeners ) {

			if ( $eventName !== $event ) {
				continue;
			}

			foreach ( $listeners as $listener ) {
				$listener->execute( $dispatchContext );
				$executed = true;
			}
		}

		$this->assertTrue(
			$executed,
			"Failed asseting that '{$eventName}' was executed"
		);
	}

}
