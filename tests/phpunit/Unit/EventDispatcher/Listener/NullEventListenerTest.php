<?php

namespace SMW\Tests\Unit\EventDispatcher\Listener;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\Listener\NullEventListener;

/**
 * @covers \SMW\EventDispatcher\Listener\NullEventListener
 *
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class NullEventListenerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\EventDispatcher\EventListener',
			new NullEventListener()
		);

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Listener\NullEventListener',
			new NullEventListener()
		);
	}

	public function testExecute() {
		$instance = new NullEventListener();

		$this->assertNull(
			$instance->execute()
		);
	}

	public function testIsPropagationStopped() {
		$instance = new NullEventListener();

		$this->assertFalse(
			$instance->isPropagationStopped()
		);
	}

}
