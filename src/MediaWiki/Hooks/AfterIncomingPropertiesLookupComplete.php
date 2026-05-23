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
	 * MediaWiki derives this method name from the hook
	 * `SMW::Browse::AfterIncomingPropertiesLookupComplete` when the handler
	 * is dispatched via the declarative `HookHandlers` registration in
	 * `extension.json`.
	 *
	 * @since 7.0.0
	 */
	public function onSMW__Browse__AfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions ): bool {
		return $this->onSMWBrowseAfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions );
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMWBrowseAfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions ): bool {
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
