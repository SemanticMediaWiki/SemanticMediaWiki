<?php

namespace SMW\MediaWiki\Hooks;

use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\EventHandler;
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

	use EventDispatcherAwareTrait;

	const CACHE_NAMESPACE = 'smw:arc';

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process( WikiPage &$wikiPage ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$title = $wikiPage->getTitle();
		$articleID = $title->getArticleID();

		$settings = $applicationFactory->getSettings();

		$cache = $applicationFactory->getCache();

		if ( $articleID > 0 ) {
			$cache->save(
				smwfCacheKey( self::CACHE_NAMESPACE, $articleID ),
				$settings->get( 'smwgAutoRefreshOnPurge' )
			);
		}

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'title', $title );
		$dispatchContext->set( 'context', 'ArticlePurge' );

		if ( $settings->get( 'smwgQueryResultCacheRefreshOnPurge' ) ) {

			$dispatchContext->set( 'ask', $applicationFactory->getStore()->getPropertyValues(
				DIWikiPage::newFromTitle( $title ),
				new DIProperty( '_ASK') )
			);

			EventHandler::getInstance()->getEventDispatcher()->dispatch(
				'cached.prefetcher.reset',
				$dispatchContext
			);
		}

		$context = [
			'context' => 'ArticlePurge',
			'title' => $title
		];

		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );

		return true;
	}

}
