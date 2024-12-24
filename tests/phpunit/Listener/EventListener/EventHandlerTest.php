<?php

namespace SMW\Tests\Listener\EventListener;

use SMW\Listener\EventListener\EventHandler;

/**
 * @covers \SMW\Listener\EventListener\EventHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EventHandlerTest extends \PHPUnit\Framework\TestCase {

	protected function tearDown(): void {
		EventHandler::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EventHandler::class,
			new EventHandler( $eventDispatcher )
		);

		$this->assertInstanceOf(
			EventHandler::class,
			EventHandler::getInstance()
		);
	}

	public function testGetEventDispatcher() {
		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EventHandler( $eventDispatcher );

		$this->assertSame(
			$eventDispatcher,
			$instance->getEventDispatcher()
		);

		$this->assertInstanceOf(
			'\Onoi\EventDispatcher\EventDispatcher',
			EventHandler::getInstance()->getEventDispatcher()
		);
	}

	public function testCanConstructDispatchContext() {
		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EventHandler( $eventDispatcher );

		$this->assertInstanceOf(
			'\Onoi\EventDispatcher\DispatchContext',
			$instance->newDispatchContext()
		);
	}

	public function testAddCallbackListenerForAdhocRegistration() {
		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\Dispatcher\GenericEventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$eventDispatcher->expects( $this->once() )
			->method( 'addListener' )
			->with(
				'foo',
				$this->anything() );

		$instance = new EventHandler( $eventDispatcher );
		$instance->addCallbackListener( 'foo', function (){
		} );
	}

}
