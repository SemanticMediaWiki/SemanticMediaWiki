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
abstract class BaseLogger implements LoggerInterface {

	/**
	 * Psr/Log/LogLevel provides level definitions but the SMW interface
	 * doesn't need them therefore null is returned
	 */
	protected $level = null;

	/**
	 * {@inheritdoc}
	 */
	public function emergency( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function error( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function info( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug( $message, array $context = array() ) {
		return $this->log( $this->level, $message, $context );
	}

}