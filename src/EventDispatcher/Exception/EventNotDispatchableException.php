<?php

namespace SMW\EventDispatcher\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 1.1
 *
 * @author mwjames
 */
class EventNotDispatchableException extends RuntimeException {

	/**
	 * @since 1.1
	 *
	 * @param string $event
	 */
	public function __construct( $event ) {
		parent::__construct( "Event delegation failed due to missing listeners for the `$event` event!" );
	}

}
