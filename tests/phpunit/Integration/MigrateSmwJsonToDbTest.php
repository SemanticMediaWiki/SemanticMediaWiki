<?php

declare( strict_types = 1 );

namespace SMW\Tests\Integration;

use Onoi\MessageReporter\MessageReporter;
use SMW\Setup\MigrateSmwJsonToDb;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Exercises {@see MigrateSmwJsonToDb} end-to-end. The migration's role is
 * narrow: rename a present `.smw.json` to `.smw.json.migrated` so future
 * loadSchema calls fall through to `smw_meta` instead of re-hydrating from
 * disk. Data transfer is the responsibility of `SetupFile::loadSchema`'s
 * legacy-file fallback combined with the install pipeline's normal writes.
 *
 * @covers \SMW\Setup\MigrateSmwJsonToDb
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MigrateSmwJsonToDbTest extends SMWIntegrationTestCase {

	private string $tmpDir;
	private string $jsonPath;

	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = sys_get_temp_dir() . '/smw-migrate-test-' . uniqid();
		mkdir( $this->tmpDir );
		$this->jsonPath = $this->tmpDir . '/.smw.json';

		$this->setMwGlobals( 'smwgConfigFileDir', $this->tmpDir );
	}

	protected function tearDown(): void {
		@unlink( $this->jsonPath );
		@unlink( $this->jsonPath . '.migrated' );
		@rmdir( $this->tmpDir );

		parent::tearDown();
	}

	public function testRenamesLegacyFile(): void {
		file_put_contents( $this->jsonPath, json_encode( [
			'wiki' => [ 'upgrade_key' => 'legacy' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testIsIdempotent(): void {
		file_put_contents( $this->jsonPath, '{}' );

		MigrateSmwJsonToDb::run( $this->makeReporter() );
		// Second call: the source file no longer exists; the file-presence
		// guard makes it a silent no-op.
		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testNoopWhenFileMissing(): void {
		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileDoesNotExist( $this->jsonPath . '.migrated' );
	}

	private function makeReporter(): MessageReporter {
		return $this->createMock( MessageReporter::class );
	}

}
