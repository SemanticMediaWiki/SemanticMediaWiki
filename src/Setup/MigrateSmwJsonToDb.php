<?php

declare( strict_types = 1 );

namespace SMW\Setup;

use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\MessageReporter;
use SMW\Site;
use SMW\SQLStore\SQLStore;

/**
 * One-shot migration: copies entries from `.smw.json` into the `smw_meta`
 * table, then renames the file to `.smw.json.migrated`.
 *
 * Invoked from {@see \SMW\SQLStore\Installer::install} after `finalize()`,
 * so the smw_meta table is guaranteed to exist and the fresh schema-state
 * keys (`upgrade_key`, `maintenance_mode`, `latest_version`) have already
 * been written. Running through `Installer::install` covers both common
 * upgrade entry points: `update.php` (via the `setupStore` extension
 * update) and `setupStore.php` (direct call into the installer).
 *
 * The migration is additive: rows are inserted with `INSERT IGNORE`, so any
 * keys already written to `smw_meta` by the installer keep their fresh
 * values, while survivor keys from `.smw.json` (`incomplete_tasks`,
 * `last_optimization_run`, ...) are added without overwriting anything.
 *
 * Idempotency is keyed on file presence: a successful run renames
 * `.smw.json` to `.smw.json.migrated`, so any subsequent run finds no source
 * file and is a no-op. Fresh installs likewise have no `.smw.json` and skip.
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
		$db = MediaWikiServices::getInstance()
			->getDBLoadBalancer()
			->getConnection( DB_PRIMARY );

		$configDir = (string)( $GLOBALS['smwgConfigFileDir'] ?? '' );
		$filePath = rtrim( $configDir, '/' ) . '/.smw.json';

		if ( !is_file( $filePath ) ) {
			$reporter->reportMessage( "...no .smw.json found at {$filePath}; nothing to migrate.\n" );
			return;
		}

		$raw = file_get_contents( $filePath );
		$parsed = json_decode( (string)$raw, true );

		$id = Site::id();
		$slice = is_array( $parsed ) ? ( $parsed[$id] ?? null ) : null;

		if ( !is_array( $slice ) || $slice === [] ) {
			$reporter->reportMessage( "...no entries for wiki id '{$id}' in .smw.json; skipping.\n" );
			return;
		}

		$reporter->reportMessage(
			'...migrating ' . count( $slice )
			. " entries from .smw.json into smw_meta (existing keys skipped via INSERT IGNORE).\n"
		);

		$rows = [];
		foreach ( $slice as $key => $value ) {
			$rows[] = [
				'meta_key' => (string)$key,
				'meta_value' => json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			];
		}

		$db->insert( SQLStore::META_TABLE, $rows, __METHOD__, [ 'IGNORE' ] );

		$renamed = $filePath . '.migrated';
		if ( @rename( $filePath, $renamed ) ) {
			$reporter->reportMessage( "...renamed {$filePath} to {$renamed}.\n" );
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
