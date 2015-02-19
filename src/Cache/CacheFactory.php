<?php

namespace SMW\Cache;

use Onoi\Cache\Cache;
use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use SMW\ApplicationFactory;
use ObjectCache;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactory {

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var CacheFactory
	 */
	private $cacheFactory;

	/**
	 * @since 2.1
	 *
	 * @param ApplicationFactory $applicationFactory
	 */
	public function __construct( ApplicationFactory $applicationFactory ) {
		$this->applicationFactory = $applicationFactory;
		$this->cacheFactory = OnoiCacheFactory::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * @param integer $cacheSize
	 *
	 * @return Cache
	 */
	public function newFixedInMemoryCache( $cacheSize = 500 ) {
		return $this->cacheFactory->newFixedInMemoryCache( $cacheSize );
	}

	/**
	 * @since 2.2
	 *
	 * @param integer|string $mediaWikiCacheType
	 *
	 * @return Cache
	 */
	public function newMediaWikiCompositeCache( $mediaWikiCacheType ) {

		$mediaWikiCache = ObjectCache::getInstance( $mediaWikiCacheType );

		$compositeCache = $this->cacheFactory->newCompositeCache( array(
			$this->newFixedInMemoryCache( 500 ),
			$this->cacheFactory->newMediaWikiCache( $mediaWikiCache )
		) );

		return $compositeCache;
	}

	/**
	 * @since 2.2
	 *
	 * @return SemanticDataCache
	 */
	public function getSemanticDataCache() {
		return SemanticDataCache::getInstance();
	}

}
