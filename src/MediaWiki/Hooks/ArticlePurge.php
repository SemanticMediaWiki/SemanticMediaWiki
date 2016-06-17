<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use WikiPage;

/**
 * A function hook being executed before running "&action=purge"
 *
 * A temporary cache entry is created to mark and identify the
 * Article that has been purged.
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ArticlePurge {

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process( WikiPage &$wikiPage ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$pageId = $wikiPage->getTitle()->getArticleID();
		$settings = $applicationFactory->getSettings();

		$cache = $applicationFactory->getCache();
		$cacheFactory = $applicationFactory->newCacheFactory();

		if ( $pageId > 0 ) {
			$cache->save(
				$cacheFactory->getPurgeCacheKey( $pageId ),
				$settings->get( 'smwgAutoRefreshOnPurge' )
			);
		}

		if ( $settings->get( 'smwgFactboxCacheRefreshOnPurge' ) ) {
			$cache->delete(
				$cacheFactory->getFactboxCacheKey( $pageId )
			);
		}

		return true;
	}

}
