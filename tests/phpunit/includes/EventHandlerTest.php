<?php

namespace SMW\Tests;

use SMW\EventHandler;

/**
 * @covers \SMW\EventHandler
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EventHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\EventHandler',
			new EventHandler( $eventDispatcher )
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

}
