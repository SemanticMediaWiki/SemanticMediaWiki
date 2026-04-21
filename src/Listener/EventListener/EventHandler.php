<?php

namespace SMW\Listener\EventListener;

use Closure;
use Onoi\EventDispatcher\DispatchContext;
use Onoi\EventDispatcher\EventDispatcher;
use Onoi\EventDispatcher\EventDispatcherFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class EventHandler {

	private static ?EventHandler $instance = null;

	/**
	 * @since 2.2
	 */
	public function __construct( private readonly EventDispatcher $eventDispatcher ) {
	}

	/**
	 * @since 2.2
	 */
	public static function getInstance(): EventHandler {
		if ( self::$instance === null ) {
			self::$instance = new self( self::newEventDispatcher() );
		}

		return self::$instance;
	}

	/**
	 * @since 2.2
	 */
	public static function clear(): void {
		self::$instance = null;
	}

	/**
	 * @since 2.2
	 */
	public function getEventDispatcher(): EventDispatcher {
		return $this->eventDispatcher;
	}

	/**
	 * @since 2.2
	 */
	public function newDispatchContext(): DispatchContext {
		return EventDispatcherFactory::getInstance()->newDispatchContext();
	}

	/**
	 * @since 2.3
	 */
	public function addCallbackListener( string $event, Closure $callback ): void {
		$listener = EventDispatcherFactory::getInstance()->newGenericCallbackEventListener();
		$listener->registerCallback( $callback );

		$this->getEventDispatcher()->addListener(
			$event,
			$listener
		);
	}

	private static function newEventDispatcher() {
		$eventListenerRegistry = new EventListenerRegistry(
			EventDispatcherFactory::getInstance()->newGenericEventListenerCollection()
		);

		$eventDispatcher = EventDispatcherFactory::getInstance()->newGenericEventDispatcher();
		$eventDispatcher->addListenerCollection( $eventListenerRegistry );

		return $eventDispatcher;
	}

}
