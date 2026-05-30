<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Page\Hook\ArticlePurgeHook;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\Settings;
use SMW\Store;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * A function hook being executed before running "&action=purge"
 *
 * A temporary cache entry is created to mark and identify the
 * Article that has been purged.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ArticlePurge implements ArticlePurgeHook {

	const CACHE_NAMESPACE = 'smw:arc';

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly BagOStuff $cache,
		private readonly Settings $settings,
		private readonly EventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onArticlePurge( $wikiPage ) {
		$title = $wikiPage->getTitle();
		$articleID = $title->getArticleID();

		if ( $articleID > 0 ) {
			$this->cache->set(
				smwfCacheKey( self::CACHE_NAMESPACE, $articleID ),
				$this->settings->get( 'smwgAutoRefreshOnPurge' )
			);
		}

		if ( $this->settings->get( 'smwgQueryResultCacheRefreshOnPurge' ) ) {
			$this->invalidateResultCache( $title );
		}

		$context = [
			'context' => 'ArticlePurge',
			'title' => $title
		];

		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );

		return true;
	}

	private function invalidateResultCache( Title $title ): void {
		$dependency_list = $this->store->getPropertyValues(
			WikiPage::newFromTitle( $title ),
			new Property( '_ASK' )
		);

		$context = [
			'context' => 'ArticlePurge',
			'title' => $title,
			'dependency_list' => $dependency_list
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );
	}

}
