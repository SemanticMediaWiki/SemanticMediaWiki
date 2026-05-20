<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Onoi\Cache\Cache;
use SMW\CacheFactory;
use SMW\EntityCache;
use SMW\MediaWiki\JobQueue;
use SMW\Services\ServicesFactory;
use SMW\Settings;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\Logger;

/**
 * Global wiring file for SMW services registered on MediaWiki's global
 * `ServiceContainer`.
 *
 * Spike: this file is the authoritative wiring location for the five services
 * that are migrated out of SMW's private `ServiceContainer` (Store, Settings,
 * Cache, EntityCache, JobQueue) and into the global container.
 *
 * Each callback constructs the service directly. Dependencies on other
 * spike-globalised services are resolved via `$services->getService('SMW.X')`
 * so the global container drives those lookups; dependencies on MediaWiki-core
 * services are resolved via `$services->getXxx()`. Callbacks MUST NOT call
 * `ServicesFactory::getInstance()->getX()` for any of the five services
 * registered in this file: `ServicesFactory::getX()` is rewritten to proxy
 * back to the global container, so doing so would recurse infinitely.
 *
 * @codeCoverageIgnore
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
return [

	'SMW.Settings' => static function ( MediaWikiServices $services ): Settings {
		$settings = new Settings();

		// HookDispatcher is still on SMW's private container; pulling it from
		// ServicesFactory is safe because ServicesFactory::getHookDispatcher()
		// does NOT proxy back to the global container.
		$settings->setHookDispatcher(
			ServicesFactory::getInstance()->getHookDispatcher()
		);

		$settings->loadFromGlobals();

		return $settings;
	},

	'SMW.Store' => static function ( MediaWikiServices $services ): Store {
		$settings = $services->getService( 'SMW.Settings' );

		$store = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::getStore( $store );

		$configs = [
			'smwgDefaultStore',
			'smwgAutoRefreshSubject',
			'smwgEnableUpdateJobs',
			'smwgQEqualitySupport',
			'smwgElasticsearchConfig',
		];

		foreach ( $configs as $config ) {
			$instance->setOption( $config, $settings->get( $config ) );
		}

		$instance->setLogger(
			new Logger( LoggerFactory::getInstance( 'smw' ), Logger::ROLE_DEVELOPER )
		);

		return $instance;
	},

	'SMW.Cache' => static function ( MediaWikiServices $services ): Cache {
		// Mirror ServicesFactory::getCache() default-path behaviour: build a
		// MediaWikiCompositeCache for the global $smwgMainCacheType. Callers
		// that need a non-default cache type still go through
		// CacheFactory::newMediaWikiCompositeCache() directly.
		return ( new CacheFactory() )->newMediaWikiCompositeCache();
	},

	'SMW.EntityCache' => static function ( MediaWikiServices $services ): EntityCache {
		return new EntityCache(
			$services->getService( 'SMW.Cache' )
		);
	},

	'SMW.JobQueue' => static function ( MediaWikiServices $services ): JobQueue {
		// JobQueueGroup is still on SMW's private container and reading it via
		// ServicesFactory honours the testOverrides map, which is required for
		// tests that swap JobQueueGroup (e.g. ChangePropagationNotifierTest).
		return new JobQueue(
			ServicesFactory::getInstance()->getJobQueueGroup()
		);
	},

];
