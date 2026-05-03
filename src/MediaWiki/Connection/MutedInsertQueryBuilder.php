<?php

namespace SMW\MediaWiki\Connection;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\InsertQueryBuilder;
use Wikimedia\ScopedCallback;

/**
 * `InsertQueryBuilder` whose `execute()` is wrapped with the SMW
 * `TransactionHandler::muteTransactionProfiler()` scope. Preserves the
 * existing behaviour of suppressing transaction-profiler warnings on
 * internal SMW writes that would otherwise violate `wgTrxProfilerLimits`.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MutedInsertQueryBuilder extends InsertQueryBuilder {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		IDatabase $db,
		private readonly TransactionHandler $transactionHandler,
	) {
		parent::__construct( $db );
	}

	public function execute(): void {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		try {
			parent::execute();
		} finally {
			ScopedCallback::consume( $scope );
		}
	}
}
