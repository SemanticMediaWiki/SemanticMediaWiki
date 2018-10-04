<?php

namespace SMW\Tests\Utils;

use Psr\Log\AbstractLogger;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpyLogger extends AbstractLogger {

	/**
	 * @var array
	 */
	private $logs = [];

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function log( $level, $message, array $context = [] ) {

		if ( is_array( $message ) ) {
			$message = json_encode( $message );
		}

		$this->logs[] = [ $level, $message, $context ];
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getLogs() {
		return $this->logs;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getMessagesAsString() {
		$message = '';

		foreach ( $this->logs as $log ) {
			$message .= ' ' . $log[1];
		}

		return $message;
	}

}
