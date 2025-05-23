<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\MediaWiki\HookListener;

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

	/**
	 * @var Page
	 */
	private $article;

	/**
	 * @since  2.0
	 *
	 * @param Page $article
	 */
	public function __construct( $article ) {
		$this->article = $article;
	}

	/**
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function process() {
		// Avoid having "noarticletext" info being generated for predefined
		// properties as we are going to display an introductory text
		if ( $this->article->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			return DIProperty::newFromUserLabel( $this->article->getTitle()->getText() )->isUserDefined();
		}

		return true;
	}

}
