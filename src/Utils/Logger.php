<?php

namespace SMW\Utils;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Logger extends AbstractLogger {

	const ROLE_DEVELOPER = 'developer';
	const ROLE_USER = 'user';
	const ROLE_PRODUCTION = 'production';

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var string
	 */
	protected $role;

	/**
	 * @since 3.0
	 *
	 * @param LoggerInterface $logger
	 * @param string $role
	 */
	public function __construct( LoggerInterface $logger, $role = self::ROLE_DEVELOPER ) {
		$this->logger = $logger;
		$this->role = $role;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function log( $level, $message, array $context = array() ) {

		$canLog = false;

		// Everthings goes for the developer role!
		if ( $this->role === self::ROLE_DEVELOPER ) {
			$canLog = true;
		} elseif ( isset( $context['role'] ) && $context['role'] === $this->role ) {
			$canLog = true;
		} elseif ( isset( $context['role'] ) && $context['role'] === self::ROLE_PRODUCTION && $this->role === self::ROLE_USER ) {
			$canLog = true;
		}

		if ( $canLog ) {
			$this->logger->log( $level, $message, $context );
		}
	}

}
