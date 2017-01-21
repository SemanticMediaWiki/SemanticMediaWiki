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
	 * @var Title[]
	 */
	private $titles = array();

	/**
	 * LoggerInterface
	 */
	private $logger;

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
	* @since 2.1
	*
	* @param Title $title
	*/
	public function addPage( Title $title ) {
		$this->titles[$title->getPrefixedDBKey()] = $title;
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
		foreach ( $this->titles as $title ) {
			$title->invalidateCache();
		}
	}

	/**
	* @since 2.1
	*/
	public function doPurgeHtmlCache() {
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
