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
	public function log( $level, $message, array $context = [] ) {

		$shouldLog = false;

		// Everthings goes for the developer role!
		if ( $this->role === self::ROLE_DEVELOPER ) {
			$shouldLog = true;
		} elseif ( isset( $context['role'] ) && $context['role'] === $this->role ) {
			$shouldLog = true;
		} elseif ( isset( $context['role'] ) && $context['role'] === self::ROLE_PRODUCTION && $this->role === self::ROLE_USER ) {
			$shouldLog = true;
		}

		if ( !$shouldLog ) {
			return;
		}

		// For convenience
		if ( isset( $context['procTime'] ) ) {
			$context['procTime'] = round( $context['procTime'], 5 );
		}

		if ( isset( $context['time'] ) ) {
			$context['time'] = round( $context['time'], 5 );
		}

		if ( is_array( $message ) ) {
			$message = array_shift( $message ) . ': ' . json_encode( $message );
		}

		foreach ( $context as $key => $value ) {
			if ( is_array( $value ) ) {
				$context[$key] = json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}

		$this->logger->log( $level, $message, $context );
	}

}
