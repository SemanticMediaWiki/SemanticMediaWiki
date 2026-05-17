<?php

namespace SMW\EventDispatcher\Listener;

use SMW\EventDispatcher\DispatchContext;
use SMW\EventDispatcher\EventListener;

/**
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class NullEventListener implements EventListener {

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function execute( ?DispatchContext $dispatchContext = null ) {
	}

	/**
	 * @since 1.0
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped() {
		return false;
	}

}
