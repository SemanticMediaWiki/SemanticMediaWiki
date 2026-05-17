<?php

namespace SMW\EventDispatcher;

use SMW\EventDispatcher\Dispatcher\GenericEventDispatcher;
use SMW\EventDispatcher\Listener\GenericCallbackEventListener;
use SMW\EventDispatcher\Listener\GenericEventListenerCollection;
use SMW\EventDispatcher\Listener\NullEventListener;

/**
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class EventDispatcherFactory {

	/**
	 * @var EventDispatcherFactory|null
	 */
	private static $instance = null;

	/**
	 * @since 1.0
	 *
	 * @return EventDispatcherFactory
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 1.0
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 1.0
	 *
	 * @return GenericEventDispatcher
	 */
	public function newGenericEventDispatcher() {
		return new GenericEventDispatcher();
	}

	/**
	 * @since 1.0
	 *
	 * @return DispatchContext
	 */
	public function newDispatchContext() {
		return new DispatchContext();
	}

	/**
	 * @since 1.0
	 *
	 * @return NullEventListener
	 */
	public function newNullEventListener() {
		return new NullEventListener();
	}

	/**
	 * @since 1.0
	 *
	 * @return GenericCallbackEventListener
	 */
	public function newGenericCallbackEventListener() {
		return new GenericCallbackEventListener();
	}

	/**
	 * @since 1.0
	 *
	 * @return GenericEventListenerCollection
	 */
	public function newGenericEventListenerCollection() {
		return new GenericEventListenerCollection();
	}

}
