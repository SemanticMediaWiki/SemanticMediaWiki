<?php

namespace SMW\MediaWiki;

use Title;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use SMW\Utils\Timer;
use DeferrableUpdate;
use DeferredUpdates;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageUpdater implements LoggerAwareInterface, DeferrableUpdate {

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
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean
	 */
	private $isHtmlCacheUpdate = true;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @var boolean
	 */
	private $asPoolPurge = false;

	/**
	 * @var boolean
	 */
	private $isPending = false;

	/**
	 * @var array
	 */
	private $pendingUpdates = array();

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
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
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
	 * @since 3.0
	 *
	 * @param boolean $isHtmlCacheUpdate
	 */
	public function isHtmlCacheUpdate( $isHtmlCacheUpdate ) {
		$this->isHtmlCacheUpdate = $isHtmlCacheUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param booloan $isPending
	 */
	public function markAsPending() {
		$this->isPending = true;
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

		$this->titles[$title->getDBKey()] = $title;
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
	 * Controls the purge to use a direct DB access to make changes to avoid
	 * racing conditions for a large number of title entities.
	 *
	 * @since 3.0
	 */
	public function asPoolPurge() {

		if ( $this->connection === null ) {
			return;
		}

		$this->onTransactionIdle = true;
		$this->asPoolPurge = true;
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
	 * Push updates to be either deferred or direct pending the setting invoked
	 * by PageUPdater::markAsPending.
	 *
	 * @since 3.0
	 */
	public function pushUpdate() {

		if ( !$this->isPending || $this->isCommandLineMode === true ) {
			return $this->doUpdate();
		}

		$this->log( __METHOD__ . " $this->origin (as DeferrableUpdate)" );

		if ( $this->onTransactionIdle ) {
			$this->connection->onTransactionIdle( function () {
				DeferredUpdates::addUpdate( $this );
			} );
		} else{
			DeferredUpdates::addUpdate( $this );
		}
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 3.0
	 */
	public function doUpdate() {
		$this->isPending = false;

		foreach ( array_keys( $this->pendingUpdates ) as $update ) {
			call_user_func( [ $this, $update ] );
		}

		$this->pendingUpdates = array();
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeParserCache() {

		$method = __METHOD__;

		if ( $this->isPending ) {
			return $this->pendingUpdates['doPurgeParserCache'] = true;
		}

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function () use( $method ) {
				$this->log( $method . ' (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doPurgeParserCache();
				$this->onTransactionIdle = true;
			} );
		}

		if ( $this->asPoolPurge ) {
			return $this->doPoolPurge();
		}

		foreach ( $this->titles as $title ) {
			$title->invalidateCache();
		}
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeHtmlCache() {

		if ( $this->isHtmlCacheUpdate === false ) {
			return;
		}

		if ( $this->isPending ) {
			return $this->pendingUpdates['doPurgeHtmlCache'] = true;
		}

		$method = __METHOD__;

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function () use ( $method ) {
				$this->log( $method . ' (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doPurgeHtmlCache();
				$this->onTransactionIdle = true;
			} );
		}

		// Calls HTMLCacheUpdate, HTMLCacheUpdateJob including HTMLFileCache,
		// CdnCacheUpdate
		foreach ( $this->titles as $title ) {
			$title->touchLinks();
		}
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeWebCache() {

		$method = __METHOD__;

		if ( $this->isPending ) {
			return $this->pendingUpdates['doPurgeWebCache'] = true;
		}

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

	/**
	 * Copied from PurgeJobUtils to avoid the AutoCommitUpdate from
	 * Title::invalidateCache introduced with MW 1.28/1.29 on a large update pool
	 */
	private function doPoolPurge() {

		Timer::start( __METHOD__ );

		// Required due to postgres and "Error: 22007 ERROR:  invalid input
		// syntax for type timestamp with time zone: "20170408113703""
		$now = $this->connection->timestamp();
		$res = $this->connection->select(
			'page',
			'page_id',
			[
				'page_title' => array_keys( $this->titles ),
				'page_touched < ' . $this->connection->addQuotes( $now )
			],
			__METHOD__
		);

		if ( $res === false ) {
			return;
		}

		$ids = [];

		foreach ( $res as $row ) {
			$ids[] = $row->page_id;
		}

		if ( $ids === array() ) {
			return;
		}

		$this->connection->update(
			'page',
			[ 'page_touched' => $now ],
			[
				'page_id' => $ids,
				'page_touched < ' . $this->connection->addQuotes( $now )
			],
			__METHOD__
		);

		$this->log( __METHOD__ . ' (procTime in sec: ' . Timer::getElapsedTime( __METHOD__, 7 ) . ')' );
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
