<?php

namespace SMW\MediaWiki\Api\Tasks;

/**
 * @license GPL-2.0-or-later
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
	 * Right a user must hold to run this task through the `smwtask` API module.
	 *
	 * Defaults to `smw-admin`, so tasks are administrator-only unless they
	 * explicitly opt into a lower privilege. Tasks invoked on behalf of
	 * ordinary users (post-edit updates, page-view indicators) override this.
	 *
	 * @since 7.3.0
	 */
	public function getRequiredPermission(): string {
		return 'smw-admin';
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 */
	abstract public function process( array $parameters );

}
