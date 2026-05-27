<?php

namespace SMW;

/**
 * Mockable wrapper around {@link Site::isReady()}.
 *
 * Handlers that gate their behaviour on whether MediaWiki has finished booting
 * (`$GLOBALS['wgFullyInitialised']`, `MEDIAWIKI_INSTALL`, `MW_NO_SESSION`) used
 * to read that state through `Site::isReady()` directly, forcing unit tests to
 * mutate `$GLOBALS['wgFullyInitialised']` to exercise the not-ready branch.
 * Injecting this wrapper instead lets tests substitute a mock that returns the
 * desired boolean without touching globals.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SiteReadiness {

	/**
	 * @since 7.0.0
	 */
	public function isReady(): bool {
		return Site::isReady();
	}

}
