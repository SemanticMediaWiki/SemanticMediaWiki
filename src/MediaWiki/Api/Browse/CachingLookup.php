<?php

namespace SMW\MediaWiki\Api\Browse;

use Onoi\Cache\Cache;
use SMW\Utils\Timer;

/**
 * @license GNU GPL v2+
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

	/**
	 * @var Lookup
	 */
	private $lookup;

	/**
	 * @var integer|boolean
	 */
	private $cacheTTL;

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 * @param Lookup $lookup
	 */
	public function __construct( Cache $cache, Lookup $lookup ) {
		$this->cache = $cache;
		$this->lookup = $lookup;
		$this->cacheTTL = self::CACHE_TTL;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer|boolean $cacheTTL
	 */
	public function setCacheTTL( $cacheTTL ) {
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
