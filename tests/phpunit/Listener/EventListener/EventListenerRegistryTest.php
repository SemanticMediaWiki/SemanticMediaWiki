<?php

namespace SMW\Tests\Listener\EventListener;

use Onoi\EventDispatcher\EventDispatcherFactory;
use Onoi\EventDispatcher\EventListenerCollection;
use SMW\Listener\EventListener\EventListenerRegistry;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Listener\EventListener\EventListenerRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EventListenerRegistryTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $eventDispatcherFactory;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->eventDispatcherFactory = EventDispatcherFactory::getInstance();
	}

	protected function tearDown(): void {
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
			->onlyMethods( [ 'registerCallback' ] )
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
	}

	public function verifyExporterResetEvent( EventListenerCollection $instance ) {
		$this->assertListenerExecuteFor( 'exporter.reset', $instance, null );
	}

	public function verifyQueryComparatorResetEvent( EventListenerCollection $instance ) {
		$this->assertListenerExecuteFor( 'query.comparator.reset', $instance, null );
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
