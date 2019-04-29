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
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
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

		if ( $settings->get( 'smwgQueryResultCacheRefreshOnPurge' ) ) {
			$this->invalidateResultCache( $applicationFactory->getStore(), $title );
		}

		$context = [
			'context' => 'ArticlePurge',
			'title' => $title
		];

		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );

		return true;
	}

	private function invalidateResultCache( $store, $title ) {

		$dependency_list = $store->getPropertyValues(
			DIWikiPage::newFromTitle( $title ),
			new DIProperty( '_ASK' )
		);

		$context = [
			'context' => 'ArticlePurge',
			'title' => $title,
			'dependency_list' => $dependency_list
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );
	}

}
