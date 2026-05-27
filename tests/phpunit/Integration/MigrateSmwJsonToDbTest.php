<?php

declare( strict_types = 1 );

namespace SMW\Tests\Integration;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use SMW\DatabaseMetaRepo;
use SMW\Setup\MigrateSmwJsonToDb;
use SMW\Site;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Exercises {@see MigrateSmwJsonToDb} end-to-end against the real `smw_meta`
 * table created by SMW's `TableSchemaManager`. The migration is additive:
 * survivor keys from `.smw.json` are inserted via `INSERT IGNORE` so that
 * fresh schema-state rows written by `Store::setupStore` are preserved.
 *
 * @covers \SMW\Setup\MigrateSmwJsonToDb
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MigrateSmwJsonToDbTest extends SMWIntegrationTestCase {

	private string $tmpDir;
	private string $jsonPath;

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = SQLStore::META_TABLE;

		$this->tmpDir = sys_get_temp_dir() . '/smw-migrate-test-' . uniqid();
		mkdir( $this->tmpDir );
		$this->jsonPath = $this->tmpDir . '/.smw.json';

		$this->setMwGlobals( 'smwgConfigFileDir', $this->tmpDir );

		// Start each test with an empty smw_meta. Prior tests in the same
		// DB-group (e.g. SetupFileDatabaseIntegrationTest) may have left rows
		// behind, and individual tests pre-populate the rows they need.
		$this->getDb()->newDeleteQueryBuilder()
			->deleteFrom( SQLStore::META_TABLE )
			->where( '1=1' )
			->caller( __METHOD__ )
			->execute();
	}

	protected function tearDown(): void {
		@unlink( $this->jsonPath );
		@unlink( $this->jsonPath . '.migrated' );
		@rmdir( $this->tmpDir );

		parent::tearDown();
	}

	public function testMigratesSurvivorKeysWithoutOverwritingFreshSchemaState(): void {
		// Simulate what `Store::setupStore` writes before the migration runs:
		// fresh schema-state keys for the upgrade currently in progress.
		$this->insertMetaRow( 'upgrade_key', 'FRESH' );
		$this->insertMetaRow( 'maintenance_mode', false );

		// `.smw.json` carries stale schema state plus survivor keys that
		// `setupStore` does not touch.
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [
				'upgrade_key' => 'STALE',
				'incomplete_tasks' => [ 'smw-test' => true ],
				'last_optimization_run' => '2026-01-01 00:00',
			],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		$repo = new DatabaseMetaRepo(
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
		$loaded = $repo->loadSmwJson( $this->tmpDir );

		$this->assertIsArray( $loaded );
		$entries = $loaded[Site::id()];

		// Fresh values written by setupStore must survive.
		$this->assertSame( 'FRESH', $entries['upgrade_key'] );
		$this->assertFalse( $entries['maintenance_mode'] );

		// Survivor keys must be inserted from .smw.json.
		$this->assertSame( [ 'smw-test' => true ], $entries['incomplete_tasks'] );
		$this->assertSame( '2026-01-01 00:00', $entries['last_optimization_run'] );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testIsIdempotent(): void {
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [ 'upgrade_key' => 'abc123' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		$firstRunCount = $this->getDb()->newSelectQueryBuilder()
			->select( 'meta_key' )
			->from( SQLStore::META_TABLE )
			->caller( __METHOD__ )
			->fetchRowCount();

		// Second invocation: the source file has been renamed by the first
		// call, so the file-presence guard must short-circuit without
		// touching the table.
		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		$secondRunCount = $this->getDb()->newSelectQueryBuilder()
			->select( 'meta_key' )
			->from( SQLStore::META_TABLE )
			->caller( __METHOD__ )
			->fetchRowCount();

		$this->assertSame( $firstRunCount, $secondRunCount );
		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testSkipsWhenFileMissing(): void {
		// No .smw.json on disk; the migration must be a silent no-op.
		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		$rowCount = $this->getDb()->newSelectQueryBuilder()
			->select( 'meta_key' )
			->from( SQLStore::META_TABLE )
			->caller( __METHOD__ )
			->fetchRowCount();

		$this->assertSame( 0, $rowCount );
	}

	private function insertMetaRow( string $key, mixed $value ): void {
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( SQLStore::META_TABLE )
			->row( [
				'meta_key' => $key,
				'meta_value' => json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			] )
			->caller( __METHOD__ )
			->execute();
	}

	private function makeDatabaseUpdater(): DatabaseUpdater {
		$updater = $this->createMock( DatabaseUpdater::class );
		$updater->method( 'getDB' )->willReturn( $this->getDb() );
		$updater->method( 'output' )->willReturn( null );

		return $updater;
	}

}
