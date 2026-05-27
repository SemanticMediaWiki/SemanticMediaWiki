<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Query\Cache\ResultCache;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;

/**
 * Runs after the regular query result lookup completes. Updates the query
 * dependency tracker, records cache stats, and warms the entity ID cache.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class AfterQueryResultLookupComplete {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly QueryDependencyLinksStoreFactory $queryDependencyLinksStoreFactory,
		private readonly ResultCache $resultCache,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Store__AfterQueryResultLookupComplete( $store, &$result ): bool {
		$queryDependencyLinksStore = $this->queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$store
		);

		$queryDependencyLinksStore->updateDependencies( $result );

		$this->resultCache->recordStats();

		$store->getObjectIds()->warmUpCache( $result );

		return true;
	}

}
