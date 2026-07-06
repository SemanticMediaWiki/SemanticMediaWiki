<?php

namespace SMW\Tests\Unit\EventDispatcher\Listener;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\Listener\GenericCallbackEventListener;
use SMW\EventDispatcher\Listener\GenericEventListenerCollection;

/**
 * @covers \SMW\EventDispatcher\Listener\GenericEventListenerCollection
 *
 * @group onoi-event-dispatcher
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class GenericEventListenerCollectionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\EventDispatcher\EventListenerCollection',
			new GenericEventListenerCollection()
		);

		$this->assertInstanceOf(
			'\SMW\EventDispatcher\Listener\GenericEventListenerCollection',
			new GenericEventListenerCollection()
		);
	}

	public function testRegisterListener() {
		$eventListener = $this->getMockBuilder( '\SMW\EventDispatcher\EventListener' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new GenericEventListenerCollection();
		$instance->registerListener( 'FOO', $eventListener );

		$expected = [
			'foo' => [ $eventListener ]
		];

		$this->assertEquals(
			$expected,
			$instance->getCollection()
		);
	}

	public function testTryRegisterListenerUsingInvalidEventIdentifierThrowsException() {
		$eventListener = $this->getMockBuilder( '\SMW\EventDispatcher\EventListener' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new GenericEventListenerCollection();

		$this->expectException( 'InvalidArgumentException' );
		$instance->registerListener( new \stdClass, $eventListener );
	}

	public function testRegisterCallback() {
		$callback = static function () { return 'doSomething';
		};

		$instance = new GenericEventListenerCollection();
		$instance->registerCallback( 'fOo', $callback );

		$expected = [
			'foo' => [ new GenericCallbackEventListener( $callback ) ]
		];

		$this->assertEquals(
			$expected,
			$instance->getCollection()
		);
	}

	public function testTryRegisterCallbackUsingInvalidEventIdentifierThrowsException() {
		$callback = static function () { return 'doSomething';
		};

		$instance = new GenericEventListenerCollection();

		$this->expectException( 'InvalidArgumentException' );
		$instance->registerCallback( new \stdClass, $callback );
	}

	public function testTryRegisterCallbackUsingInvalidCallbackThrowsException() {
		$eventListener = $this->getMockBuilder( '\SMW\EventDispatcher\EventListener' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new GenericEventListenerCollection();

		$this->expectException( 'RuntimeException' );
		$instance->registerCallback( 'foo', new \stdClass );
	}

}
