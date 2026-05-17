<?php

namespace SMW\EventDispatcher;

/**
 * @license GPL-2.0-or-later
 * @since 1.1
 *
 * @author mwjames
 */
trait EventDispatcherAwareTrait {

	/**
	 * @var EventDispatcher
	 */
	protected $eventDispatcher;

	/**
	 * @since 1.1
	 *
	 * @param EventDispatcher $eventDispatcher
	 */
	public function setEventDispatcher( EventDispatcher $eventDispatcher ) {
		$this->eventDispatcher = $eventDispatcher;
	}

}
