<?php

namespace SMW\Tests\Unit\EventDispatcher;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\EventDispatcherFactory;

/**
 * @covers \SMW\EventDispatcher\EventDispatcherFactory
 *
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class EventDispatcherFactoryTest extends TestCase {

	public function testCanConstruct() {
		$instance = new EventDispatcherFactory();

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\EventDispatcherFactory',
			$instance
		);

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\EventDispatcherFactory',
			EventDispatcherFactory::getInstance()
		);

		EventDispatcherFactory::clear();
	}

	public function testCanConstructDispatchContext() {
		$instance = new EventDispatcherFactory();

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\DispatchContext',
			$instance->newDispatchContext()
		);
	}

	public function testCanConstructGenericEventDispatcher() {
		$instance = new EventDispatcherFactory();

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Dispatcher\GenericEventDispatcher',
			$instance->newGenericEventDispatcher()
		);
	}

	public function testCanConstructNullEventListener() {
		$instance = new EventDispatcherFactory();

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Listener\NullEventListener',
			$instance->newNullEventListener()
		);
	}

	public function testCanConstructGenericCallbackEventListener() {
		$instance = new EventDispatcherFactory();

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Listener\GenericCallbackEventListener',
			$instance->newGenericCallbackEventListener()
		);
	}

	public function testCanConstructGenericEventListenerCollection() {
		$instance = new EventDispatcherFactory();

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Listener\GenericEventListenerCollection',
			$instance->newGenericEventListenerCollection()
		);
	}

}
