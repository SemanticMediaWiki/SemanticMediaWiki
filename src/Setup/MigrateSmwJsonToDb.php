<?php

declare( strict_types = 1 );

namespace SMW\Setup;

use Onoi\MessageReporter\MessageReporter;

/**
 * One-shot migration: marks a legacy `.smw.json` consumed by renaming it to
 * `.smw.json.migrated`.
 *
 * Invoked from {@see \SMW\SQLStore\Installer::install} at the end of the
 * install pipeline. Data transfer is not the migration's job: when a
 * pre-7.0 `.smw.json` is present, {@see \SMW\SetupFile::loadSchema} falls
 * back to reading it and hydrates `$GLOBALS['smw.json']` early in the
 * request. The install's many `SetupFile` writes then persist the merged
 * state into `smw_meta` via the normal `write` -> `saveSmwJson` path. By
 * the time this callback runs, `smw_meta` already reflects the user's
 * legacy state plus the fresh schema-state keys written by the installer.
 *
 * The rename here marks the file consumed so that subsequent loadSchema
 * calls fall through to `smw_meta` instead of re-hydrating from disk.
 *
 * The callback is idempotent: a missing file is a no-op (fresh installs,
 * or any run after the first successful migration).
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @private
 */
class MigrateSmwJsonToDb {

	/**
	 * @since 7.0.0
	 */
	public static function run( MessageReporter $reporter ): void {
		$configDir = (string)( $GLOBALS['smwgConfigFileDir'] ?? '' );
		$filePath = rtrim( $configDir, '/' ) . '/.smw.json';

		if ( !is_file( $filePath ) ) {
			return;
		}

		// Sanity-check the file before consuming it. If the file is
		// unreadable or contains invalid JSON, `SetupFile::loadSchema`'s
		// fallback also failed silently and the user's legacy state was NOT
		// hydrated into `smw_meta` during this install. Renaming the file
		// now would lose that data permanently. Warn loudly and bail so the
		// admin can fix the file and re-run the upgrade.
		$raw = file_get_contents( $filePath );
		if ( $raw === false ) {
			$reporter->reportMessage(
				"...warning: cannot read {$filePath}; skipping rename so a future"
				. " upgrade can retry.\n"
			);
			return;
		}
		$parsed = json_decode( $raw, true );
		if ( $parsed === null && json_last_error() !== JSON_ERROR_NONE ) {
			$reporter->reportMessage(
				"...warning: {$filePath} contains invalid JSON ("
				. json_last_error_msg() . "); your legacy install-state was NOT"
				. " migrated. Fix the file and re-run setupStore.php or update.php.\n"
			);
			return;
		}
		unset( $parsed );

		$renamed = $filePath . '.migrated';
		if ( @rename( $filePath, $renamed ) ) {
			$reporter->reportMessage(
				"...migrated legacy {$filePath} to {$renamed};"
				. " state is now persisted in `smw_meta`.\n"
			);
		} else {
			$reporter->reportMessage(
				"...warning: could not rename {$filePath}; please remove or rename it manually.\n"
			);
		}

		// Surface a deprecation notice for admins who explicitly set the
		// legacy config path; the setting has no further effect after this
		// migration and can be removed from LocalSettings.php. The
		// extension.json default ("") resolves to the SMW root via
		// path: true, so compare resolved paths to distinguish a
		// customised value from the default.
		$extensionRoot = realpath( dirname( __DIR__, 2 ) );
		$resolvedConfigDir = $configDir !== '' ? realpath( $configDir ) : false;
		if ( $resolvedConfigDir !== false && $resolvedConfigDir !== $extensionRoot ) {
			$reporter->reportMessage(
				"...notice: \$smwgConfigFileDir is deprecated (since 7.0.0) and will be"
				. " removed in 8.0.0. It is safe to remove from LocalSettings.php now.\n"
			);
		}
	}

}
