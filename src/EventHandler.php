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

}
