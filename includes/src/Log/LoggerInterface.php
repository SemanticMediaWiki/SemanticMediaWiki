<?php

namespace SMW\Log;

/**
 * @see Psr\Log\LoggerInterface
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 */
interface LoggerInterface {

	/**
	 * @see Psr\Log\LoggerInterface::emergency
	 *
	 * @since  1.9.0.3
	 */
	public function emergency( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::alert
	 *
	 * @since  1.9.0.3
	 */
	public function alert( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::critical
	 *
	 * @since  1.9.0.3
	 */
	public function critical( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::error
	 *
	 * @since  1.9.0.3
	 */
	public function error( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::warning
	 *
	 * @since  1.9.0.3
	 */
	public function warning( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::notice
	 *
	 * @since  1.9.0.3
	 */
	public function notice( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::info
	 *
	 * @since  1.9.0.3
	 */
	public function info( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::debug
	 *
	 * @since  1.9.0.3
	 */
	public function debug( $message, array $context = array() );

	/**
	 * @see Psr\Log\LoggerInterface::log
	 *
	 * @since  1.9.0.3
	 */
	public function log( $level, $message, array $context = array() );

}
