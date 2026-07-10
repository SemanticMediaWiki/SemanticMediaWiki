<?php

declare( strict_types = 1 );

namespace SMW;

use InvalidArgumentException;
use SMW\SQLStore\SQLStore;
use Throwable;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LikeValue;

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
 * Rows whose key starts with {@see self::RESERVED_PREFIX} are treated as
 * out-of-band: they are neither returned by `loadSmwJson` (they are not
 * install state) nor removed by `saveSmwJson`'s full-slice sync-delete. They
 * are read and written one row at a time through {@see self::readValue} /
 * {@see self::writeValue}. The maintenance-script auto-recovery checkpoint
 * (#7030) stores one such row per script identifier, so frequent per-entity
 * writes neither trigger a whole-slice rewrite, clobber install-state keys
 * through a stale snapshot, nor overwrite a concurrent script's checkpoint.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @private
 */
class DatabaseMetaRepo implements SmwJsonRepo {

	/**
	 * Key prefix for rows that live in `smw_meta` but are not part of the
	 * install-state slice managed by `SetupFile`. Kept in sync with
	 * {@see \SMW\Maintenance\AutoRecovery::TOPIC_IDENTIFIER}.
	 */
	private const RESERVED_PREFIX = 'maintenance_script.auto_recovery';

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
			// Reserved rows are out-of-band and must not surface as install
			// state; they are read via `readValue` instead.
			if ( str_starts_with( (string)$row->meta_key, self::RESERVED_PREFIX ) ) {
				continue;
			}
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

			// Never delete reserved rows: they are managed one row at a time
			// via readValue/writeValue, not as part of the install-state slice,
			// so a full or partial slice save must leave them untouched.
			// `LikeValue` escapes the prefix (including its `_`), so this
			// matches only the reserved namespace.
			$deleteBuilder->where(
				$db->expr(
					'meta_key',
					IExpression::NOT_LIKE,
					new LikeValue( self::RESERVED_PREFIX, $db->anyString() )
				)
			);

			// Among the remaining (install-state) rows, delete those the slice
			// no longer carries. When the slice is empty this clause is omitted
			// and every non-reserved row is removed.
			if ( $inputKeys !== [] ) {
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

	/**
	 * Reads a single out-of-band {@see self::RESERVED_PREFIX} row, bypassing
	 * the install-state slice. Returns `null` when the row (or the table) is
	 * absent, mirroring `loadSmwJson`'s missing-table semantics.
	 *
	 * Reads from the primary (unlike `loadSmwJson`) so a resuming maintenance
	 * run sees the checkpoint its previous run wrote: the row is written to the
	 * primary and read exactly once per run, so replication lag must not hide
	 * it and there is no replica-offload benefit to gain.
	 *
	 * @since 7.2.0
	 *
	 * @return mixed
	 */
	public function readValue( string $key ) {
		$this->assertReserved( $key );

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		try {
			$value = $db->newSelectQueryBuilder()
				->select( 'meta_value' )
				->from( SQLStore::META_TABLE )
				->where( [ 'meta_key' => $key ] )
				->caller( __METHOD__ )
				->fetchField();
		} catch ( DBQueryError $e ) {
			if ( !$db->tableExists( SQLStore::META_TABLE, __METHOD__ ) ) {
				return null;
			}
			throw $e;
		}

		if ( $value === false ) {
			return null;
		}

		return $this->decode( $value );
	}

	/**
	 * Upserts a single out-of-band {@see self::RESERVED_PREFIX} row. Unlike
	 * `saveSmwJson` this touches only the one row (no full-slice sync-delete),
	 * so it cannot remove install-state keys or a concurrent script's row.
	 * Silently no-ops when the table does not exist yet, mirroring
	 * `saveSmwJson`.
	 *
	 * @since 7.2.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function writeValue( string $key, $value ): void {
		$this->assertReserved( $key );

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

		if ( !$db->tableExists( SQLStore::META_TABLE, __METHOD__ ) ) {
			return;
		}

		$db->newReplaceQueryBuilder()
			->replaceInto( SQLStore::META_TABLE )
			->uniqueIndexFields( [ 'meta_key' ] )
			->row( [
				'meta_key' => $key,
				'meta_value' => $this->encode( $value ),
			] )
			->caller( __METHOD__ )
			->execute();
	}

	private function assertReserved( string $key ): void {
		if ( !str_starts_with( $key, self::RESERVED_PREFIX ) ) {
			throw new InvalidArgumentException(
				"'$key' is not a reserved meta key; use loadSmwJson/saveSmwJson for install-state keys."
			);
		}
	}

	private function encode( mixed $value ): string {
		return json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function decode( string $value ) {
		return json_decode( $value, true );
	}

}
