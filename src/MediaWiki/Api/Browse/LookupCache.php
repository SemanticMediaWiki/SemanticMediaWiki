<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\Utils\Timer;
use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LookupCache {

	const CACHE_NAMESPACE = 'smw:api:browse';
	const CACHE_TTL = 3600;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @var integer|boolean
	 */
	private $cacheTTL;

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 * @param ListLookup $listLookup
	 */
	public function __construct( Cache $cache, ListLookup $listLookup ) {
		$this->cache = $cache;
		$this->listLookup = $listLookup;
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
	 * @param integer $ns
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( $ns, array $parameters ) {

		Timer::start( __METHOD__ );

		$hash = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[
				$ns,
				$parameters,
				ListLookup::VERSION
			]
		);

		if ( $this->cacheTTL !== false && ( $res = $this->cache->fetch( $hash ) ) !== false ) {
			$res['meta']['isFromCache'] = true;
			$res['meta']['queryTime'] = Timer::getElapsedTime( __METHOD__, 5 );
			return $res;
		}

		$res = $this->listLookup->lookup(
			$ns,
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
