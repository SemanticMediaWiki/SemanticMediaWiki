<?php

namespace SMW\Elastic;

use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Indexer\Replication\ReplicationEntityExaminerDeferrableIndicatorProvider;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class Hooks {

	/**
	 * @var
	 */
	private array $handlers = [];

	/**
	 * @since 3.2
	 */
	public function __construct( private readonly ElasticFactory $elasticFactory ) {
	}

	/**
	 * @since 3.2
	 *
	 * @return
	 */
	public function getHandlers(): array {
		return [
			'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders' => [ $this, 'onRegisterEntityExaminerDeferrableIndicatorProviders' ],
			'SMW::Admin::RegisterTaskHandlers' => [ $this, 'onRegisterTaskHandlers' ],
		];
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Admin::RegisterTaskHandlers
	 * @since 3.0
	 */
	public function onRegisterTaskHandlers( TaskHandlerRegistry $taskHandlerRegistry, Store $store, $outputFormatter, $user ): bool {
		if (
			( $connection = $store->getConnection( 'elastic' ) ) === null ||
			$connection instanceof DummyClient ) {
			return true;
		}

		$taskHandler = $this->elasticFactory->newInfoTaskHandler(
			$store,
			$outputFormatter
		);

		$taskHandlerRegistry->registerTaskHandler(
			$taskHandler
		);

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders
	 * @since 3.2
	 */
	public function onRegisterEntityExaminerDeferrableIndicatorProviders( Store $store, &$indicatorProviders ): bool {
		if (
			( $connection = $store->getConnection( 'elastic' ) ) === null ||
			$connection instanceof DummyClient ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$options = $connection->getConfig();

		$replicationEntityExaminerDeferrableIndicatorProvider = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$store,
			$applicationFactory->getEntityCache(),
			$this->elasticFactory->newReplicationCheck( $store )
		);

		$replicationEntityExaminerDeferrableIndicatorProvider->canCheckReplication(
			$options->dotGet( 'indexer.monitor.entity.replication' )
		);

		$indicatorProviders[] = $replicationEntityExaminerDeferrableIndicatorProvider;

		return true;
	}

}
