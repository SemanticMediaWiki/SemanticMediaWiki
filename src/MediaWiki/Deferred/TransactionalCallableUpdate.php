<?php

namespace SMW\MediaWiki\Deferred;

use Exception;
use SMW\MediaWiki\Connection\Database;
use SMW\Site;

/**
 * Extends DeferredCallableUpdate to allow handling of transaction related tasks
 * or isolations to ensure an undisturbed update process before and after
 * MediaWiki::preOutputCommit.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TransactionalCallableUpdate extends CallableUpdate {

	private bool $onTransactionIdle = false;

	private ?int $transactionTicket = null;

	private array $preCommitableCallbacks = [];

	private array $postCommitableCallbacks = [];

	private bool $autoCommit = false;

	/**
	 * @since 3.1
	 */
	public static function newUpdate( callable $callback, Database $connection ): self {
		$transactionalCallableUpdate = new self( $callback, $connection );

		$transactionalCallableUpdate->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $transactionalCallableUpdate;
	}

	/**
	 * @since 3.0
	 */
	public function __construct(
		?callable $callback = null,
		private readonly ?Database $connection = null,
	) {
		parent::__construct( $callback );
		$this->connection->onTransactionResolution( [ $this, 'cancelOnRollback' ], __METHOD__ );
	}

	/**
	 * @note MW 1.29+ showed transaction collisions (Exception thrown with
	 * an uncommitted database transaction), use 'onTransactionIdle' to isolate
	 * the update execution.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle(): void {
		$this->onTransactionIdle = !$this->isCommandLineMode;
	}

	/**
	 * @since 3.0
	 */
	public function runAsAutoCommit(): void {
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
	public function commitWithTransactionTicket(): void {
		if ( !$this->isCommandLineMode && $this->isDeferrableUpdate ) {
			$this->transactionTicket = $this->connection->getEmptyTransactionTicket( $this->getOrigin() );
		}
	}

	/**
	 * Attaches a callback pre-execution of the source callback and is scheduled
	 * to be executed before the source callback.
	 *
	 * @since 3.0
	 */
	public function addPreCommitableCallback( string $fname, callable $callback ): void {
		$this->preCommitableCallbacks[$fname] = $callback;
	}

	/**
	 * Attaches a callback post execution of the source callback and is scheduled
	 * to be executed after the source callback.
	 *
	 * @since 3.0
	 */
	public function addPostCommitableCallback( string $fname, callable $callback ): void {
		$this->postCommitableCallbacks[$fname] = $callback;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 3.0
	 * @throws Exception
	 */
	public function doUpdate(): void {
		if ( $this->onTransactionIdle ) {
			$this->runOnTransactionIdle();
			return;
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
		} catch ( Exception ) {
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
	public function cancelOnRollback( $trigger ): void {
		if ( $trigger === Database::TRIGGER_ROLLBACK ) {
			$this->callback = [ $this, 'emptyCancelCallback' ];
		}
	}

	protected function registerUpdate( $update ): void {
		if ( $this->onTransactionIdle ) {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Added: {origin} (onTransactionIdle)' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin() ]
			);

			$this->connection->onTransactionCommitOrIdle( function () use( $update ): void {
				$update->onTransactionIdle = false;
				parent::registerUpdate( $update );
			} );
			return;
		}

		parent::registerUpdate( $update );
	}

	protected function loggableContext(): array {
		return parent::loggableContext() + [
			'transactionTicket' => $this->transactionTicket
		];
	}

	private function runOnTransactionIdle(): void {
		$fname = __METHOD__;
		$this->connection->onTransactionCommitOrIdle( function () use ( $fname ): void {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Update: {origin} (onTransactionIdle)' ],
				[ 'method' => $fname, 'role' => 'developer', 'origin' => $this->getOrigin() ]
			);
			$this->onTransactionIdle = false;
			$this->doUpdate();
		} );
	}

	private function runPreCommitCallbacks(): void {
		foreach ( $this->preCommitableCallbacks as $fname => $preCallback ) {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Update: {origin} (pre-commitable callback: {fname})' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin(), 'fname' => $fname ]
			);

			call_user_func( $preCallback, $this->transactionTicket );
		}
	}

	private function runPostCommitCallbacks(): void {
		foreach ( $this->postCommitableCallbacks as $fname => $postCallback ) {
			$this->logger->info(
				[ 'DeferrableUpdate', 'Transactional', 'Update: {origin} (post-commitable callback: {fname})' ],
				[ 'method' => __METHOD__, 'role' => 'developer', 'origin' => $this->getOrigin(), 'fname' => $fname ]
			);

			call_user_func( $postCallback, $this->transactionTicket );
		}
	}

	protected function emptyCancelCallback(): void {
		$this->logger->info(
			[ 'DeferrableUpdate', 'cancelOnRollback' ],
			[ 'role' => 'developer', 'method' => __METHOD__ ]
		);
	}

}
