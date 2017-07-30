<?php

namespace SMW\MediaWiki\Hooks;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareInterface;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HookHandler implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @since  2.5
	 */
	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	protected function log( $message, $context = array() ) {
		$this->logger->info( $message, $context );
	}

}
