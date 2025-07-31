<?php

namespace SMW\MediaWiki\Hooks;

use Article;
use MediaWiki\Title\Title;
use SMW\MediaWiki\HookListener;
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
class ArticleFromTitle implements HookListener {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 2.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.0
	 *
	 * @param Title &$title
	 * @param Article|null &$page
	 *
	 * @return true
	 */
	public function process( Title &$title, ?Article &$page = null ) {
		$ns = $title->getNamespace();

		if ( $ns !== SMW_NS_PROPERTY && $ns !== SMW_NS_CONCEPT ) {
			return true;
		}

		$pageFactory = new PageFactory(
			$this->store
		);

		$page = $pageFactory->newPageFromTitle(
			$title
		);

		return true;
	}

}
