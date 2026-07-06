<?php

namespace SMW\Elastic\Hooks;

use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\ElasticStore;
use SMW\Elastic\Indexer\Indexer;
use SMW\Store;

/**
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::EntityReferenceCleanUpComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class EntityReferenceCleanUpComplete {

	private ?Indexer $indexer = null;

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
	public function onSMW__SQLStore__EntityReferenceCleanUpComplete( Store $store, $id, $subject, $isRedirect ): bool {
		if ( !$store instanceof ElasticStore || $store->getConnection( 'elastic' ) instanceof DummyClient ) {
			return true;
		}

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $store );
		}

		$this->indexer->setOrigin( __METHOD__ );
		$this->indexer->delete( [ $id ] );

		return true;
	}

}
