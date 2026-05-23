<?php

namespace SMW\Elastic\Hooks;

use MediaWiki\Html\TemplateParser;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\Indexer\Replication\ReplicationEntityExaminerDeferrableIndicatorProvider;
use SMW\EntityCache;
use SMW\Store;

/**
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class RegisterEntityExaminerDeferrableIndicatorProviders {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly ElasticFactory $elasticFactory,
		private readonly EntityCache $entityCache,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Indicator__EntityExaminer__RegisterDeferrableIndicatorProviders(
		Store $store,
		&$indicatorProviders
	): bool {
		$connection = $store->getConnection( 'elastic' );
		if ( $connection === null || $connection instanceof DummyClient ) {
			return true;
		}

		$options = $connection->getConfig();

		$replicationEntityExaminerDeferrableIndicatorProvider = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$store,
			$this->entityCache,
			$this->elasticFactory->newReplicationCheck( $store ),
			new TemplateParser( __DIR__ . '/../../../templates/EntityExaminer' )
		);

		$replicationEntityExaminerDeferrableIndicatorProvider->canCheckReplication(
			$options->dotGet( 'indexer.monitor.entity.replication' )
		);

		$indicatorProviders[] = $replicationEntityExaminerDeferrableIndicatorProvider;

		return true;
	}

}
