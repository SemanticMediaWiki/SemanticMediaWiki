<?php

namespace SMW\Tests\Utils;

use MapCacheLRU;
use SMW\EntityCache;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\EmptyBagOStuff;

/**
 * @license GPL-2.0-or-later
 * @since 7.4.0
 */
class EntityCacheFactory {

	/**
	 * An EntityCache of one process. Caches built over the same backend share
	 * its values but not their in-process ones.
	 *
	 * @param BagOStuff $backend
	 * @param float|null &$now Mock clock of the cache and its backend, when given
	 */
	public static function newEntityCache( BagOStuff $backend = new EmptyBagOStuff(), ?float &$now = null ): EntityCache {
		$processCache = new MapCacheLRU( 1000 );

		if ( $now !== null ) {
			$backend->setMockTime( $now );
			$processCache->setMockTime( $now );
		}

		return new EntityCache( $backend, $processCache );
	}

}
