<?php

namespace SMW\MediaWiki\Hooks;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
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
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {

		if ( $this->options === null ) {
			$this->setOptions( [] );
		}

		$this->options->set( $key, $value );
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

		if ( $this->options === null ) {
			$this->setOptions( [] );
		}

		return $this->options->safeGet( $key, $default );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $flag
	 *
	 * @return boolean
	 */
	public function isFlagSet( $key, $flag ) {
		return $this->options->isFlagSet( $key, $flag );
	}

	protected function log( $message, $context = [] ) {
		if ( $this->logger instanceof LoggerInterface ) {
			$this->logger->info( $message, $context );
		}
	}

}
