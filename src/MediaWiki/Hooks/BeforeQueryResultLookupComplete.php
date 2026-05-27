<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Query\Cache\ResultCache;

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
	public function __construct(
		private readonly ResultCache $resultCache,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Store__BeforeQueryResultLookupComplete( $store, $query, &$result, $queryEngine ): bool {
		$this->resultCache->setQueryEngine(
			$queryEngine
		);

		if ( !$this->resultCache->isEnabled() ) {
			return true;
		}

		$result = $this->resultCache->getQueryResult(
			$query
		);

		return false;
	}

}
