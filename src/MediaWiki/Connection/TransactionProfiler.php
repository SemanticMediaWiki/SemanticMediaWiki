<?php

namespace SMW\MediaWiki\Connection;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TransactionProfiler {

	/**
	 * @var TransactionProfiler
	 */
	private $transactionProfiler;

	/**
	 * @var boolean
	 */
	private $silenceTransactionProfiler = false;

	/**
	 * @since 3.0
	 *
	 * @param TransactionProfiler|null $transactionProfiler
	 */
	public function __construct( $transactionProfiler = null ) {

		// MW 1.28+
		if ( method_exists( $transactionProfiler, 'setSilenced' ) ) {
			$this->transactionProfiler = $transactionProfiler;
		}
	}

	/**
	 * @since 3.0
	 */
	public function silenceTransactionProfiler() {
		$this->silenceTransactionProfiler = true;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $state
	 */
	public function setSilenced( $state ) {

		if ( $this->transactionProfiler === null || $this->silenceTransactionProfiler === false ) {
			return;
		}

		// @see https://gerrit.wikimedia.org/r/c/mediawiki/core/+/462130/3/includes/objectcache/SqlBagOStuff.php#836
		return $this->transactionProfiler->setSilenced( $state );
	}

}
