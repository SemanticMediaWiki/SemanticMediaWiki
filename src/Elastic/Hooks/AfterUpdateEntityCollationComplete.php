<?php

namespace SMW\Elastic\Hooks;

use Onoi\MessageReporter\MessageReporter;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\ElasticFactory;
use SMW\Store;

/**
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Maintenance::AfterUpdateEntityCollationComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class AfterUpdateEntityCollationComplete {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly ElasticFactory $elasticFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Maintenance__AfterUpdateEntityCollationComplete( Store $store, MessageReporter $messageReporter ): bool {
		$connection = $store->getConnection( 'elastic' );
		if ( $connection === null || $connection instanceof DummyClient ) {
			return true;
		}

		$rebuilder = $this->elasticFactory->newRebuilder( $store );

		$rebuilder->setMessageReporter( $messageReporter );

		$updateEntityCollationComplete = $this->elasticFactory->newUpdateEntityCollationComplete(
			$store,
			$messageReporter
		);

		$updateEntityCollationComplete->runUpdate( $rebuilder );

		return true;
	}

}
