<?php

namespace SMW\Cache;

use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use SMW\ApplicationFactory;
use ObjectCache;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactory {

	/**
	 * @var string|integer
	 */
	private $mainCacheType;

	/**
	 * @since 2.2
	 *
	 * @param string|integer $mainCacheType
	 */
	public function __construct( $mainCacheType ) {
		$this->mainCacheType = $mainCacheType;
	}

	/**
	 * @since 2.2
	 *
	 * @return string|integer
	 */
	public function getMainCacheType() {
		return $this->mainCacheType;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $cacheOptions
	 *
	 * @return stdClass
	 */
	public function newCacheOptions( array $cacheOptions ) {
		return (object)$cacheOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @param integer $cacheSize
	 *
	 * @return Cache
	 */
	public function newFixedInMemoryCache( $cacheSize = 500 ) {
		return OnoiCacheFactory::getInstance()->newFixedInMemoryCache( $cacheSize );
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

		$compositeCache = OnoiCacheFactory::getInstance()->newCompositeCache( array(
			$this->newFixedInMemoryCache( 500 ),
			OnoiCacheFactory::getInstance()->newMediaWikiCache( $mediaWikiCache )
		) );

		return $compositeCache;
	}

}
