<?php

declare( strict_types = 1 );

namespace SMW;

use SMW\SQLStore\SQLStore;
use Throwable;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * `SmwJsonRepo` implementation backed by the `smw_meta` table.
 *
 * Translates between the per-key row shape (`meta_key`, `meta_value`) and the
 * `[ Site::id() => [ key => value, ... ] ]` array shape that `SetupFile`
 * operates on. Mirrors the file-not-found semantics of
 * `FileSystemSmwJsonRepo::loadSmwJson`: when the underlying table is missing
 * (e.g. before `update.php`/`setupStore.php` has run), `loadSmwJson` returns
 * `null` rather than throwing.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @private
 */
class DatabaseMetaRepo implements SmwJsonRepo {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly ILoadBalancer $loadBalancer
	) {
	}

	/**
	 * The `$configDirectory` parameter is part of the `SmwJsonRepo` contract
	 * for filesystem-backed repos and is irrelevant here.
	 *
	 * @since 7.0.0
	 */
	public function loadSmwJson( string $configDirectory ): ?array {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		try {
			$rows = $db->newSelectQueryBuilder()
				->select( [ 'meta_key', 'meta_value' ] )
				->from( SQLStore::META_TABLE )
				->caller( __METHOD__ )
				->fetchResultSet();
		} catch ( DBQueryError $e ) {
			// `tableExists` is the source of truth across MySQL/MariaDB,
			// SQLite, and Postgres. Substring-matching the error message
			// is brittle (e.g. MySQL says "Table 'x' doesn't exist", with
			// the contracted form). Only call it when the SELECT has
			// failed, so the happy path stays one query.
			if ( !$db->tableExists( SQLStore::META_TABLE, __METHOD__ ) ) {
				return null;
			}
			throw $e;
		}

		$entries = [];
		foreach ( $rows as $row ) {
			$entries[ $row->meta_key ] = $this->decode( $row->meta_value );
		}

		if ( $entries === [] ) {
			return null;
		}

		return [ Site::id() => $entries ];
	}

	/**
	 * Synchronises the `smw_meta` rows with the per-wiki slice in `$smwJson`:
	 * keys present in the input are upserted, keys present in the table but
	 * absent from the input are deleted. This mirrors the whole-file rewrite
	 * semantics of {@see FileSystemSmwJsonRepo::saveSmwJson} so that
	 * `SetupFile::remove` / `SetupFile::reset` (which `unset` keys before
	 * saving) propagate to the database.
	 *
	 * @since 7.0.0
	 */
	public function saveSmwJson( string $configDirectory, array $smwJson ): void {
		$id = Site::id();
		$entries = $smwJson[ $id ] ?? [];

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		// `Installer::install` calls `SetupFile::setMaintenanceMode(true)`
		// before it has created the SMW tables (the first checkpoint write
		// fires before `TableBuilder::create` runs). With file-backed
		// storage this wrote to disk and the missing-table problem did not
		// arise; with `smw_meta` we silently drop the write and let the
		// next checkpoint (issued after tables exist) persist the state.
		if ( !$db->tableExists( SQLStore::META_TABLE, __METHOD__ ) ) {
			return;
		}

		// Wrap the sync-delete + per-key writes in a single atomic section
		// so a partial failure cannot leave `smw_meta` half-updated (which
		// would silently flip install-state checks).
		$db->startAtomic( __METHOD__ );

		try {
			$inputKeys = array_map( 'strval', array_keys( $entries ) );

			$deleteBuilder = $db->newDeleteQueryBuilder()
				->deleteFrom( SQLStore::META_TABLE )
				->caller( __METHOD__ );

			if ( $inputKeys === [] ) {
				$deleteBuilder->where( '1=1' );
			} else {
				$deleteBuilder->where( $db->expr( 'meta_key', '!=', $inputKeys ) );
			}

			$deleteBuilder->execute();

			foreach ( $entries as $key => $value ) {
				if ( $value === null ) {
					$db->newDeleteQueryBuilder()
						->deleteFrom( SQLStore::META_TABLE )
						->where( [ 'meta_key' => (string)$key ] )
						->caller( __METHOD__ )
						->execute();
					continue;
				}

				$db->newReplaceQueryBuilder()
					->replaceInto( SQLStore::META_TABLE )
					->uniqueIndexFields( [ 'meta_key' ] )
					->row( [
						'meta_key' => (string)$key,
						'meta_value' => $this->encode( $value ),
					] )
					->caller( __METHOD__ )
					->execute();
			}

			$db->endAtomic( __METHOD__ );
		} catch ( Throwable $e ) {
			$db->cancelAtomic( __METHOD__ );
			throw $e;
		}
	}

	private function encode( mixed $value ): string {
		return json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function decode( string $value ) {
		return json_decode( $value, true );
	}

}
