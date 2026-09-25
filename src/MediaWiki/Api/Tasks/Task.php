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
	 * Serialized subject the caller must be authorized to edit before this
	 * task runs, or null when the task gates on the global
	 * getRequiredPermission() right instead.
	 *
	 * A task that acts on a caller-supplied page returns that page here so the
	 * `smwtask` module authorizes the caller against the specific page, rather
	 * than a wiki-wide right an unprivileged (including anonymous) caller may
	 * hold regardless of the page it names.
	 *
	 * @since 7.3.1
	 */
	public function getAuthorizationSubject( array $parameters ): ?string {
		return null;
	}

	/**
	 * Whether the caller-supplied parameters name only work this task may
	 * perform on the caller's behalf. The `smwtask` module refuses the request
	 * when this returns false, so a task can reject parameters it must not act
	 * on regardless of the caller's authority over the subject.
	 *
	 * @since 7.3.1
	 */
	public function requestedWorkIsPermitted( array $parameters ): bool {
		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 */
	abstract public function process( array $parameters );

}
