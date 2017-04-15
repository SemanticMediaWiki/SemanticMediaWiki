<?php

namespace SMW\Log;

/**
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class NullLogger extends BaseLogger {

	/**
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return null
	 */
	public function log( $level, $message, array $context = array() ) {}

}