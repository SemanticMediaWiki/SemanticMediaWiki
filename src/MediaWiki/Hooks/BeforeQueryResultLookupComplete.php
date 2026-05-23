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
	 * MediaWiki derives this method name from the hook
	 * `SMW::Store::BeforeQueryResultLookupComplete` when the handler is
	 * dispatched via the declarative `HookHandlers` registration in
	 * `extension.json`.
	 *
	 * @since 7.0.0
	 */
	public function onSMW__Store__BeforeQueryResultLookupComplete( $store, $query, &$result, $queryEngine ): bool {
		return $this->onSMWStoreBeforeQueryResultLookupComplete( $store, $query, $result, $queryEngine );
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMWStoreBeforeQueryResultLookupComplete( $store, $query, &$result, $queryEngine ): bool {
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
