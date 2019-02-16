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
class DuplicateLookupTask extends Task {

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

		if ( isset( $this->cacheUsage['api.task'] ) ) {
			$cacheTTL = $this->cacheUsage['api.task'];
		}

		$key = self::makeCacheKey( 'duplicate-lookup' );

		// Guard against repeated API calls (or fuzzing)
		if ( ( $result = $this->cache->fetch( $key ) ) !== false && $cacheTTL !== false ) {
			return $result + ['isFromCache' => true ];
		}

		$rows = $this->store->getObjectIds()->findDuplicates();

		// Avoid "Exception caught: Serialization of 'Closure' is not allowedException ..."
		if ( $rows instanceof Iterator ) {
			$rows = iterator_to_array( $rows );
		}

		$result = [
			'list'  => $rows,
			'count' => count( $rows ),
			'time'  => date( 'Y-m-d H:i:s' )
		];

		$this->cache->save( $key, $result, $cacheTTL );

		return $result;
	}

}
