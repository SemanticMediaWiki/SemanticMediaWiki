<?php

namespace SMW\Tests\Unit\EventDispatcher\Exception;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\Exception\EventNotDispatchableException;

/**
 * @covers \SMW\EventDispatcher\Exception\EventNotDispatchableException
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.1
 *
 * @author mwjames
 */
class EventNotDispatchableExceptionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EventNotDispatchableException::class,
			new EventNotDispatchableException( 'foo' )
		);
	}

}
