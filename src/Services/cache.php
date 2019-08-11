<?php

namespace SMW\Services;

use Onoi\Cache\CacheFactory;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed either via the
 * ServicesFactory or a different factory instance.
 *
 * @license GNU GPL v2
 * @since 3.1
 *
 * @author mwjames
 */
return [

	/**
	 * FixedInMemoryLruCache
	 *
	 * @return callable
	 */
	'FixedInMemoryLruCache' => function( $containerBuilder, $cacheSize = 500 ) {
		return CacheFactory::getInstance()->newFixedInMemoryLruCache( $cacheSize );
	},

];