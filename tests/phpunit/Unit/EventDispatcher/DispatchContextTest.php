<?php

namespace SMW\Tests\Unit\EventDispatcher;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\DispatchContext;
use stdClass;

/**
 * @covers \SMW\EventDispatcher\DispatchContext
 *
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class DispatchContextTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\EventDispatcher\DispatchContext',
			new DispatchContext()
		);
	}

	public function testRoundtrip() {
		$instance = new DispatchContext();

		$this->assertFalse(
			$instance->has( 'FOO' )
		);

		$instance->set( 'foo', 'bar' );

		$this->assertTrue(
			$instance->has( 'FOO' )
		);

		$this->assertEquals(
			'bar',
			$instance->get( 'FOO' )
		);

		$instance->set( 'foo', new stdClass );

		$this->assertEquals(
			new stdClass,
			$instance->get( 'FOO' )
		);
	}

	public function testNewFromArray() {
		$instance = DispatchContext::newFromArray( [ 'FOO' => 123 ] );

		$this->assertTrue(
			$instance->has( 'FOO' )
		);

		$this->assertEquals(
			123,
			$instance->get( 'foo' )
		);
	}

	public function testChangePropagationState() {
		$instance = new DispatchContext();

		$this->assertFalse(
			$instance->isPropagationStopped()
		);

		$instance->set( 'proPagationSTOP', true );

		$this->assertTrue(
			$instance->isPropagationStopped()
		);
	}

	public function testUnknownKeyThrowsException() {
		$instance = new DispatchContext();

		$this->expectException( 'InvalidArgumentException' );
		$instance->get( 'FOO' );
	}

}
