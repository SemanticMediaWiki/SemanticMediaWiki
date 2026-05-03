<?php

namespace SMW\MediaWiki\Connection;

use Wikimedia\Rdbms\DeleteQueryBuilder;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\ScopedCallback;

/**
 * `DeleteQueryBuilder` whose `execute()` is wrapped with the SMW
 * `TransactionHandler::muteTransactionProfiler()` scope.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MutedDeleteQueryBuilder extends DeleteQueryBuilder {

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
