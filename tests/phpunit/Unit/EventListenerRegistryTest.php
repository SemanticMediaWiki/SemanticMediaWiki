<?php

namespace SMW\Tests;

use Onoi\EventDispatcher\EventDispatcherFactory;
use Onoi\EventDispatcher\EventListenerCollection;
use SMW\DIWikiPage;
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

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$eventListenerCollection = $this->getMockBuilder( '\Onoi\EventDispatcher\EventListenerCollection' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\EventListenerRegistry',
			new EventListenerRegistry( $eventListenerCollection )
		);
	}

	public function testListenerCollection() {

		$eventListenerCollection = $this->getMockBuilder( '\Onoi\EventDispatcher\EventListenerCollection' )
			->disableOriginalConstructor()
			->setMethods( array( 'registerCallback' ) )
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
			EventDispatcherFactory::getInstance()->newGenericEventListenerCollection()
		);

		$this->verifyPropertyTypeChangeEvent( $instance );
		$this->verifyExporterResetEvent( $instance );
		$this->verifyFactboxCacheDeleteEvent( $instance );
		$this->verifyCachedPropertyValuesPrefetcherResetEvent( $instance );
		$this->verifyOnBeforeSemanticDataUpdateCompleteEvent( $instance );
		$this->verifyOnAfterSemanticDataUpdateCompleteEvent( $instance );
	}

	public function verifyExporterResetEvent( EventListenerCollection $instance ) {
		$this->assertListenerExecuteFor( 'exporter.reset', $instance, null );
	}

	public function verifyQueryComparatorResetEvent( EventListenerCollection $instance ) {
		$this->assertListenerExecuteFor( 'query.comparator.reset', $instance, null );
	}

	public function verifyPropertyTypeChangeEvent( EventListenerCollection $instance ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$dispatchContext = EventDispatcherFactory::getInstance()->newDispatchContext();
		$dispatchContext->set( 'subject', new DIWikiPage( 'Foo', NS_MAIN ) );

		$this->assertListenerExecuteFor( 'property.spec.change', $instance, $dispatchContext );
	}

	public function verifyFactboxCacheDeleteEvent( EventListenerCollection $instance ) {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 42 ) );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		$dispatchContext = EventDispatcherFactory::getInstance()->newDispatchContext();

		$dispatchContext->set(
			'title',
			$title
		);

		$this->assertListenerExecuteFor(
			'factbox.cache.delete',
			$instance,
			$dispatchContext
		);
	}

	public function verifyCachedPropertyValuesPrefetcherResetEvent( EventListenerCollection $instance ) {

		$dispatchContext = EventDispatcherFactory::getInstance()->newDispatchContext();

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
			'cached.propertyvalues.prefetcher.reset',
			$instance,
			$dispatchContext
		);
	}

	public function verifyOnBeforeSemanticDataUpdateCompleteEvent( EventListenerCollection $instance ) {

		$dispatchContext = EventDispatcherFactory::getInstance()->newDispatchContext();

		$dispatchContext->set(
			'subject',
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$this->assertListenerExecuteFor(
			'on.before.semanticdata.update.complete',
			$instance,
			$dispatchContext
		);
	}

	public function verifyOnAfterSemanticDataUpdateCompleteEvent( EventListenerCollection $instance ) {

		$dispatchContext = EventDispatcherFactory::getInstance()->newDispatchContext();

		$dispatchContext->set(
			'subject',
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$this->assertListenerExecuteFor(
			'on.after.semanticdata.update.complete',
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
