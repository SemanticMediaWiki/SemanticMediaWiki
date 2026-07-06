<?php

namespace SMW\EventDispatcher;

/**
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
interface EventListenerCollection {

	/**
	 * Returns a collection of registered EventListeners
	 *
	 * @since 1.0
	 *
	 * @return array<string, EventListener[]>
	 */
	public function getCollection();

}
