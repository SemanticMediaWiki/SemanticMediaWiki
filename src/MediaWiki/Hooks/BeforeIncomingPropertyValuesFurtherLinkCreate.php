<?php

namespace SMW\MediaWiki\Hooks;

use SMW\SQLStore\QueryDependencyLinksStoreFactory;

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
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly QueryDependencyLinksStoreFactory $queryDependencyLinksStoreFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Browse__BeforeIncomingPropertyValuesFurtherLinkCreate( $property, $subject, &$html, $store ): bool {
		$queryReferenceBacklinks = $this->queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
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
