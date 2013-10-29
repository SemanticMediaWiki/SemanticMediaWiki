<?php

namespace SMW;

/**
 * Interface for objects that can report messages
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface MessageReporter {

	/**
	 * Report the provided message.
	 *
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message );

}