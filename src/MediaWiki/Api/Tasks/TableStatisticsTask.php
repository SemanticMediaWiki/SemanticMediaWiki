<?php

namespace SMW\MediaWiki\Api\Tasks;

use Onoi\Cache\Cache;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTask extends Task {

	const CACHE_KEY = 'table-statistics';

	/**
	 * @var
	 */
	private ?array $cacheUsage = null;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly Cache $cache,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param array $cacheUsage
	 */
	public function setCacheUsage( array $cacheUsage ): void {
		$this->cacheUsage = $cacheUsage;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ): array {
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
