<?php

namespace SMW\MediaWiki\Hooks;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use SMW\Options;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HookHandler {

	use LoggerAwareTrait;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @since 2.5
	 */
	public function __construct() {
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

	protected function log( $message, $context = array() ) {
		if ( $this->logger instanceof LoggerInterface ) {
			$this->logger->info( $message, $context );
		}
	}

}
