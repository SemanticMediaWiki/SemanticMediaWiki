<?php

namespace SMW\Setup;

/**
 * Registration-time bootstrap for SMW config defaults that cannot be
 * expressed as static JSON in extension.json — chiefly settings whose
 * values derive from SMW feature-flag constants (SMW_FACTBOX_*, SMW_DV_*,
 * etc.) or class constants.
 *
 * Called from SemanticMediaWiki::initExtension() after extension.json
 * config defaults have been seeded into $GLOBALS by ExtensionRegistry.
 * Implementations must use provide-default semantics: write only when the
 * key is not already set, so user values from LocalSettings.php win.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigBootstrap {

	/**
	 * @since 7.0.0
	 */
	public static function seedComputedDefaults(): void {
		// Filled in PR 2 (SMW feature-flag constants) and PR 4 (class constants).
		// Writes directly to $GLOBALS using provide-default semantics
		// (only sets keys that aren't already user-defined).
	}

}
