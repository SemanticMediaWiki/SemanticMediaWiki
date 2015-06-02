<?php

namespace SMW;

use Onoi\EventDispatcher\EventDispatcher;
use Onoi\EventDispatcher\EventDispatcherFactory;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EventHandler {

	/**
	 * @var EventHandler
	 */
	private static $instance = null;

	/**
	 * @var EventDispatcher
	 */
	private $eventDispatcher = null;

	/**
	 * @since 2.2
	 *
	 * @param EventDispatcher $eventDispatcher
	 */
	public function __construct( EventDispatcher $eventDispatcher ) {
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @since 2.2
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( self::newEventDispatcher() );
		}

		return self::$instance;
	}

	/**
	 * @since 2.2
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.2
	 *
	 * @return EventDispatcher
	 */
	public function getEventDispatcher() {
		return $this->eventDispatcher;
	}

	/**
	 * @since 2.2
	 *
	 * @return DispatchContext
	 */
	public function newDispatchContext() {
		return EventDispatcherFactory::getInstance()->newDispatchContext();
	}

	/**
	 * @since 2.3
	 *
	 * @param string $event
	 * @param Closure $callback
	 */
	public function addCallbackListener( $event, \Closure $callback ) {

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
