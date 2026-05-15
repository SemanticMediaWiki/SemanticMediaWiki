<?php

namespace SMW\Tests\Utils;

/**
 * Runs a callable with `E_USER_DEPRECATED` swallowed at the PHP error
 * handler level. Used by unit tests that deliberately exercise SMW's
 * legacy integer-constant config form (e.g. via
 * {@see \SMW\Setup\LegacyConstantNormalizer}) so the resulting
 * `wfDeprecatedMsg()` call does not leak into CI stderr while the test's
 * own behavioural assertions still cover the deprecation contract.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
trait SilenceUserDeprecationTrait {

	protected function withSilencedUserDeprecation( callable $fn ) {
		$previous = set_error_handler(
			static fn ( int $severity ) => $severity === E_USER_DEPRECATED,
			E_USER_DEPRECATED
		);
		try {
			return $fn();
		} finally {
			restore_error_handler();
		}
	}
}
