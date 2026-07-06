<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Maintenance\populateHashField;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\RawSQLValue;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashField {

	use MessageReporterAwareTrait;

	/**
	 * @var ?PopulateHashField
	 */
	private ?populateHashField $populateHashField;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private SQLStore $store,
		?PopulateHashField $populateHashField = null,
	) {
		$this->populateHashField = $populateHashField;
	}

	/**
	 * @since 3.1
	 *
	 * @return int
	 */
	public static function threshold() {
		return PopulateHashField::COUNT_SCRIPT_EXECUTION_THRESHOLD;
	}

	/**
	 * Convert hex-encoded smw_hash values to raw binary.
	 *
	 * Must run BEFORE the column type changes from VARBINARY(40) to
	 * BINARY(20), because the ALTER would truncate 40-byte hex strings.
	 * The LENGTH check distinguishes hex (40) from already-converted
	 * binary (20) and empty values.
	 *
	 * Always runs to completion regardless of row count: this is a
	 * single server-side UPDATE on MySQL/Postgres, and skipping it would
	 * leave 40-byte hex values that the subsequent ALTER TABLE cannot
	 * narrow to BINARY(20) without truncation.
	 *
	 * @since 7.0
	 */
	public function migrateHexHashes(): void {
		$cliMsgFormatter = new CliMsgFormatter();
		$connection = $this->store->getConnection( 'mw.db' );

		$count = (int)$connection->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( SQLStore::ID_TABLE )
			->where( 'LENGTH(smw_hash) = 40' )
			->caller( __METHOD__ )
			->fetchField();

		if ( $count === 0 ) {
			return;
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "... converting hex hashes to binary ...", "(rows) $count", 3 )
		);

		$type = $connection->getType();

		try {
			if ( $type === 'postgres' ) {
				$connection->newUpdateQueryBuilder()
					->update( SQLStore::ID_TABLE )
					->set( [ 'smw_hash' => new RawSQLValue( "decode(smw_hash, 'hex')" ) ] )
					->where( [ 'LENGTH(smw_hash) = 40' ] )
					->caller( __METHOD__ )
					->execute();
			} elseif ( $type === 'sqlite' ) {
				// unhex() requires SQLite 3.38+; fall back to PHP-side conversion
				$this->migrateHexHashesViaPHP( $connection );
			} else {
				$connection->newUpdateQueryBuilder()
					->update( SQLStore::ID_TABLE )
					->set( [ 'smw_hash' => new RawSQLValue( 'UNHEX(smw_hash)' ) ] )
					->where( [ 'LENGTH(smw_hash) = 40' ] )
					->caller( __METHOD__ )
					->execute();
			}
		} catch ( DBError $e ) {
			$this->reportMigrationFailure( $cliMsgFormatter );
			throw $e;
		}
	}

	/**
	 * Surface a recovery hint before the raw `DBError` propagates and aborts
	 * `update.php`. The migration is safe to retry after a partial failure
	 * because the `LENGTH(smw_hash) = 40` predicate skips already-converted
	 * rows — the SQLite per-row fallback in particular is not transactional,
	 * but the idempotent predicate makes that safe.
	 */
	private function reportMigrationFailure( CliMsgFormatter $cliMsgFormatter ): void {
		$text = [
			$cliMsgFormatter->red(
				"\n... hex-to-binary conversion failed; the schema change " .
				"cannot proceed until this UPDATE succeeds."
			),
			"Common causes: lock contention on `smw_object_ids`, insufficient " .
			"disk space, or a restrictive SQL mode. Resolve the underlying " .
			"database error and re-run `update.php` — already-converted rows " .
			"are skipped, so the migration is safe to retry.",
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);
	}

	/**
	 * Row-by-row hex-to-binary conversion for databases without UNHEX().
	 */
	private function migrateHexHashesViaPHP( $connection ): void {
		$rows = $connection->newSelectQueryBuilder()
			->select( [ 'smw_id', 'smw_hash' ] )
			->from( SQLStore::ID_TABLE )
			->where( 'LENGTH(smw_hash) = 40' )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $rows as $row ) {
			$connection->newUpdateQueryBuilder()
				->update( SQLStore::ID_TABLE )
				->set( [ 'smw_hash' => hex2bin( $row->smw_hash ) ] )
				->where( [ 'smw_id' => $row->smw_id ] )
				->caller( __METHOD__ )
				->execute();
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $opts
	 */
	public function check( array $opts = [] ): void {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage( "Checking smw_hash field consistency ...\n" );
		require_once $GLOBALS['smwgMaintenanceDir'] . "/populateHashField.php";

		if ( $this->populateHashField === null ) {
			$this->populateHashField = new PopulateHashField();
		}

		$this->populateHashField->setStore( $this->store );
		$this->populateHashField->setMessageReporter( $this->messageReporter );

		$rows = $this->populateHashField->fetchRows();
		$count = 0;

		if ( $rows !== null ) {
			$count = $rows->numRows();
		}

		if ( $count > self::threshold() ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( "... found missing rows ...", "(rows) $count", 3 )
			);

			$this->messageReporter->reportMessage( "   ... skipping the `smw_hash` field population ...\n" );

			$this->populateHashField->setComplete( false );
		} elseif ( $count != 0 ) {
			$this->populateHashField->populate( $rows );
		} else {
			$this->populateHashField->setComplete( true );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
