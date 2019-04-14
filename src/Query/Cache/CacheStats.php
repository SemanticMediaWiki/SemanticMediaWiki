<?php

namespace SMW\Query\Cache;

use SMW\Utils\Stats;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CacheStats extends Stats {

	/**
	 * @since 3.0
	 */
	public function initRecord() {
		parent::initRecord();

		$date = date( 'Y-m-d H:i:s' );

		$this->init( 'misses', [] );
		$this->init( 'hits', [] );
		$this->init( 'deletes', [] );
		$this->init( 'noCache', [] );
		$this->init( 'medianRetrievalResponseTime', [] );
		$this->set( 'meta.version', self::VERSION );
		$this->set( 'meta.cacheLifetime.embedded', $GLOBALS['smwgQueryResultCacheLifetime'] );
		$this->set( 'meta.cacheLifetime.nonEmbedded', $GLOBALS['smwgQueryResultNonEmbeddedCacheLifetime'] );
		$this->init( 'meta.collectionDate.start', $date );
		$this->set( 'meta.collectionDate.update', $date );
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getStats() {

		$stats = array_filter( parent::getStats(), function( $key ) {
			return $key !== false;
		} );

		if ( !isset( $stats['misses'] ) || ! isset( $stats['hits'] ) ) {
			return $stats;
		}

		$misses = $this->sum( 0, $stats['misses'] );
		$hits = $this->sum( 0, $stats['hits'] );

		$stats['ratio'] = [];
		$stats['ratio']['hit'] = $hits > 0 ? round( $hits / ( $hits + $misses ), 4 ) : 0;
		$stats['ratio']['miss'] = $hits > 0 ? round( 1 - $stats['ratio']['hit'], 4 ) : 1;

		// Move to last
		$meta = $stats['meta'];
		unset( $stats['meta'] );
		$stats['meta'] = $meta;

		return $stats;
	}

	// http://stackoverflow.com/questions/3777995/php-array-recursive-sum
	private static function sum( $value, $container ) {
		return $value + ( is_array( $container ) ? array_reduce( $container, 'self::sum' ) : $container );
	}

}
