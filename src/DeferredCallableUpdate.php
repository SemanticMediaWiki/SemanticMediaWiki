<?php

namespace SMW;

use Closure;
use DeferrableUpdate;
use DeferredUpdates;
use RuntimeException;

/**
 * @see MWCallableUpdate
 *
 * @license GNU GPL v2+
 * @since 2.4
 */
class DeferredCallableUpdate implements DeferrableUpdate {

	/**
	 * @var Closure|callable
	 */
	private $callback;

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
	 * @since 2.4
	 *
	 * @param Closure $callback
	 * @throws RuntimeException
	 */
	public function __construct( Closure $callback ) {

		if ( !is_callable( $callback ) ) {
			throw new RuntimeException( 'Expected a valid callback/closure!' );
		}

		$this->callback = $callback;
	}

	/**
	 * @note Unit/Integration tests in MW 1.26- showed ambiguous behaviour when
	 * run in deferred mode because not all MW operations were supporting late
	 * execution.
	 *
	 * @since 2.4
	 */
	public function enabledDeferredUpdate( $enabledDeferredUpdate = true ) {
		$this->enabledDeferredUpdate = $enabledDeferredUpdate;
	}

	/**
	 * @note If wgCommandLineMode = true (e.g. MW is in CLI mode) then
	 * DeferredUpdates::addUpdate pushes updates directly into execution mode
	 * which may not be desirable for all update processes therefore hold on to it
	 * by using an internal waitableUpdate list and release them at convenience.
	 *
	 * @since 2.4
	 */
	public function markAsPending( $isPending = false ) {
		$this->isPending = $isPending;
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
		wfDebugLog( 'smw', $this->origin . ' doUpdate' );
		call_user_func( $this->callback );
	}

	/**
	 * @since 2.4
	 * @deprecated since 2.5, use DeferredCallableUpdate::pushUpdate
	 */
	public function pushToDeferredUpdateList() {
		$this->pushUpdate();
	}

	/**
	 * @since 2.5
	 */
	public function pushUpdate() {

		if ( $this->isPending && $this->enabledDeferredUpdate ) {
			wfDebugLog( 'smw', $this->origin . ' (as pending DeferredCallableUpdate)' );
			return self::$pendingUpdates[] = $this;
		}

		if ( $this->enabledDeferredUpdate ) {
			wfDebugLog( 'smw', $this->origin . ' (as DeferredCallableUpdate)' );
			return DeferredUpdates::addUpdate( $this );
		}

		$this->doUpdate();
	}

}
