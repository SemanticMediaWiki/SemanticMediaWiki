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
		// Resolved per hook fire rather than injected through the declarative
		// `services:` array, because `HookContainer` memoises the constructed
		// handler and would pin one `ResultCache` for the container's lifetime.
		// JSONScript data sets vary `smwgQueryResultCacheType`, and the
		// `ServicesFactory::clear()` they run between data sets refreshes the
		// service but not a handler already holding it. `SMW.ResultCache` is
		// shared, so this hook and `AfterQueryResultLookupComplete` still see
		// one instance, with one query-result store and one set of statistics.
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
