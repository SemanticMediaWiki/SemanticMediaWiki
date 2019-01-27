<?php

namespace SMW\Tests;

use SMW\EventHandler;

/**
 * @covers \SMW\EventHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EventHandlerTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		EventHandler::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\EventHandler',
			new EventHandler( $eventDispatcher )
		);

		$this->assertInstanceOf(
			'\SMW\EventHandler',
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
				$this->equalTo( 'foo' ),
				$this->anything() );

		$instance = new EventHandler( $eventDispatcher );
		$instance->addCallbackListener( 'foo', function (){} );
	}

}
