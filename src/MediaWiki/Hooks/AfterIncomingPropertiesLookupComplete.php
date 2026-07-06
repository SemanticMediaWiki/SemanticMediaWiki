<?php

namespace SMW\MediaWiki\Hooks;

use SMW\SQLStore\QueryDependencyLinksStoreFactory;

/**
 * Adds reference backlinks to the result of an incoming-properties lookup.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::AfterIncomingPropertiesLookupComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class AfterIncomingPropertiesLookupComplete {

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
	public function onSMW__Browse__AfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions ): bool {
		$queryReferenceBacklinks = $this->queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
			$store
		);

		$queryReferenceBacklinks->addReferenceLinksTo(
			$semanticData,
			$requestOptions
		);

		return true;
	}

}
