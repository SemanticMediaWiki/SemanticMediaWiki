<?php

namespace SMW\MediaWiki\Api\Browse;

use Onoi\Cache\Cache;
use SMW\Store;
use SMW\Utils\Timer;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CachingLookup {

	const CACHE_NAMESPACE = 'smw:api:browse';
	const CACHE_TTL = 3600;

	/**
	 * @var Store
	 */
	private $store;

	private int|false $cacheTTL;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly Cache $cache,
		private readonly Lookup $lookup,
	) {
		$this->cacheTTL = self::CACHE_TTL;
	}

	/**
	 * @since 3.0
	 *
	 * @param int|bool $cacheTTL
	 */
	public function setCacheTTL( int|bool $cacheTTL ): void {
		$this->cacheTTL = $cacheTTL;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {
		Timer::start( __METHOD__ );

		$hash = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[
				$parameters,
				$this->lookup->getVersion()
			]
		);

		if ( $this->cacheTTL !== false && ( $res = $this->cache->fetch( $hash ) ) !== false ) {
			$res['meta']['isFromCache'] = true;
			$res['meta']['queryTime'] = Timer::getElapsedTime( __METHOD__, 5 );
			return $res;
		}

		$res = $this->lookup->lookup(
			$parameters
		);

		if ( $this->cacheTTL !== false ) {
			$this->cache->save( $hash, $res, $this->cacheTTL );
		}

		$res['meta']['isFromCache'] = false;
		$res['meta']['queryTime'] = Timer::getElapsedTime( __METHOD__, 5 );

		return $res;
	}

}
