<?php

namespace SMW\MediaWiki;

use SMW\Log\BaseLogger;

/**
 * Adapter class to implement a logger for a MediaWiki environment
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class Logger extends BaseLogger {

	/**
	 * Due to some MWDebug design issues we return the message to verify its
	 * correct implementation and expect wfDebug to work as expected
	 *
	 * MWDebug::getLog() does not provide sufficient access to cached messages
	 * while MWDebug::debugMsg is inferior to wfDebug (calls wfErrorLog to
	 * write the debug logs)
	 *
	 * Currently the log-level is considered as of lesser importance, wfDebug
	 * is to handle the output and categorization.
	 *
	 * @since 1.9.0.3
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 */
	public function log( $level, $message, array $context = array() ) {

		if ( $context !== array() ) {
			$message = $message . ' with context: '. serialize( $context );
		}

		wfDebug( $message );

		return $message;
	}

}