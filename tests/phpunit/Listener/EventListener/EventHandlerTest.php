<?php

namespace SMW\Tests\Listener\EventListener;

use Onoi\EventDispatcher\DispatchContext;
use Onoi\EventDispatcher\Dispatcher\GenericEventDispatcher;
use Onoi\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;
use SMW\Listener\EventListener\EventHandler;

/**
 * @covers \SMW\Listener\EventListener\EventHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class EventHandlerTest extends TestCase {

	protected function tearDown(): void {
		EventHandler::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
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
		$eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EventHandler( $eventDispatcher );

		$this->assertSame(
			$eventDispatcher,
			$instance->getEventDispatcher()
		);

		$this->assertInstanceOf(
			EventDispatcher::class,
			EventHandler::getInstance()->getEventDispatcher()
		);
	}

	public function testCanConstructDispatchContext() {
		$eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EventHandler( $eventDispatcher );

		$this->assertInstanceOf(
			DispatchContext::class,
			$instance->newDispatchContext()
		);
	}

	public function testAddCallbackListenerForAdhocRegistration() {
		$eventDispatcher = $this->getMockBuilder( GenericEventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$eventDispatcher->expects( $this->once() )
			->method( 'addListener' )
			->with(
				'foo',
				$this->anything() );

		$instance = new EventHandler( $eventDispatcher );
		$instance->addCallbackListener( 'foo', static function (){
		} );
	}

}
