<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use RuntimeException;
use SMW\Maintenance\populateHashField;
use SMW\MediaWiki\Connection\Database;
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
	 * Number of `smw_object_ids` rows one conversion statement covers.
	 *
	 * Small enough that a single statement finishes in about a second even
	 * on a wiki with millions of entities, which is what keeps the migration
	 * clear of connection and query timeouts.
	 *
	 * @since 7.2.1
	 */
	public const CONVERSION_BATCH_SIZE = 5000;

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
	 * Always runs to completion regardless of row count: skipping it would
	 * leave 40-byte hex values that the subsequent ALTER TABLE cannot
	 * narrow to BINARY(20) without truncation.
	 *
	 * @since 7.0
	 */
	public function migrateHexHashes(): void {
		$cliMsgFormatter = new CliMsgFormatter();
		$connection = $this->store->getConnection( 'mw.db' );

		[ 'count' => $count, 'first' => $first, 'last' => $last ] = $this->hexRowBounds( $connection );

		if ( $count === 0 ) {
			return;
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "... converting hex hashes to binary ...", "(rows) $count", 3 )
		);

		try {
			$this->convertInBatches( $connection, $cliMsgFormatter, $first, $last );
		} catch ( DBError $e ) {
			$this->reportMigrationFailure( $cliMsgFormatter );
			throw $e;
		}

		$this->messageReporter->reportMessage( "\n" );

		$this->assertNoHexRowsRemain( $connection, $cliMsgFormatter );
	}

	/**
	 * How many rows still hold a hex hash, and the `smw_id` range they sit
	 * in. One scan answers all three: the count drives the report and the
	 * completeness check, the range bounds the walk. Taking the bounds from
	 * the hex rows rather than from the table as a whole keeps a resumed run
	 * proportional to what is left instead of to the whole id space, and it
	 * skips id ranges that never held a hex hash.
	 *
	 * Reads the primary. A lagging replica would under-report and let the
	 * schema change run against rows that are still hex, and would fail the
	 * post-conversion check on an upgrade that actually succeeded.
	 *
	 * `LENGTH(smw_hash) = 40` cannot use an index, so this scans the whole
	 * `smw_hash` index. It is the one part of the migration whose duration
	 * still grows with the table, but it is a read: losing the connection
	 * here costs no converted rows.
	 *
	 * @return array{count:int,first:int,last:int} `first`/`last` are 0 when
	 *   nothing is left to convert
	 */
	private function hexRowBounds( Database $connection ): array {
		$row = $connection->newPrimarySelectQueryBuilder()
			->select( [ 'rows' => 'COUNT(*)', 'first' => 'MIN(smw_id)', 'last' => 'MAX(smw_id)' ] )
			->from( SQLStore::ID_TABLE )
			->where( 'LENGTH(smw_hash) = 40' )
			->caller( __METHOD__ )
			->fetchRow();

		return [
			'count' => (int)$row->rows,
			'first' => (int)$row->first,
			'last' => (int)$row->last,
		];
	}

	/**
	 * Before batching, "every hex row was converted" held by construction:
	 * one unbounded statement could not miss a row. It now depends on the
	 * `smw_id` walk having covered them all. The caller narrows the column
	 * to `BINARY(20)` immediately afterwards, which truncates whatever is
	 * left, so confirm the conversion really is complete and stop the
	 * upgrade with an explanation if it is not.
	 */
	private function assertNoHexRowsRemain( Database $connection, CliMsgFormatter $cliMsgFormatter ): void {
		$remaining = $this->hexRowBounds( $connection )['count'];

		if ( $remaining === 0 ) {
			return;
		}

		$text = [
			$cliMsgFormatter->red(
				"... $remaining row(s) still hold a hex `smw_hash` after the conversion."
			),
			"The schema change that follows would truncate them, so the upgrade " .
			"stops here. This happens when rows are written to `smw_object_ids` " .
			"by an older Semantic MediaWiki while `update.php` runs. Make sure no " .
			"other process is writing to the wiki and re-run `update.php`: rows " .
			"converted so far are already committed and are skipped.",
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		throw new RuntimeException(
			"$remaining `smw_object_ids` row(s) still hold a hex `smw_hash`; " .
			"refusing to narrow the column to BINARY(20)."
		);
	}

	/**
	 * Walks the ID table in `smw_id` ranges, converting one range per
	 * statement and committing after each.
	 *
	 * A single whole-table `UPDATE` rewrites every row and every `smw_hash`
	 * index entry in one transaction, which on a wiki with over a million
	 * entities runs for minutes. Anything that drops the connection in that
	 * window (a proxy timeout, a query killer, the server running out of
	 * memory) discards the entire conversion, so the next `update.php`
	 * starts over and fails the same way. Bounded ranges keep each statement
	 * short and let a failed run resume: the range comes from the rows that
	 * still hold a hex hash, so a resumed run neither revisits the ids an
	 * earlier one finished nor, thanks to the `LENGTH(smw_hash) = 40`
	 * predicate, reconverts a row it happens to meet again.
	 */
	private function convertInBatches(
		Database $connection,
		CliMsgFormatter $cliMsgFormatter,
		int $first,
		int $last
	): void {
		$type = $connection->getType();

		// unhex() requires SQLite 3.38+; fall back to PHP-side conversion
		$convertsInPHP = $type === 'sqlite';

		$expression = $type === 'postgres'
			? new RawSQLValue( "decode(smw_hash, 'hex')" )
			: new RawSQLValue( 'UNHEX(smw_hash)' );

		// Without a ticket `commitAndWaitForReplication` is a no-op. Every
		// statement still autocommits under `update.php`, but a caller that
		// holds an open transaction (the web-based updater) would collect the
		// whole walk into one transaction again, so say so rather than
		// silently losing the property this migration depends on.
		$ticket = $connection->getEmptyTransactionTicket( __METHOD__ );

		if ( $ticket === null ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols(
					"... cannot commit per batch, a transaction is already open ...",
					"(warning)",
					3
				)
			);
		}

		$span = $last - $first + 1;

		for ( $start = $first; $start <= $last; $start += self::CONVERSION_BATCH_SIZE ) {
			$end = min( $start + self::CONVERSION_BATCH_SIZE - 1, $last );

			if ( $convertsInPHP ) {
				$this->convertRangeViaPHP( $connection, $start, $end );
			} else {
				$this->convertRange( $connection, $expression, $start, $end );
			}

			$connection->commitAndWaitForReplication( __METHOD__, $ticket );

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoColsOverride(
					"... converting hex hashes to binary ...",
					// Percentage over the range being walked, so it does not
					// start near 100% when the remaining rows sit at high ids.
					$cliMsgFormatter->progressCompact( $end - $first + 1, $span, $end, $last ),
					3
				)
			);
		}
	}

	private function convertRange( Database $connection, RawSQLValue $expression, int $start, int $end ): void {
		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [ 'smw_hash' => $expression ] )
			->where( [ 'LENGTH(smw_hash) = 40' ] )
			->andWhere( $connection->expr( 'smw_id', '>=', $start ) )
			->andWhere( $connection->expr( 'smw_id', '<=', $end ) )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * Row-by-row hex-to-binary conversion for databases without UNHEX(),
	 * bounded to one `smw_id` range so the result set stays small.
	 */
	private function convertRangeViaPHP( Database $connection, int $start, int $end ): void {
		$rows = $connection->newPrimarySelectQueryBuilder()
			->select( [ 'smw_id', 'smw_hash' ] )
			->from( SQLStore::ID_TABLE )
			->where( 'LENGTH(smw_hash) = 40' )
			->andWhere( $connection->expr( 'smw_id', '>=', $start ) )
			->andWhere( $connection->expr( 'smw_id', '<=', $end ) )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $rows as $row ) {
			$connection->newUpdateQueryBuilder()
				->update( SQLStore::ID_TABLE )
				->set( [ 'smw_hash' => hex2bin( (string)$row->smw_hash ) ] )
				->where( [ 'smw_id' => $row->smw_id ] )
				->caller( __METHOD__ )
				->execute();
		}
	}

	/**
	 * Surface a recovery hint before the raw `DBError` propagates and aborts
	 * `update.php`. The migration is safe to retry after a partial failure
	 * because the `LENGTH(smw_hash) = 40` predicate skips already-converted
	 * rows, and each batch is committed as it completes.
	 */
	private function reportMigrationFailure( CliMsgFormatter $cliMsgFormatter ): void {
		$text = [
			$cliMsgFormatter->red(
				"\n... hex-to-binary conversion failed; the schema change " .
				"cannot proceed until the conversion completes."
			),
			"Common causes: a dropped database connection, lock contention on " .
			"`smw_object_ids`, insufficient disk space, or a restrictive SQL " .
			"mode. Resolve the underlying database error and re-run " .
			"`update.php`: rows converted so far are already committed and " .
			"are skipped, so the migration resumes where it stopped.",
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);
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
