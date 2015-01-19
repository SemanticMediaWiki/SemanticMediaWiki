<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DIProperty;

/**
 * Before displaying noarticletext or noarticletext-nopermission messages
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class BeforeDisplayNoArticleText {

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
	 * @return boolean
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
