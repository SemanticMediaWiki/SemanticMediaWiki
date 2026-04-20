<?php

namespace SMW\MediaWiki\Connection;

use RuntimeException;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\TransactionProfiler;
use Wikimedia\ScopedCallback;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TransactionHandler {

	private ?string $sectionTransaction = null;

	private ?bool $mutedTransactionProfiler = null;

	private ?TransactionProfiler $transactionProfiler = null;

	/**
	 * @since 3.1
	 */
	public function __construct( private readonly ILBFactory $loadBalancerFactory ) {
	}

	/**
	 * @since 3.1
	 */
	public function setTransactionProfiler( TransactionProfiler $transactionProfiler ): void {
		$this->transactionProfiler = $transactionProfiler;
	}

	/**
	 * @note Only supported with 1.28+
	 *
	 * Mute the transaction profiler to avoid reports on master writes or similar
	 * operations that violates the expectation set in `wgTrxProfilerLimits` hereby
	 * avoids unnecessary log spam.
	 *
	 * @see https://gerrit.wikimedia.org/r/c/mediawiki/core/+/462130/3/includes/objectcache/SqlBagOStuff.php#836
	 *
	 * @since 3.1
	 */
	public function muteTransactionProfiler(): ?ScopedCallback {
		if ( $this->transactionProfiler === null ) {
			return null;
		}

		return $this->transactionProfiler->silenceForScope();
	}

	/**
	 * @since 3.1
	 */
	public function inSectionTransaction( string $fname = __METHOD__ ): bool {
		return $this->sectionTransaction === $fname;
	}

	/**
	 * @since 3.1
	 */
	public function hasActiveSectionTransaction(): bool {
		return $this->sectionTransaction !== null;
	}

	/**
	 * Register a `section` as transaction
	 *
	 * The intent is to make it possible to mark a section and disable any other
	 * atomic transaction request while being part of a section hereby allowing
	 * to bundle all requests and encapsulate them into one coherent atomic
	 * transaction without changing pending callers that may require individual
	 * atomic transactions when they are not part of a section request.
	 *
	 * Only one active a section transaction is allowed at a time otherwise an
	 * `Exception` is thrown.
	 *
	 * @since 3.1
	 *
	 * @throws RuntimeException
	 */
	public function markSectionTransaction( string $fname = __METHOD__ ): void {
		if ( $this->sectionTransaction !== null ) {
			throw new RuntimeException(
				"Trying to begin a new section transaction while {$this->sectionTransaction} is still active!"
			);
		}

		$this->sectionTransaction = $fname;
	}

	/**
	 * @since 3.1
	 */
	public function detachSectionTransaction( string $fname = __METHOD__ ): void {
		if ( $this->sectionTransaction !== $fname ) {
			throw new RuntimeException(
				"Trying to end an invalid section transaction (registered: {$this->sectionTransaction}, requested: {$fname})"
			);
		}

		$this->sectionTransaction = null;
	}

	/**
	 * @note Only supported with 1.28+
	 *
	 * @since 3.1
	 */
	public function getEmptyTransactionTicket( string $fname = __METHOD__ ): ?int {
		$ticket = null;

		// @see LBFactory::getEmptyTransactionTicket
		// We don't try very hard at this point and will continue without a ticket
		// if the check fails and hereby avoid a "... does not have outer scope" error

		if ( !$this->primaryDbHasChanges() ) {
			$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( $fname );
		}

		return $ticket;
	}

	/**
	 * Convenience method for safely running commitMasterChanges/waitForReplication
	 * where it will allow to commit and wait for when a TransactionTicket is
	 * available.
	 *
	 * @note Only supported with 1.28+
	 *
	 * @since 3.1
	 */
	public function commitAndWaitForReplication( string $fname, mixed $ticket, array $opts = [] ) {
		if ( !is_int( $ticket ) ) {
			return;
		}

		return $this->loadBalancerFactory->commitAndWaitForReplication( $fname, $ticket, $opts );
	}

	private function primaryDbHasChanges(): bool {
		return $this->loadBalancerFactory->hasPrimaryChanges();
	}
}
