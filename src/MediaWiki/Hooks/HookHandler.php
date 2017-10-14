<?php

namespace SMW\MediaWiki\Hooks;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareInterface;
use SMW\Options;

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
	 * @var Options
	 */
	private $options;

	/**
	 * @since 2.5
	 */
	public function __construct() {
		$this->logger = new NullLogger();
		$this->options = new Options();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = new Options( $options );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {
		return $this->options->safeGet( $key, $default );
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
