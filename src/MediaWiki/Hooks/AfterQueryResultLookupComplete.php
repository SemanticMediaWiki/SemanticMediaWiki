<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Services\ServicesFactory as ApplicationFactory;
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

		// `ResultCache` is resolved lazily rather than via the `services:`
		// array because its `BlobStore` is built from `smwgQueryResultCacheType`
		// at construction and `MediaWikiServices` caches the resolved instance.
		// See `BeforeQueryResultLookupComplete` for the full rationale.
		ApplicationFactory::getInstance()->singleton( 'ResultCache' )->recordStats();

		$store->getObjectIds()->warmUpCache( $result );

		return true;
	}

}
