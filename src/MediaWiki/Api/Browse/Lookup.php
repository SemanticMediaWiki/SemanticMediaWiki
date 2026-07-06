<?php

namespace SMW\MediaWiki\Api\Browse;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
abstract class Lookup {

	/**
	 * @since 3.0
	 *
	 * @return string|int
	 */
	abstract public function getVersion();

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	// phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle
	abstract public function lookup( array $parameters );

	/**
	 * Decides whether the request opts into cursor pagination. Cursor mode
	 * is triggered by *presence* of the `cursor` key in the request payload
	 * (any value, including 0), not by truthiness. The presence-of-key form
	 * is unambiguous for a JSON-payload API where clients explicitly opt
	 * in: `cursor=0` is the canonical "first page in cursor mode" value
	 * and a value > 0 is the previous response's `query-continue-cursor`.
	 *
	 * Absence of the key keeps the legacy OFFSET path for backward
	 * compatibility with clients that follow `query-continue-offset`.
	 *
	 * @since 7.0.0
	 *
	 * @param array $parameters The decoded `params` payload from the
	 *   `smwbrowse` API request.
	 */
	public static function shouldUseCursorMode( array $parameters ): bool {
		return array_key_exists( 'cursor', $parameters );
	}

}
