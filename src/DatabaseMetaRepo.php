<?php

declare( strict_types = 1 );

namespace SMW;

use SMW\SQLStore\SQLStore;
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
			if ( $this->isMissingTable( $e ) ) {
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
	 * @since 7.0.0
	 */
	public function saveSmwJson( string $configDirectory, array $smwJson ): void {
		$id = Site::id();
		$entries = $smwJson[ $id ] ?? [];

		if ( $entries === [] ) {
			return;
		}

		$db = $this->loadBalancer->getConnection( DB_PRIMARY );

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
	}

	private function encode( mixed $value ): string {
		return json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function decode( string $value ) {
		return json_decode( $value, true );
	}

	/**
	 * Recognises the table-doesn't-exist condition across the database
	 * backends MediaWiki supports. SQLSTATE 42S02 covers MySQL/MariaDB and
	 * SQLite via PDO; SQLite also surfaces "no such table" in the message;
	 * Postgres surfaces "does not exist".
	 */
	private function isMissingTable( DBQueryError $e ): bool {
		$message = $e->getMessage();

		return str_contains( $message, '42S02' )
			|| stripos( $message, 'no such table' ) !== false
			|| stripos( $message, 'does not exist' ) !== false;
	}

}
