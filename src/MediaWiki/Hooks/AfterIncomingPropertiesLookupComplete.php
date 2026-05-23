<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Services\ServicesFactory as ApplicationFactory;

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
	public function onSMW__Browse__AfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions ): bool {
		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()
			->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryReferenceBacklinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
			$store
		);

		$queryReferenceBacklinks->addReferenceLinksTo(
			$semanticData,
			$requestOptions
		);

		return true;
	}

}
