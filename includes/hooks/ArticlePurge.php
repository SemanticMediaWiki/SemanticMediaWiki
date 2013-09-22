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
class ArticlePurge extends FunctionHook {

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
	 * Returns a CacheIdGenerator object
	 *
	 * @since 1.9
	 *
	 * @return CacheIdGenerator
	 */
	public static function newCacheId( $pageId ) {
		return new CacheIdGenerator( $pageId, 'autorefresh' );
	}

	/**
	 * @see HookBase::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$pageId = $this->wikiPage->getTitle()->getArticleID();

		/**
		 * @var Settings $settings
		 */
		$settings = $this->getDependencyBuilder()->newObject( 'Settings' );

		/**
		 * @var CacheHandler $cache
		 */
		$cache = $this->getDependencyBuilder()->newObject( 'CacheHandler' );

		$cache->setCacheEnabled( $pageId > 0 )
			->setKey( $this->newCacheId( $pageId ) )
			->set( $settings->get( 'smwgAutoRefreshOnPurge' ) );

		$cache->setCacheEnabled( $settings->get( 'smwgFactboxCacheRefreshOnPurge' ) )
			->setKey( FactboxCache::newCacheId( $pageId ) )
			->delete();

		return true;
	}

}
