<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
abstract class Task {

	const CACHE_NAMESPACE = 'smw:api:task';

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function makeCacheKey( $key ) {
		return smwfCacheKey( self::CACHE_NAMESPACE, [ $key ] );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 */
	abstract public function process( array $parameters );

}
