<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\EventHandler;
use SMW\DIWikiPage;
use SMW\DIProperty;
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

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'title', $wikiPage->getTitle() );
		$dispatchContext->set( 'context', 'ArticlePurge' );

		if ( $settings->get( 'smwgFactboxCacheRefreshOnPurge' ) ) {
			EventHandler::getInstance()->getEventDispatcher()->dispatch(
				'factbox.cache.delete',
				$dispatchContext
			);
		}

		if ( $settings->get( 'smwgQueryResultCacheRefreshOnPurge' ) ) {

			$dispatchContext->set( 'ask', $applicationFactory->getStore()->getPropertyValues(
				DIWikiPage::newFromTitle( $wikiPage->getTitle() ),
				new DIProperty( '_ASK') )
			);

			EventHandler::getInstance()->getEventDispatcher()->dispatch(
				'cached.prefetcher.reset',
				$dispatchContext
			);
		}

		return true;
	}

}
