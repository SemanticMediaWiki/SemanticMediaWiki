<?php

namespace SMW;

use WikiPage;

/**
 * ArticlePurge hook
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * ArticlePurge hook executes before running "&action=purge"
 *
 * @note Create a CacheStore entry about the article in order
 * for ParserAfterTidy to initiate a Store update.
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
 *
 * @ingroup Hook
 */
class ArticlePurge extends InjectableHook {

	/** @var OutputPage */
	protected $wikiPage = null;

	/**
	 * @since  1.9
	 *
	 * @param WikiPage $wikiPage article being purged
	 */
	public function __construct( WikiPage &$wikiPage ) {
		$this->wikiPage = $wikiPage;
	}

	/**
	 * @see HookBase::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$pageId   = $this->wikiPage->getTitle()->getArticleID();

		$cache    = $this->getDependencyBuilder()->newObject( 'CacheHandler' );
		$settings = $this->getDependencyBuilder()->newObject( 'Settings' );

		$cache->setCacheEnabled( $pageId > 0 )
			->key( 'autorefresh', $pageId )
			->set( $settings->get( 'smwgAutoRefreshOnPurge' ) );

		return true;
	}

}
