<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\SpecialSearchProfilesHook;
use SearchEngineConfig;
use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\Site;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfiles
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SpecialSearchProfiles implements SpecialSearchProfilesHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly SearchEngineConfig $searchEngineConfig,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSpecialSearchProfiles( &$profiles ) {
		$options = [
			'default_namespaces' => $this->searchEngineConfig->defaultNamespaces()
		];

		ProfileForm::addProfile(
			Site::searchType(),
			$profiles,
			$options
		);

		return true;
	}

}
