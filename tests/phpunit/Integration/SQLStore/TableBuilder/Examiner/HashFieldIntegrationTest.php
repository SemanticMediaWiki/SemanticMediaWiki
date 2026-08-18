<?php

namespace SMW\Tests\Integration\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * `HashField::migrateHexHashes` converts every hex `smw_hash` to raw binary
 * just before `update.php` narrows the column to `BINARY(20)`, so a row it
 * misses is a row the schema change truncates. These tests exercise the
 * conversion against the database rather than the statements it builds.
 *
 * @covers \SMW\SQLStore\TableBuilder\Examiner\HashField
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.2.1
 */
class HashFieldIntegrationTest extends SMWIntegrationTestCase {

	/**
	 * Window name => `smw_id`, so assertions can name the row they mean.
	 */
	private array $ids = [];

	/**
	 * PHPUnit runs `tearDown()` even when `setUp()` skips, so the restore has
	 * to know whether the widening actually happened.
	 */
	private bool $columnWidened = false;

	/**
	 * The migration only has work to do while `smw_hash` is still the
	 * pre-7.0 `VARBINARY(40)`, and the test schema is created at the current
	 * definition. Widen the column for the duration of the test so hex values
	 * can be stored at all, then put it back.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( $this->getStore()->getConnection( 'mw.db' )->getType() !== 'mysql' ) {
			$this->markTestSkipped( 'Column juggling is written for MySQL/MariaDB' );
		}

		$this->changeHashColumnTo( 'VARBINARY(40)' );
		$this->columnWidened = true;
	}

	protected function tearDown(): void {
		if ( $this->ids !== [] ) {
			$this->getStore()->getConnection( 'mw.db' )->newDeleteQueryBuilder()
				->deleteFrom( SQLStore::ID_TABLE )
				->where( [ 'smw_id' => array_values( $this->ids ) ] )
				->caller( __METHOD__ )
				->execute();
		}

		if ( $this->columnWidened ) {
			$this->changeHashColumnTo( 'BINARY(20)' );
		}

		parent::tearDown();
	}

	private function changeHashColumnTo( string $type ): void {
		$connection = $this->getStore()->getConnection( 'mw.db' );
		$table = $connection->tableName( SQLStore::ID_TABLE );

		$connection->query(
			"ALTER TABLE $table CHANGE `smw_hash` `smw_hash` $type",
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_SCHEMA
		);
	}

	public function testConvertsHexRowsSpreadOverSeveralBatches() {
		$expected = $this->seedRows();

		$this->runMigration();

		$this->assertSame( $expected, $this->readHashes() );
	}

	public function testLeavesAnEmptyHashAlone() {
		$this->seedRows();

		$this->runMigration();

		$this->assertNull( $this->readHash( 'empty' ) );
	}

	public function testSecondRunIsANoOpSoAnInterruptedRunCanResume() {
		$expected = $this->seedRows();

		$this->runMigration();
		$this->runMigration();

		$this->assertSame( $expected, $this->readHashes() );
	}

	/**
	 * Seeds one hex row per batch window, plus a row an earlier run already
	 * converted, and returns the raw binary each is expected to hold once the
	 * migration has run.
	 *
	 * @return array<string,string> window name => expected raw binary
	 */
	private function seedRows(): array {
		$base = $this->nextFreeId();
		$stride = HashField::CONVERSION_BATCH_SIZE;

		// Spacing the rows a full batch apart forces the walk to cross window
		// boundaries instead of converting everything in one statement.
		$hex = [
			'first' => sha1( 'first' ),
			'second' => sha1( 'second' ),
			'third' => sha1( 'third' ),
		];

		$expected = [];
		$offset = 0;

		foreach ( $hex as $window => $value ) {
			$this->insertRow( $window, $base + $offset * $stride, $value );
			$expected[$window] = hex2bin( $value );
			$offset++;
		}

		// Rows the conversion has to leave alone, placed *inside* the id range
		// the walk covers so that they are actually exposed to it: one an
		// earlier run already converted, one that never had a hash. Only
		// `LENGTH(smw_hash) = 40` keeps them out of the UPDATE, and converting
		// 20 raw bytes a second time would destroy the hash.
		$converted = hex2bin( sha1( 'converted' ) );
		$this->insertRow( 'converted', $base + 1, $converted );
		$expected['converted'] = $converted;

		$this->insertRow( 'empty', $base + 2, null );

		return $expected;
	}

	private function nextFreeId(): int {
		return (int)$this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField() + 1;
	}

	private function insertRow( string $window, int $id, ?string $hash ): void {
		$connection = $this->getStore()->getConnection( 'mw.db' );

		$connection->newInsertQueryBuilder()
			->insertInto( SQLStore::ID_TABLE )
			->row( [
				'smw_id' => $id,
				'smw_namespace' => NS_MAIN,
				'smw_title' => "HashFieldIntegration$id",
				'smw_iw' => '',
				'smw_subobject' => '',
				'smw_sortkey' => "HashFieldIntegration$id",
				'smw_hash' => $hash === null ? null : $connection->escape_bytea( $hash ),
			] )
			->caller( __METHOD__ )
			->execute();

		$this->ids[$window] = $id;
	}

	private function runMigration(): void {
		$instance = new HashField( $this->getStore() );

		$instance->setMessageReporter(
			MessageReporterFactory::getInstance()->newSpyMessageReporter()
		);

		$instance->migrateHexHashes();
	}

	/**
	 * @return array<string,string> window name => stored raw binary, for the
	 *   windows that are expected to hold one
	 */
	private function readHashes(): array {
		$windows = array_diff( array_keys( $this->ids ), [ 'empty' ] );

		return array_combine(
			$windows,
			array_map( fn ( string $window ) => $this->readHash( $window ), $windows )
		);
	}

	private function readHash( string $window ): ?string {
		$connection = $this->getStore()->getConnection( 'mw.db' );

		$value = $connection->newSelectQueryBuilder()
			->select( 'smw_hash' )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_id' => $this->ids[$window] ] )
			->caller( __METHOD__ )
			->fetchField();

		if ( $value === null || $value === false ) {
			return null;
		}

		return $connection->unescape_bytea( $value );
	}

}
