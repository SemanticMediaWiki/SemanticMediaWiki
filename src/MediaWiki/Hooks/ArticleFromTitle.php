<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Page\Hook\ArticleFromTitleHook;
use SMW\MediaWiki\PageFactory;
use SMW\Store;

/**
 * Register special classes for displaying semantic content on Property and
 * Concept pages.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleFromTitle implements ArticleFromTitleHook {

	/**
	 * @since 2.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onArticleFromTitle( $title, &$article, $context ) {
		$ns = $title->getNamespace();

		if ( $ns !== SMW_NS_PROPERTY && $ns !== SMW_NS_CONCEPT ) {
			return true;
		}

		$pageFactory = new PageFactory(
			$this->store
		);

		$article = $pageFactory->newPageFromTitle(
			$title
		);

		return true;
	}

}
