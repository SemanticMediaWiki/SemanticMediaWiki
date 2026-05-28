<?php

declare( strict_types = 1 );

namespace SMW\Setup;

use Onoi\MessageReporter\MessageReporter;
use SMW\Site;

/**
 * One-shot migration: consumes a legacy `.smw.json` slice for the current
 * wiki by removing it from the file, leaving any other wiki slices intact.
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
 * The file is keyed by `Site::id()` at the top level, so a shared-codebase
 * multi-wiki setup may have several wiki slices in the same file. This
 * callback removes only the slice for the wiki being upgraded and
 * rewrites the file. When the last slice is consumed, the file is
 * renamed to `.smw.json.migrated` as a tombstone.
 *
 * The callback is idempotent: a missing file or a file with no slice for
 * the current wiki is a no-op.
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

		// `SetupFile::loadSchema`'s fallback throws on an unreadable or
		// invalid file before the installer ever writes, so by the time
		// this callback runs the file is guaranteed parseable. We re-read
		// here only to remove the current wiki's slice.
		$raw = file_get_contents( $filePath );
		if ( $raw === false ) {
			$reporter->reportMessage(
				"...warning: cannot read {$filePath}; leaving in place for manual cleanup.\n"
			);
			return;
		}
		$parsed = json_decode( $raw, true );
		if ( !is_array( $parsed ) ) {
			$reporter->reportMessage(
				"...warning: {$filePath} is not a JSON object; leaving in place for manual cleanup.\n"
			);
			return;
		}

		$id = Site::id();
		if ( !array_key_exists( $id, $parsed ) ) {
			// No slice for this wiki; nothing to consume. Leave the file
			// alone so other wikis sharing this config can still migrate.
			return;
		}

		unset( $parsed[$id] );

		if ( $parsed === [] ) {
			// Last slice consumed: rename as a tombstone so future
			// `loadSchema` calls short-circuit at the `is_file` check.
			$renamed = $filePath . '.migrated';
			if ( @rename( $filePath, $renamed ) ) {
				$reporter->reportMessage(
					"...migrated legacy {$filePath} to {$renamed};"
					. " all wiki slices have been consumed.\n"
				);
			} else {
				$reporter->reportMessage(
					"...warning: could not rename {$filePath}; please remove it manually.\n"
				);
			}
		} else {
			$rewritten = file_put_contents(
				$filePath,
				json_encode( $parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			);
			if ( $rewritten === false ) {
				$reporter->reportMessage(
					"...warning: could not rewrite {$filePath} without the slice for '{$id}';"
					. " other wikis sharing this file may see stale data.\n"
				);
			} else {
				$reporter->reportMessage(
					"...consumed slice for wiki id '{$id}' from {$filePath};"
					. " " . count( $parsed ) . " other wiki slice(s) remain.\n"
				);
			}
		}

		// Surface a deprecation notice for admins who explicitly set the
		// legacy config path; the setting has no further effect after the
		// last slice is consumed and can be removed from LocalSettings.php.
		// The extension.json default ("") resolves to the SMW root via
		// path: true, so compare resolved paths to distinguish a
		// customised value from the default.
		$extensionRoot = realpath( dirname( __DIR__, 2 ) );
		$resolvedConfigDir = $configDir !== '' ? realpath( $configDir ) : false;
		if ( $resolvedConfigDir !== false && $resolvedConfigDir !== $extensionRoot ) {
			$reporter->reportMessage(
				"...notice: \$smwgConfigFileDir is deprecated (since 7.0.0) and will be"
				. " removed in 8.0.0. It is safe to remove from LocalSettings.php once"
				. " all wiki slices have been migrated.\n"
			);
		}
	}

}
