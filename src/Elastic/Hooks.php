<?php

namespace SMW\Elastic;

use SMW\ApplicationFactory;
use SMW\Store;
use SMW\Elastic\Indexer\Replication\ReplicationEntityExaminerDeferrableIndicatorProvider;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Connection\DummyClient;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Hooks {

	/**
	 * @var ElasticFactory
	 */
	private $elasticFactory;

	/**
	 * @var []
	 */
	private $handlers = [];

	/**
	 * @since 3.2
	 *
	 * @param ElasticFactory $elasticFactory
	 */
	public function __construct( ElasticFactory $elasticFactory ) {
		$this->elasticFactory = $elasticFactory;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getHandlers() : array {
		return [
			'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders' => [ $this, 'onRegisterEntityExaminerDeferrableIndicatorProviders' ],
		];
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders
	 * @since 3.2
	 */
	public function onRegisterEntityExaminerDeferrableIndicatorProviders( Store $store, &$indicatorProviders ) {

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
