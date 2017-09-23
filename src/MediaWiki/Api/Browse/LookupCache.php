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
	const CACHE_TTL = 1800;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 * @param ListLookup $listLookup
	 */
	public function __construct( Cache $cache, ListLookup $listLookup ) {
		$this->cache = $cache;
		$this->listLookup = $listLookup;
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

		if ( ( $res = $this->cache->fetch( $hash ) ) !== false ) {
			$res['meta']['isFromCache'] = true;
			$res['meta']['queryTime'] = Timer::getElapsedTime( __METHOD__, 5 );
			return $res;
		}

		$res = $this->listLookup->lookup(
			$ns,
			$parameters
		);

		$res['meta']['isFromCache'] = false;
		$res['meta']['queryTime'] = Timer::getElapsedTime( __METHOD__, 5 );

		$this->cache->save(
			$hash,
			$res,
			self::CACHE_TTL
		);

		return $res;
	}

}
