<?php

declare( strict_types = 1 );

namespace SMW\Setup;

use MediaWiki\Installer\DatabaseUpdater;
use SMW\Site;
use SMW\SQLStore\SQLStore;

/**
 * One-shot migration: copies entries from `.smw.json` into the `smw_meta`
 * table, then renames the file to `.smw.json.migrated`.
 *
 * Registered as a serialisable static callback through
 * {@see DatabaseUpdater::addExtensionUpdate}. The callback runs after
 * `Store::setupStore` so the table is guaranteed to exist by the time the
 * migration fires.
 *
 * The migration is additive: rows are inserted with `INSERT IGNORE`, so any
 * keys already written to `smw_meta` by `Store::setupStore` (`upgrade_key`,
 * `maintenance_mode`, `latest_version`, ...) keep their fresh values, while
 * survivor keys from `.smw.json` (`incomplete_tasks`,
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
	public static function run( DatabaseUpdater $updater ): void {
		$configDir = (string)( $GLOBALS['smwgConfigFileDir'] ?? '' );
		$filePath = rtrim( $configDir, '/' ) . '/.smw.json';

		if ( !is_file( $filePath ) ) {
			$updater->output( "...no .smw.json found at {$filePath}; nothing to migrate.\n" );
			return;
		}

		$raw = file_get_contents( $filePath );
		$parsed = json_decode( (string)$raw, true );

		$id = Site::id();
		$slice = is_array( $parsed ) ? ( $parsed[$id] ?? null ) : null;

		if ( !is_array( $slice ) || $slice === [] ) {
			$updater->output( "...no entries for wiki id '{$id}' in .smw.json; skipping.\n" );
			return;
		}

		$updater->output(
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

		$db = $updater->getDB();
		$db->insert( SQLStore::META_TABLE, $rows, __METHOD__, [ 'IGNORE' ] );

		$renamed = $filePath . '.migrated';
		if ( @rename( $filePath, $renamed ) ) {
			$updater->output( "...renamed {$filePath} to {$renamed}.\n" );
		} else {
			$updater->output(
				"...warning: could not rename {$filePath}; please remove or rename it manually.\n"
			);
		}
	}

}
