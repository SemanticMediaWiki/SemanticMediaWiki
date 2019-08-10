<?php

namespace SMW\MediaWiki\Connection;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TransactionHandler {

	/**
	 * @var LBFactory
	 */
	private $loadBalancerFactory;

	/**
	 * @var string|null
	 */
	private $sectionTransaction;

	/**
	 * @var boolean|null
	 */
	private $mutedTransactionProfiler;

	/**
	 * @since 3.1
	 *
	 * @param ILBFactory|LBFactory $loadBalancerFactory
	 */
	public function __construct( $loadBalancerFactory ) {

		if (
			!$loadBalancerFactory instanceof \LBFactory &&
			!$loadBalancerFactory instanceof \Wikimedia\Rdbms\ILBFactory ) {
			throw new RuntimeException( "Expected a LBFactory instance!" );
		}

		$this->loadBalancerFactory = $loadBalancerFactory;
	}

	/**
	 * @since 3.1
	 *
	 * @param TransactionProfiler $transactionProfiler
	 */
	public function setTransactionProfiler( $transactionProfiler ) {

		// MW 1.28+
		if ( method_exists( $transactionProfiler, 'setSilenced' ) ) {
			$this->transactionProfiler = $transactionProfiler;
		}
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
	public function muteTransactionProfiler( $mute ) {

		if ( $this->transactionProfiler === null ) {
			return;
		}

		if ( $this->mutedTransactionProfiler === null && $mute !== false ) {
			$this->mutedTransactionProfiler = $this->transactionProfiler->setSilenced( $mute );
		} elseif ( $this->mutedTransactionProfiler !== null && $mute === false ) {
			$this->transactionProfiler->setSilenced( $this->mutedTransactionProfiler );
			$this->mutedTransactionProfiler = null;
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param  string $fname
	 * @return boolean
	 */
	public function inSectionTransaction( $fname = __METHOD__ ) {
		return $this->sectionTransaction === $fname;
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function hasActiveSectionTransaction() {
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
	 * @param string $fname
	 * @throws RuntimeException
	 */
	public function markSectionTransaction( $fname = __METHOD__ ) {

		if ( $this->sectionTransaction !== null ) {
			throw new RuntimeException(
				"Trying to begin a new section transaction while {$this->sectionTransaction} is still active!"
			);
		}

		$this->sectionTransaction = $fname;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $fname
	 */
	public function detachSectionTransaction( $fname = __METHOD__ ) {

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
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 *
	 * @return mixed A value to pass to commitAndWaitForReplication
	 */
	public function getEmptyTransactionTicket( $fname = __METHOD__ ) {

		$ticket = null;

		if ( !method_exists( $this->loadBalancerFactory, 'getEmptyTransactionTicket' ) ) {
			return $ticket;
		}

		// @see LBFactory::getEmptyTransactionTicket
		// We don't try very hard at this point and will continue without a ticket
		// if the check fails and hereby avoid a "... does not have outer scope" error
		if ( !$this->loadBalancerFactory->hasMasterChanges() ) {
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
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 * @param mixed $ticket Result of Database::getEmptyTransactionTicket
	 * @param array $opts Options to waitForReplication
	 */
	public function commitAndWaitForReplication( $fname, $ticket, array $opts = [] ) {

		if ( !is_int( $ticket ) || !method_exists( $this->loadBalancerFactory, 'commitAndWaitForReplication' ) ) {
			return;
		}

		return $this->loadBalancerFactory->commitAndWaitForReplication( $fname, $ticket, $opts );
	}

}
