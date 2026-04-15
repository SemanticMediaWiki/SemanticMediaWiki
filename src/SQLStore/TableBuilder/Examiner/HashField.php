<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Maintenance\populateHashField;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;

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
	 * @since 7.0
	 */
	public function migrateHexHashes(): void {
		$cliMsgFormatter = new CliMsgFormatter();
		$connection = $this->store->getConnection( 'mw.db' );

		$count = (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'COUNT(*)',
			'LENGTH(smw_hash) = 40',
			__METHOD__
		);

		if ( $count === 0 ) {
			return;
		}

		if ( $count > self::threshold() ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols(
					"... hex hashes to convert ...",
					"(rows) $count — run populateHashField.php --force-update",
					3
				)
			);
			return;
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "... converting hex hashes to binary ...", "(rows) $count", 3 )
		);

		$table = $connection->tableName( SQLStore::ID_TABLE );
		$type = $connection->getType();

		if ( $type === 'postgres' ) {
			$connection->query(
				"UPDATE $table SET smw_hash = decode(smw_hash, 'hex') WHERE LENGTH(smw_hash) = 40",
				__METHOD__
			);
		} elseif ( $type === 'sqlite' ) {
			// unhex() requires SQLite 3.38+; fall back to PHP-side conversion
			$this->migrateHexHashesViaPHP( $connection );
		} else {
			$connection->query(
				"UPDATE $table SET smw_hash = UNHEX(smw_hash) WHERE LENGTH(smw_hash) = 40",
				__METHOD__
			);
		}
	}

	/**
	 * Row-by-row hex-to-binary conversion for databases without UNHEX().
	 */
	private function migrateHexHashesViaPHP( $connection ): void {
		$rows = $connection->select(
			SQLStore::ID_TABLE,
			[ 'smw_id', 'smw_hash' ],
			'LENGTH(smw_hash) = 40',
			__METHOD__
		);

		foreach ( $rows as $row ) {
			$connection->update(
				SQLStore::ID_TABLE,
				[ 'smw_hash' => hex2bin( $row->smw_hash ) ],
				[ 'smw_id' => $row->smw_id ],
				__METHOD__
			);
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
