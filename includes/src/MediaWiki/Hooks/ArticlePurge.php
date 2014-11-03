<?php

namespace SMW\MediaWiki\Hooks;

use SMW\FactboxCache;
use SMW\ApplicationFactory;
use SMW\CacheIdGenerator;

use WikiPage;

/**
 * A function hook being executed before running "&action=purge"
 *
 * A temporary cache entry is created to mark and identify the
 * Article that has been purged.
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ArticlePurge {

	/**
	 * @var WikiPage
	 */
	protected $wikiPage = null;

	/**
	 * @since  1.9
	 *
	 * @param WikiPage $wikiPage
	 */
	public function __construct( WikiPage &$wikiPage ) {
		$this->wikiPage = $wikiPage;
	}

	/**
	 * @since 1.9
	 *
	 * @return CacheIdGenerator
	 */
	public static function newCacheId( $pageId ) {
		return new CacheIdGenerator( $pageId, 'autorefresh' );
	}

	/**
	 * @see FunctionHook::process
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
		$settings = ApplicationFactory::getInstance()->getSettings();

		/**
		 * @var CacheHandler $cache
		 */
		$cache = ApplicationFactory::getInstance()->getCache();

		$cache->setCacheEnabled( $pageId > 0 )
			->setKey( $this->newCacheId( $pageId ) )
			->set( $settings->get( 'smwgAutoRefreshOnPurge' ) );

		$cache->setCacheEnabled( $settings->get( 'smwgFactboxCacheRefreshOnPurge' ) )
			->setKey( FactboxCache::newCacheId( $pageId ) )
			->delete();

		return true;
	}

}
