<?php

namespace SMW\MediaWiki\Hooks;

use Article;
use SMW\DataItems\Property;
use SMW\MediaWiki\HookListener;
use WikiPage;

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
class BeforeDisplayNoArticleText implements HookListener {

	private Article|WikiPage $article;

	/**
	 * @since  2.0
	 *
	 * @param Article $article
	 */
	public function __construct( Article|WikiPage $article ) {
		$this->article = $article;
	}

	/**
	 * @since 2.0
	 */
	public function process(): bool {
		// Avoid having "noarticletext" info being generated for predefined
		// properties as we are going to display an introductory text
		if ( $this->article->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			return Property::newFromUserLabel( $this->article->getTitle()->getText() )->isUserDefined();
		}

		return true;
	}

}
