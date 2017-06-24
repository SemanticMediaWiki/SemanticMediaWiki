<?php

namespace SMW\Updater;

use Closure;
use SMW\MediaWiki\Database;

/**
 * Extends DeferredCallableUpdate to allow handling of transaction related tasks
 * or isolations to ensure an undisturbed update process before and after
 * MediaWiki::preOutputCommit.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TransactionalDeferredCallableUpdate extends DeferredCallableUpdate {

	/**
	 * @var Database|null
	 */
	private $connection;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @var int|null
	 */
	private $transactionTicket = null;

	/**
	 * @var array
	 */
	private $preCommitableCallbacks = array();

	/**
	 * @var array
	 */
	private $postCommitableCallbacks = array();

	/**
	 * @since 3.0
	 *
	 * @param Closure $callback|null
	 * @param Database|null $connection
	 */
	public function __construct( Closure $callback = null, Database $connection ) {
		parent::__construct( $callback );
		$this->connection = $connection;
	}

	/**
	 * @note MW 1.29+ showed transaction collisions (Exception thrown with
	 * an uncommited database transaction), use 'onTransactionIdle' to isolate
	 * the update execution.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = !$this->isCommandLineMode;
	}

 	/**
	 * It tries to fetch a transactionTicket to assert whether transaction writes
	 * are active or not and if available will process Database::commitAndWaitForReplication
	 * during DeferredCallableUpdate::doUpdate to safely post commits to the
	 * master.
	 *
	 * @note If the commandLine is active then continue an update without a ticket
	 * to avoid any update lag or possible transaction lock.
	 *
	 * @since 3.0
	 */
	public function commitWithTransactionTicket() {
		if ( $this->isCommandLineMode === false && $this->isDeferrableUpdate === true ) {
			$this->transactionTicket = $this->connection->getEmptyTransactionTicket( $this->getOrigin() );
		}
	}

	/**
	 * Attaches a callback pre-execution of the source callback and is scheduled
	 * to be executed before the source callback.
	 *
	 * @since 3.0
	 *
	 * @param string $fname
	 * @param Closure $callback
	 */
	public function addPreCommitableCallback( $fname, $callback ) {
		if ( is_callable( $callback ) ) {
			$this->preCommitableCallbacks[$fname] = $callback;
		}
	}

	/**
	 * Attaches a callback post execution of the source callback and is scheduled
	 * to be executed after the source callback.
	 *
	 * @since 3.0
	 *
	 * @param string $fname
	 * @param Closure $callback
	 */
	public function addPostCommitableCallback( $fname, $callback ) {
		if ( is_callable( $callback ) ) {
			$this->postCommitableCallbacks[$fname] = $callback;
		}
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 3.0
	 */
	public function doUpdate() {

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function() {
				$this->log( $this->getOrigin() . ' doUpdate (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->doUpdate();
			} );
		}

		foreach ( $this->preCommitableCallbacks as $fname => $preCallback ) {
			$this->log( $this->getOrigin() . " (pre-commitable callback: $fname)" );
			call_user_func( $preCallback, $this->transactionTicket );
		}

		parent::doUpdate();

		foreach ( $this->postCommitableCallbacks as $fname => $postCallback ) {
			$this->log( $this->getOrigin() . " (post-commitable callback: $fname)" );
			call_user_func( $postCallback, $this->transactionTicket );
		}

		$this->connection->commitAndWaitForReplication( $this->getOrigin(), $this->transactionTicket );
	}

	protected function addUpdate( $update ) {

		if ( $this->onTransactionIdle ) {
			$this->log( $this->getOrigin() . ' (as waitable DeferrableUpdate)' );
			return $this->connection->onTransactionIdle( function() use( $update ) {
				$update->onTransactionIdle = false;
				parent::addUpdate( $update );
			} );
		}

		parent::addUpdate( $update );
	}

	protected function getLoggableContext() {
		return parent::getLoggableContext() + array(
			'transactionTicket' => $this->transactionTicket
		);
	}

}
