<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Replaces the standard "further" link in the property browser when the
 * property has reference backlinks that should drive the link target.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::BeforeIncomingPropertyValuesFurtherLinkCreate
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class BeforeIncomingPropertyValuesFurtherLinkCreate {

	/**
	 * MediaWiki derives this method name from the hook
	 * `SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate` when the
	 * handler is dispatched via the declarative `HookHandlers` registration
	 * in `extension.json`.
	 *
	 * @since 7.0.0
	 */
	public function onSMW__Browse__BeforeIncomingPropertyValuesFurtherLinkCreate( $property, $subject, &$html, $store ): bool {
		return $this->onSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate( $property, $subject, $html, $store );
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate( $property, $subject, &$html, $store ): bool {
		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()
			->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryReferenceBacklinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
			$store
		);

		$doesRequireFurtherLink = $queryReferenceBacklinks->doesRequireFurtherLink(
			$property,
			$subject,
			$html
		);

		// Return false in order to stop the link creation process to replace the
		// standard link
		return $doesRequireFurtherLink;
	}

}
