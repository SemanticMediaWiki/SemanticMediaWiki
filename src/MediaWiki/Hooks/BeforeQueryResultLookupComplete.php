<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Runs before the regular query result lookup runs, allowing the cache to
 * short-circuit when a fresh cached result is available.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::BeforeQueryResultLookupComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class BeforeQueryResultLookupComplete {

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Store__BeforeQueryResultLookupComplete( $store, $query, &$result, $queryEngine ): bool {
		// `ResultCache` cannot be injected through the declarative `services:`
		// array because its `BlobStore` is constructed from
		// `smwgQueryResultCacheType` at instantiation, and `MediaWikiServices`
		// caches the resolved instance for the container's lifetime. JSONScript
		// tests vary that setting per test case, so the cached instance would
		// hold a stale cache backend. Resolving it through
		// `ServicesFactory::singleton()` rebuilds the instance per hook fire
		// against current settings.
		$resultCache = ApplicationFactory::getInstance()->singleton( 'ResultCache' );

		$resultCache->setQueryEngine(
			$queryEngine
		);

		if ( !$resultCache->isEnabled() ) {
			return true;
		}

		$result = $resultCache->getQueryResult(
			$query
		);

		return false;
	}

}
