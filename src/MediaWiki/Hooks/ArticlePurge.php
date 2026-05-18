<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use Onoi\Cache\Cache;
use SMW\DataItems\Property;
use SMW\EventDispatcher\EventDispatcherAwareTrait;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use WikiPage;

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
class ArticlePurge implements HookListener {

	use OptionsAwareTrait;
	use EventDispatcherAwareTrait;

	const CACHE_NAMESPACE = 'smw:arc';

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly Cache $cache ) {
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process( WikiPage &$wikiPage ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$title = $wikiPage->getTitle();
		$articleID = $title->getArticleID();

		$settings = $applicationFactory->getSettings();

		if ( $articleID > 0 ) {
			$this->cache->save(
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

	private function invalidateResultCache( Store $store, Title $title ): void {
		$dependency_list = $store->getPropertyValues(
			\SMW\DataItems\WikiPage::newFromTitle( $title ),
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
