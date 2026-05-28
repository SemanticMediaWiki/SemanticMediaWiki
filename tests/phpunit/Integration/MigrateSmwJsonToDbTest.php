<?php

declare( strict_types = 1 );

namespace SMW\Tests\Integration;

use Onoi\MessageReporter\MessageReporter;
use SMW\Setup\MigrateSmwJsonToDb;
use SMW\Site;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Exercises {@see MigrateSmwJsonToDb} end-to-end. The migration's role is
 * narrow: remove the current wiki's slice from a legacy `.smw.json` so
 * future loadSchema calls fall through to `smw_meta` for this wiki while
 * other wikis sharing the file can still migrate. Data transfer itself is
 * the responsibility of `SetupFile::loadSchema`'s legacy-file fallback
 * combined with the install pipeline's normal writes.
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

	public function testRenamesFileWhenLastSliceIsConsumed(): void {
		// File contains only the current wiki's slice: the migration
		// removes that slice and then the file is empty, so it is renamed
		// to `.smw.json.migrated` as a tombstone.
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [ 'upgrade_key' => 'legacy' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testRemovesCurrentSliceButPreservesOtherWikiSlices(): void {
		// Shared-codebase multi-wiki: only this wiki's slice is consumed.
		// Other wiki slices stay in the file so their own upgrades can
		// still migrate.
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [ 'upgrade_key' => 'legacy-for-this-wiki' ],
			'some-other-wiki' => [ 'upgrade_key' => 'legacy-for-other' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileExists( $this->jsonPath );
		$this->assertFileDoesNotExist( $this->jsonPath . '.migrated' );

		$remaining = json_decode( file_get_contents( $this->jsonPath ), true );
		$this->assertSame(
			[ 'some-other-wiki' => [ 'upgrade_key' => 'legacy-for-other' ] ],
			$remaining
		);
	}

	public function testIsIdempotent(): void {
		file_put_contents( $this->jsonPath, json_encode( [
			Site::id() => [ 'upgrade_key' => 'legacy' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeReporter() );
		// Second call: the source file has been renamed by the first run;
		// the file-presence guard makes it a silent no-op.
		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileExists( $this->jsonPath . '.migrated' );
	}

	public function testNoopWhenFileMissing(): void {
		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileDoesNotExist( $this->jsonPath );
		$this->assertFileDoesNotExist( $this->jsonPath . '.migrated' );
	}

	public function testNoopWhenFileHasNoSliceForThisWiki(): void {
		// Another wiki's slice exists but not ours; leave the file alone
		// so that wiki can still migrate.
		file_put_contents( $this->jsonPath, json_encode( [
			'some-other-wiki' => [ 'upgrade_key' => 'legacy-for-other' ],
		] ) );

		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileExists( $this->jsonPath );
		$this->assertFileDoesNotExist( $this->jsonPath . '.migrated' );
		$remaining = json_decode( file_get_contents( $this->jsonPath ), true );
		$this->assertSame(
			[ 'some-other-wiki' => [ 'upgrade_key' => 'legacy-for-other' ] ],
			$remaining
		);
	}

	public function testLeavesMalformedFileInPlace(): void {
		// `SetupFile::loadSchema`'s fallback already aborts the request
		// when the file cannot be decoded, so this branch is defensive.
		// If we somehow reach it, leave the file alone so the admin can
		// still recover.
		file_put_contents( $this->jsonPath, '{invalid: json' );

		MigrateSmwJsonToDb::run( $this->makeReporter() );

		$this->assertFileExists( $this->jsonPath );
		$this->assertFileDoesNotExist( $this->jsonPath . '.migrated' );
	}

	private function makeReporter(): MessageReporter {
		return $this->createMock( MessageReporter::class );
	}

}
