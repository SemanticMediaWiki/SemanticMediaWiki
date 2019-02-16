<?php

namespace SMW\Services;

use SMW\Events\InvalidateResultCacheEventListener;
use SMW\Events\InvalidateEntityCacheEventListener;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed either via the
 * ApplicationFactory or a different factory instance.
 *
 * @license GNU GPL v2
 * @since 3.1
 *
 * @author mwjames
 */
return [

	/**
	 * InvalidateResultCacheEventListener
	 *
	 * @return callable
	 */
	'InvalidateResultCacheEventListener' => function( $containerBuilder ) {

		$invalidateResultCacheEventListener = new InvalidateResultCacheEventListener(
			$containerBuilder->singleton( 'CachedQueryResultPrefetcher' )
		);

		return $invalidateResultCacheEventListener;
	},

	/**
	 * InvalidateEntityCacheEventListener
	 *
	 * @return callable
	 */
	'InvalidateEntityCacheEventListener' => function( $containerBuilder ) {

		$invalidateEntityCacheEventListener = new InvalidateEntityCacheEventListener(
			$containerBuilder->singleton( 'EntityCache' )
		);

		return $invalidateEntityCacheEventListener;
	}

];