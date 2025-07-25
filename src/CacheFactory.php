<?php

namespace SMW;

use ObjectCache;
use Onoi\BlobStore\BlobStore;
use Onoi\Cache\Cache;
use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use RuntimeException;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactory {

	/**
	 * @var string|int
	 */
	private $mainCacheType;

	/**
	 * @since 2.2
	 *
	 * @param string|int|null $mainCacheType
	 */
	public function __construct( $mainCacheType = null ) {
		$this->mainCacheType = $mainCacheType;

		if ( $this->mainCacheType === null ) {
			$this->mainCacheType = $GLOBALS['smwgMainCacheType'];
		}
	}

	/**
	 * @since 2.2
	 *
	 * @return string|int
	 */
	public function getMainCacheType() {
		return $this->mainCacheType;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public static function getCachePrefix() {
		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			return $GLOBALS['wgCachePrefix'] === false ?
				\WikiMap::getCurrentWikiId() : $GLOBALS['wgCachePrefix'];
		}

		return $GLOBALS['wgCachePrefix'] === false ?
			\MediaWiki\WikiMap\WikiMap::getCurrentWikiId() : $GLOBALS['wgCachePrefix'];
	}

	/**
	 * @since 2.2
	 *
	 * @param \MediaWiki\Title\Title|\Title|int|string $key
	 *
	 * @return string
	 */
	public static function getPurgeCacheKey( $key ) {
		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			if ( $key instanceof \Title ) {
				$key = $key->getArticleID();
			}
		} elseif ( $key instanceof \MediaWiki\Title\Title ) {
			$key = $key->getArticleID();
		}

		return self::getCachePrefix() . ':smw:arc:' . md5( $key ?? '' );
	}

	/**
	 * @since 2.2
	 *
	 * @param array $cacheOptions
	 *
	 * @return object
	 * @throws RuntimeException
	 */
	public function newCacheOptions( array $cacheOptions ) {
		if ( !isset( $cacheOptions['useCache'] ) || !isset( $cacheOptions['ttl'] ) ) {
			throw new RuntimeException( "Cache options is missing a useCache/ttl parameter" );
		}

		return (object)$cacheOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @param int $cacheSize
	 *
	 * @return Cache
	 */
	public function newFixedInMemoryCache( $cacheSize = 500 ) {
		return OnoiCacheFactory::getInstance()->newFixedInMemoryLruCache( $cacheSize );
	}

	/**
	 * @since 2.2
	 *
	 * @return Cache
	 */
	public function newNullCache() {
		return OnoiCacheFactory::getInstance()->newNullCache();
	}

	/**
	 * @since 2.2
	 *
	 * @param int|string|null $mediaWikiCacheType
	 *
	 * @return Cache
	 */
	public function newMediaWikiCompositeCache( $mediaWikiCacheType = null ) {
		$compositeCache = OnoiCacheFactory::getInstance()->newCompositeCache( [
			$this->newFixedInMemoryCache( 500 ),
			$this->newMediaWikiCache( $mediaWikiCacheType )
		] );

		return $compositeCache;
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string|null $mediaWikiCacheType
	 *
	 * @return Cache
	 */
	public function newMediaWikiCache( $mediaWikiCacheType = null ) {
		$mediaWikiCache = ObjectCache::getInstance(
			( $mediaWikiCacheType === null ? $this->getMainCacheType() : $mediaWikiCacheType )
		);

		return OnoiCacheFactory::getInstance()->newMediaWikiCache( $mediaWikiCache );
	}

	/**
	 * @since 2.5
	 *
	 * @param int|null $cacheType
	 *
	 * @return Cache
	 */
	public function newCacheByType( $cacheType = null ) {
		if ( $cacheType === CACHE_NONE || $cacheType === null ) {
			return $this->newNullCache();
		}

		return $this->newMediaWikiCache( $cacheType );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $namespace
	 * @param string|int|null $cacheType
	 * @param int $cacheLifetime
	 *
	 * @return BlobStore
	 */
	public function newBlobStore( $namespace, $cacheType = null, $cacheLifetime = 0 ) {
		return ApplicationFactory::getInstance()->create( 'BlobStore', $namespace, $cacheType, $cacheLifetime );
	}

}
