<?php

namespace SMW\Services;

use Onoi\CallbackContainer\CallbackContainerBuilder;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed either via the
 * ApplicationFactory or a different factory instance.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
return [

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return InvalidateResultCacheEventListener
	 */
	'InvalidateResultCacheEventListener' => static function ( $containerBuilder ) {
		$invalidateResultCacheEventListener = new InvalidateResultCacheEventListener(
			$containerBuilder->singleton( 'ResultCache' )
		);

		return $invalidateResultCacheEventListener;
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return InvalidateEntityCacheEventListener
	 */
	'InvalidateEntityCacheEventListener' => static function ( $containerBuilder ) {
		$invalidateEntityCacheEventListener = new InvalidateEntityCacheEventListener(
			$containerBuilder->singleton( 'EntityCache' )
		);

		return $invalidateEntityCacheEventListener;
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return InvalidatePropertySpecificationLookupCacheEventListener
	 */
	'InvalidatePropertySpecificationLookupCacheEventListener' => static function ( $containerBuilder ) {
		$invalidatePropertySpecificationLookupCacheEventListener = new InvalidatePropertySpecificationLookupCacheEventListener(
			$containerBuilder->singleton( 'PropertySpecificationLookup' )
		);

		return $invalidatePropertySpecificationLookupCacheEventListener;
	}

];
