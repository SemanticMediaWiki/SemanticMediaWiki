<?php

namespace SMW\Reporter;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NullMessageReporter implements MessageReporter {

	/**
	 * @param string $message
	 */
	public function reportMessage( $message ) {}

}
