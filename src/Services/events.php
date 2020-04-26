<?php

namespace SMW\Services;

use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;

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
			$containerBuilder->singleton( 'ResultCache' )
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
	},

	/**
	 * InvalidatePropertySpecificationLookupCacheEventListener
	 *
	 * @return callable
	 */
	'InvalidatePropertySpecificationLookupCacheEventListener' => function( $containerBuilder ) {

		$invalidatePropertySpecificationLookupCacheEventListener = new InvalidatePropertySpecificationLookupCacheEventListener(
			$containerBuilder->singleton( 'PropertySpecificationLookup' )
		);

		return $invalidatePropertySpecificationLookupCacheEventListener;
	}

];
