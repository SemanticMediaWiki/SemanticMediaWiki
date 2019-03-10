<?php

namespace SMW\MediaWiki\Deferred;

use Closure;
use SMW\MediaWiki\Database;
use SMW\Site;

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
class TransactionalCallableUpdate extends CallableUpdate {

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
	private $preCommitableCallbacks = [];

	/**
	 * @var array
	 */
	private $postCommitableCallbacks = [];

	/**
	 * @var boolean
	 */
	private $autoCommit = false;

	/**
	 * @since 3.1
	 *
	 * @param callable $callback
	 * @param Database $instance
	 */
	public static function newUpdate( callable $callback, Database $connection ) {

		$transactionalCallableUpdate = new self( $callback, $connection );

		$transactionalCallableUpdate->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $transactionalCallableUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param callable $callback|null
	 * @param Database|null $connection
	 */
	public function __construct( callable $callback = null, Database $connection ) {
		parent::__construct( $callback );
		$this->connection = $connection;
		$this->connection->onTransactionResolution( [ $this, 'cancelOnRollback' ], __METHOD__ );
	}

	/**
	 * @note MW 1.29+ showed transaction collisions (Exception thrown with
	 * an uncommitted database transaction), use 'onTransactionIdle' to isolate
	 * the update execution.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = !$this->isCommandLineMode;
	}

	/**
	 * @since 3.0
	 */
	public function runAsAutoCommit() {
		$this->autoCommit = true;
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
	public function addPreCommitableCallback( $fname, callable $callback ) {
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
	public function addPostCommitableCallback( $fname, callable $callback ) {
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
			return $this->runOnTransactionIdle();
		}

		$this->runPreCommitCallbacks();

		$e = null;
		$autoTrx = null;

		if ( $this->autoCommit ) {
			$this->logger->info( [ 'DeferrableUpdate', 'Transactional, as auto commit', 'Update' ] );
			$autoTrx = $this->connection->getFlag( DBO_TRX );
			$this->connection->clearFlag( DBO_TRX );
		}

		try {
			parent::doUpdate();
		} catch ( \Exception $e ) {
		}

		if ( $this->autoCommit && $autoTrx ) {
			$this->connection->setFlag( DBO_TRX );
		}

		if ( $e ) {
			throw $e;
		}

		$this->runPostCommitCallbacks();

		if ( $this->transactionTicket !== null ) {
			$this->connection->commitAndWaitForReplication( $this->getOrigin(), $this->transactionTicket );
		}
	}

	/**
	 * @since 3.0
	 */
	public function cancelOnRollback( $trigger ) {
		if ( $trigger === Database::TRIGGER_ROLLBACK ) {
			$this->callback = [ $this, 'emptyCancelCallback' ];
		}
	}

	protected function registerUpdate( $update ) {

		if ( $this->onTransactionIdle ) {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Added: {origin} (onTransactionIdle)' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin() ]
			);

			return $this->connection->onTransactionIdle( function() use( $update ) {
				$update->onTransactionIdle = false;
				parent::registerUpdate( $update );
			} );
		}

		parent::registerUpdate( $update );
	}

	protected function loggableContext() {
		return parent::loggableContext() + [
			'transactionTicket' => $this->transactionTicket
		];
	}

	private function runOnTransactionIdle() {
		$this->connection->onTransactionIdle( function() {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Update: {origin} (onTransactionIdle)' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin() ]
			);
			$this->onTransactionIdle = false;
			$this->doUpdate();
		} );
	}

	private function runPreCommitCallbacks() {
		foreach ( $this->preCommitableCallbacks as $fname => $preCallback ) {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Update: {origin} (pre-commitable callback: {fname})' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin(), 'fname' => $fname ]
			);

			call_user_func( $preCallback, $this->transactionTicket );
		}
	}

	private function runPostCommitCallbacks() {
		foreach ( $this->postCommitableCallbacks as $fname => $postCallback ) {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Update: {origin} (post-commitable callback: {fname})' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin(), 'fname' => $fname ]
			);

			call_user_func( $postCallback, $this->transactionTicket );
		}
	}

	protected function emptyCancelCallback() {
		$this->logger->info(
			[ 'DeferrableUpdate', 'cancelOnRollback' ],
			[ 'role' => 'developer', 'method' => __METHOD__ ]
		);
	}

}
