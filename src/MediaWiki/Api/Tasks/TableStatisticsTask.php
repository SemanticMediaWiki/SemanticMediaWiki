<?php

namespace SMW\MediaWiki\Api\Tasks;

use Onoi\Cache\Cache;
use SMW\Store;
use Iterator;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTask extends Task {

	const CACHE_KEY = 'table-statistics';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var []
	 */
	private $cacheUsage;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param Cache $cache
	 */
	public function __construct( Store $store, Cache $cache ) {
		$this->store = $store;
		$this->cache = $cache;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $cacheUsage
	 */
	public function setCacheUsage( array $cacheUsage ) {
		$this->cacheUsage = $cacheUsage;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		$cacheTTL = 3600;

		if ( isset( $this->cacheUsage['api.table.statistics'] ) ) {
			$cacheTTL = $this->cacheUsage['api.table.statistics'];
		}

		$key = self::makeCacheKey( self::CACHE_KEY );

		// Guard against repeated API calls (or fuzzing)
		if ( $cacheTTL !== false && ( $result = $this->cache->fetch( $key ) ) !== false ) {
			return $result + [ 'isFromCache' => true, 'cacheTTL' => $cacheTTL ];
		}

		$tableStatisticsLookup = $this->store->service( 'TableStatisticsLookup' );

		$result = [
			'list' => $tableStatisticsLookup->getStats(),
			'time' => date( 'Y-m-d H:i:s' )
		];

		$this->cache->save( $key, $result, $cacheTTL );

		return $result;
	}

}
