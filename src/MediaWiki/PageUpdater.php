<?php

namespace SMW\MediaWiki;

use Title;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageUpdater implements LoggerAwareInterface {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var Title[]
	 */
	private $titles = array();

	/**
	 * LoggerInterface
	 */
	private $logger;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @since 2.5
	 *
	 * @param Database|null $connection
	 */
	public function __construct( Database $connection = null ) {
		$this->connection = $connection;
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

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode or not.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 2.1
	 *
	 * @param Title|null $title
	 */
	public function addPage( Title $title = null ) {

		if ( $title === null ) {
			return;
		}

		$this->titles[$title->getPrefixedDBKey()] = $title;
	}

	/**
	 * @note MW 1.29+ runs Title::invalidateCache in AutoCommitUpdate which has
	 * been shown to cause transaction issues when executed while a transaction
	 * hasn't finished therefore use 'onTransactionIdle' to isolate the
	 * execution.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {

		if ( $this->connection === null ) {
			$this->log( __METHOD__ . ' is missing an active connection therefore `onTransactionIdle` cannot be used.' );
			return $this->onTransactionIdle = false;
		}

		$this->onTransactionIdle = !$this->isCommandLineMode;
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->titles = array();
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canUpdate() {
		return !wfReadOnly();
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeParserCache() {

		$method = __METHOD__;

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function () use( $method ) {
				$this->log( $method . ' (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doPurgeParserCache();
				$this->onTransactionIdle = true;
			} );
		}

		foreach ( $this->titles as $title ) {
			$title->invalidateCache();
		}
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeHtmlCache() {

		$method = __METHOD__;

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function () use ( $method ) {
				$this->log( $method . ' (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doPurgeHtmlCache();
				$this->onTransactionIdle = true;
			} );
		}

		foreach ( $this->titles as $title ) {
			$title->touchLinks();

			// @see MW 1.19 Title::invalidateCache
			\HTMLFileCache::clearFileCache( $title );
		}
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeWebCache() {

		$method = __METHOD__;

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function () use ( $method ) {
				$this->log( $method . ' (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doPurgeWebCache();
				$this->onTransactionIdle = true;
			} );
		}

		foreach ( $this->titles as $title ) {
			$title->purgeSquid();
		}
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
