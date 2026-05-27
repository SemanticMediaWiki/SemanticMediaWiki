<?php

declare( strict_types = 1 );

namespace SMW\Setup;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use SMW\DatabaseMetaRepo;
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
 * The migration is idempotent:
 *  - if `smw_meta` already has any rows it is treated as already migrated
 *    and the callback is a no-op;
 *  - if `.smw.json` is missing (fresh installs, or a re-run after the
 *    file has been renamed) it is also a no-op.
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
		$db = $updater->getDB();

		$existingCount = $db->newSelectQueryBuilder()
			->select( 'meta_key' )
			->from( SQLStore::META_TABLE )
			->caller( __METHOD__ )
			->fetchRowCount();

		if ( $existingCount > 0 ) {
			$updater->output( "...smw_meta already populated; skipping .smw.json migration.\n" );
			return;
		}

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

		$updater->output( '...migrating ' . count( $slice ) . " entries from .smw.json into smw_meta.\n" );

		$repo = new DatabaseMetaRepo(
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
		$repo->saveSmwJson( $configDir, [ $id => $slice ] );

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
