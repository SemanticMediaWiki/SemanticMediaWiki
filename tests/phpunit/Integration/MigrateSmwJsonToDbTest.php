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
 * Exercises {@see MigrateSmwJsonToDb} end-to-end against the real
 * `smw_meta` table created by SMW's `TableSchemaManager`. Verifies that
 * the one-shot migration copies `.smw.json` entries into the table,
 * renames the file, and is safe to re-run (idempotent both when the
 * table is already populated and when the file is missing).
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

		// Force a clean slate so the "already populated" guard does not
		// short-circuit the migration. Prior tests in the same DB-group
		// (e.g. SetupFileDatabaseIntegrationTest) may have left rows behind.
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

	public function testMigratesEntriesAndRenamesFile(): void {
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [
				'upgrade_key' => 'abc123',
				'maintenance_mode' => false,
				'latest_version' => '7.0.0',
				'incomplete_tasks' => [ 'smw-test' => true ],
			],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		$repo = new DatabaseMetaRepo(
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
		$loaded = $repo->loadSmwJson( $this->tmpDir );

		$this->assertIsArray( $loaded );
		$this->assertSame( 'abc123', $loaded[Site::id()]['upgrade_key'] );
		$this->assertFalse( $loaded[Site::id()]['maintenance_mode'] );
		$this->assertSame( '7.0.0', $loaded[Site::id()]['latest_version'] );
		$this->assertSame( [ 'smw-test' => true ], $loaded[Site::id()]['incomplete_tasks'] );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testIsIdempotent(): void {
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [ 'upgrade_key' => 'abc123' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		// Second invocation: the source file has been renamed by the first
		// call, and the table is populated, so the guard must short-circuit
		// without producing any further rows or errors.
		MigrateSmwJsonToDb::run( $this->makeDatabaseUpdater() );

		$rowCount = $this->getDb()->newSelectQueryBuilder()
			->select( 'meta_key' )
			->from( SQLStore::META_TABLE )
			->where( [ 'meta_key' => 'upgrade_key' ] )
			->caller( __METHOD__ )
			->fetchRowCount();

		$this->assertSame( 1, $rowCount );
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

	private function makeDatabaseUpdater(): DatabaseUpdater {
		$updater = $this->createMock( DatabaseUpdater::class );
		$updater->method( 'getDB' )->willReturn( $this->getDb() );
		$updater->method( 'output' )->willReturn( null );

		return $updater;
	}

}
