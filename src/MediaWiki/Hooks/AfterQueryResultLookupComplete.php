<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Services\ServicesFactory as ApplicationFactory;

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
	 * MediaWiki derives this method name from the hook
	 * `SMW::Store::AfterQueryResultLookupComplete` when the handler is
	 * dispatched via the declarative `HookHandlers` registration in
	 * `extension.json`.
	 *
	 * @since 7.0.0
	 */
	public function onSMW__Store__AfterQueryResultLookupComplete( $store, &$result ): bool {
		return $this->onSMWStoreAfterQueryResultLookupComplete( $store, $result );
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMWStoreAfterQueryResultLookupComplete( $store, &$result ): bool {
		$applicationFactory = ApplicationFactory::getInstance();
		$queryDependencyLinksStoreFactory = $applicationFactory->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$store
		);

		$queryDependencyLinksStore->updateDependencies( $result );

		$applicationFactory->singleton( 'ResultCache' )->recordStats();

		$store->getObjectIds()->warmUpCache( $result );

		return true;
	}

}
