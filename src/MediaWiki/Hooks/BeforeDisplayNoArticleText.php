<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Page\Hook\BeforeDisplayNoArticleTextHook;
use SMW\DataItems\Property;

/**
 * Before displaying noarticletext or noarticletext-nopermission messages
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class BeforeDisplayNoArticleText implements BeforeDisplayNoArticleTextHook {

	/**
	 * @since 7.0.0
	 */
	public function onBeforeDisplayNoArticleText( $article ) {
		// Avoid having "noarticletext" info being generated for predefined
		// properties as we are going to display an introductory text
		if ( $article->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			return Property::newFromUserLabel( $article->getTitle()->getText() )->isUserDefined();
		}

		return true;
	}

}
