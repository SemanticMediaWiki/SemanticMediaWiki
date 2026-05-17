<?php

namespace SMW\EventDispatcher;

/**
 * Dispatches events to registered listeners
 *
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
interface EventDispatcher {

	/**
	 * Whether an event identifier has been registered listeners or not
	 *
	 * @since 1.0
	 *
	 * @param string $event
	 *
	 * @return bool
	 */
	public function hasEvent( $event );

	/**
	 * Notifies all listeners registered to an event identifier
	 *
	 * @since 1.0
	 *
	 * @param string $event
	 * @param DispatchContext|array|null $dispatchContext
	 */
	public function dispatch( $event, $dispatchContext = null );

}
