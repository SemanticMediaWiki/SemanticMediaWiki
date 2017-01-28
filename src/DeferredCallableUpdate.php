<?php

namespace SMW;

use Closure;
use DeferrableUpdate;
use DeferredUpdates;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use SMW\MediaWiki\Database;

/**
 * @see MWCallableUpdate
 *
 * @license GNU GPL v2+
 * @since 2.4
 */
class DeferredCallableUpdate implements DeferrableUpdate, LoggerAwareInterface {

	/**
	 * @var Closure|callable
	 */
	private $callback;

	/**
	 * @var Database|null
	 */
	private $connection;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var boolean
	 */
	private $enabledDeferredUpdate = true;

	/**
	 * @var boolean
	 */
	private $isPending = false;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var array
	 */
	private static $pendingUpdates = array();

	/**
	 * @var string|null
	 */
	private $fingerprint = null;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var array
	 */
	private static $queueList = array();

	/**
	 * @since 2.4
	 *
	 * @param Closure $callback
	 * @param Database|null $connection
	 *
	 * @throws RuntimeException
	 */
	public function __construct( Closure $callback, Database $connection = null ) {

		if ( !is_callable( $callback ) ) {
			throw new RuntimeException( 'Expected a valid callback/closure!' );
		}

		$this->callback = $callback;
		$this->connection = $connection;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
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
	 * @note Unit/Integration tests in MW 1.26- showed ambiguous behaviour when
	 * run in deferred mode because not all MW operations were supporting late
	 * execution.
	 *
	 * @since 2.4
	 */
	public function enabledDeferredUpdate( $enabledDeferredUpdate = true ) {
		$this->enabledDeferredUpdate = (bool)$enabledDeferredUpdate;
	}

	/**
	 * @note If wgCommandLineMode = true (e.g. MW is in CLI mode) then
	 * DeferredUpdates::addUpdate pushes updates directly into execution mode
	 * which may not be desirable for all update processes therefore hold on to it
	 * by using an internal waitableUpdate list and release them at convenience.
	 *
	 * @since 2.4
	 *
	 * @param booloan $isPending
	 */
	public function markAsPending( $isPending = false ) {
		$this->isPending = (bool)$isPending;
	}

	/**
	 * @note MW 1.29+ showed transaction collisions (Exception thrown with
	 * an uncommited database transaction), use 'onTransactionIdle' to isolate
	 * the update execution.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {

		if ( $this->connection === null ) {
			$this->log( __METHOD__ . ' is missing an active connection therefore `onTransactionIdle` cannot be used.' );
			return $this->onTransactionIdle = false;
		}

		$this->onTransactionIdle = true;
	}

	/**
	 * @note Set a fingerprint allowing it to track and detect duplicate update
	 * requests while being unprocessed.
	 *
	 * @since 2.5
	 *
	 * @param string|null $queue
	 */
	public function setFingerprint( $fingerprint = null ) {
		$this->fingerprint = md5( $fingerprint );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @see DeferrableCallback::getOrigin
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getOrigin() {
		return $this->origin;
	}

	/**
	 * @since 2.4
	 */
	public static function releasePendingUpdates() {
		foreach ( self::$pendingUpdates as $update ) {
			DeferredUpdates::addUpdate( $update );
		}

		self::$pendingUpdates = array();
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 2.4
	 */
	public function doUpdate() {

		$this->log( $this->origin . ' doUpdate' . ( $this->fingerprint ? ' (' . $this->fingerprint . ')' : '' ) );

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function() {
				$this->log( $this->origin . ' doUpdate (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doUpdate();
			} );
		}

		call_user_func( $this->callback );
		unset( self::$queueList[$this->fingerprint] );
	}

	/**
	 * @since 2.5
	 */
	public function pushUpdate() {

		if ( $this->fingerprint !== null && isset( self::$queueList[$this->fingerprint] ) ) {
			$this->log( $this->origin . ' (fingerprint: ' . $this->fingerprint .' is already listed therefore skip)' );
			return;
		}

		self::$queueList[$this->fingerprint] = true;

		if ( $this->isPending && $this->enabledDeferredUpdate ) {
			$this->log( $this->origin . ' (as pending DeferredCallableUpdate)' );
			return self::$pendingUpdates[] = $this;
		}

		if ( !$this->isCommandLineMode && $this->enabledDeferredUpdate ) {
			$this->log( $this->origin . ' (as DeferredCallableUpdate' . ( $this->fingerprint ? ' ' . $this->fingerprint : '' ) . ')' );
			return DeferredUpdates::addUpdate( $this );
		}

		$this->doUpdate();
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
