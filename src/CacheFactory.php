<?php

namespace SMW;

use MediaWiki\WikiMap\WikiMap;
use RuntimeException;
use SMW\Query\Cache\QueryResultStore;
use SMW\Services\ServicesFactory as ApplicationFactory;
use stdClass;

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
		$this->mainCacheType = $mainCacheType ?? $GLOBALS['smwgMainCacheType'];
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
		return $GLOBALS['wgCachePrefix'] === false ?
			WikiMap::getCurrentWikiId() : $GLOBALS['wgCachePrefix'];
	}

	/**
	 * @since 2.2
	 *
	 * @throws RuntimeException
	 */
	public function newCacheOptions( array $cacheOptions ): stdClass {
		if ( !isset( $cacheOptions['useCache'] ) || !isset( $cacheOptions['ttl'] ) ) {
			throw new RuntimeException( "Cache options is missing a useCache/ttl parameter" );
		}

		return (object)$cacheOptions;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $namespace
	 * @param string|int|null $cacheType
	 * @param int $cacheLifetime
	 *
	 * @return QueryResultStore
	 */
	public function newQueryResultStore( $namespace, $cacheType = null, $cacheLifetime = 0 ) {
		return ApplicationFactory::getInstance()->create( 'QueryResultStore', $namespace, $cacheType, $cacheLifetime );
	}

}
