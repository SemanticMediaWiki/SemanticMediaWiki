<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Search\Hook\SpecialSearchProfileFormHook;
use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SpecialSearchProfileForm implements SpecialSearchProfileFormHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSpecialSearchProfileForm( $search, &$form, $profile, $term, $opts ) {
		if ( !ProfileForm::isValidProfile( $profile ) ) {
			return true;
		}

		$searchEngineConfig = ApplicationFactory::getInstance()->singleton( 'SearchEngineConfig' );

		$profileForm = new ProfileForm(
			$this->store,
			$search
		);

		$profileForm->setSearchableNamespaces(
			$searchEngineConfig->searchableNamespaces()
		);

		$profileForm->buildForm( $form, $opts );

		return false;
	}

}
