<?php

declare( strict_types = 1 );

namespace SMW\Tests\Integration;

use RuntimeException;
use SMW\Globals;
use SMW\SetupFile;
use SMW\Site;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Exercises {@see SetupFile} end-to-end against the real `smw_meta` table
 * created by SMW's `TableSchemaManager`. Verifies that the default repo
 * (now {@see \SMW\DatabaseMetaRepo}) round-trips state across distinct
 * `SetupFile` instances and that `remove` deletes the underlying row.
 *
 * @covers \SMW\SetupFile
 * @covers \SMW\DatabaseMetaRepo
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SetupFileDatabaseIntegrationTest extends SMWIntegrationTestCase {

	public function testRoundTripAcrossInstances(): void {
		$vars = $this->makeVars();

		$writer = new SetupFile();
		$writer->loadSchema( $vars );

		$writer->set(
			[
				SetupFile::LATEST_VERSION => '7.0.0',
				SetupFile::INCOMPLETE_TASKS => [ 'smw-test-task' => true ],
			],
			$vars
		);

		// Force a fresh load with a new instance against the same repo.
		$readerVars = $this->makeVars();
		$reader = new SetupFile();
		$reader->loadSchema( $readerVars );

		$this->assertSame(
			'7.0.0',
			$reader->get( SetupFile::LATEST_VERSION, $readerVars )
		);
		$this->assertSame(
			[ 'smw-test-task' => true ],
			$reader->get( SetupFile::INCOMPLETE_TASKS, $readerVars )
		);
	}

	public function testLegacyFileFallbackPreservesSurvivorKeysAcrossInstallWrites(): void {
		// Regression guard for the smoke-discovered data-loss bug:
		// `HashField::check` -> `populateHashField::setComplete(true)` ->
		// `removeIncompleteTask` fires during install and used to write a
		// fresh `incomplete_tasks: []` on a legacy upgrade, shadowing the
		// user's real tasks. The option-B fix in `loadSchema` hydrates
		// `$GLOBALS['smw.json']` from `.smw.json` first, so the defensive
		// remove operates on the user's actual array and preserves the
		// other entries.
		$tmpDir = sys_get_temp_dir() . '/smw-legacy-upgrade-' . uniqid();
		mkdir( $tmpDir );
		$legacyFile = $tmpDir . '/.smw.json';

		file_put_contents( $legacyFile, json_encode( [
			Site::id() => [
				'upgrade_key' => 'STALE_FROM_PRE_7_0',
				'incomplete_tasks' => [
					'smw-rebuildelasticindex-incomplete' => true,
					'smw-populatehashfield-incomplete' => true,
				],
				'entity_collation' => 'identity',
			],
		] ) );

		// Truncate smw_meta and clear $GLOBALS so the legacy fallback is
		// what populates state. `SetupFile`'s default-vars methods read
		// from `$GLOBALS` in production, so we mirror that here.
		$this->getDb()->newDeleteQueryBuilder()
			->deleteFrom( SQLStore::META_TABLE )
			->where( '1=1' )
			->caller( __METHOD__ )
			->execute();
		unset( $GLOBALS[ 'smw.json' ] );
		$this->setMwGlobals( 'smwgConfigFileDir', $tmpDir );

		// Mirror `Setup::init`'s pattern: local `$vars` passed by reference,
		// then write back into `$GLOBALS` via the production helper. PHP 8
		// forbids passing `$GLOBALS` itself by reference.
		$vars = $GLOBALS;
		$setupFile = new SetupFile();
		$setupFile->loadSchema( $vars );
		Globals::replace( $vars );

		// Confirm the fallback hydrated the survivor keys.
		$this->assertSame(
			[
				'smw-rebuildelasticindex-incomplete' => true,
				'smw-populatehashfield-incomplete' => true,
			],
			$setupFile->get( SetupFile::INCOMPLETE_TASKS )
		);

		// Simulate the install pipeline's defensive remove for one of the
		// keys. Pre-option-B this would have shadowed everything with [];
		// post-fix it only removes the named entry and preserves the rest.
		$setupFile->removeIncompleteTask( 'smw-populatehashfield-incomplete' );

		// Fresh instance reads from `smw_meta` (file fallback only fires
		// when the DB has no rows; the write above populated it).
		unset( $GLOBALS[ 'smw.json' ] );
		$readerVars = $GLOBALS;
		$reader = new SetupFile();
		$reader->loadSchema( $readerVars );
		Globals::replace( $readerVars );

		$this->assertSame(
			[ 'smw-rebuildelasticindex-incomplete' => true ],
			$reader->get( SetupFile::INCOMPLETE_TASKS ),
			'survivor incomplete_tasks must persist into smw_meta after a defensive remove'
		);
		$this->assertSame(
			'identity',
			$reader->get( SetupFile::ENTITY_COLLATION ),
			'survivor entity_collation must persist'
		);

		@unlink( $legacyFile );
		@unlink( $legacyFile . '.migrated' );
		@rmdir( $tmpDir );
	}

	public function testLegacyFileFallbackThrowsOnUnreadableFile(): void {
		// Regression guard for the silent-data-loss path identified by
		// CodeRabbit: a present-but-malformed `.smw.json` used to collapse
		// to `null` in the fallback, the installer wrote fresh rows to
		// `smw_meta`, and the next run found `smw_meta` non-empty so the
		// fallback never fired again. Even if the admin fixed the file,
		// it would be silently ignored forever. `loadSchema` must now
		// throw before any install writes happen.
		$tmpDir = sys_get_temp_dir() . '/smw-legacy-malformed-' . uniqid();
		mkdir( $tmpDir );
		$legacyFile = $tmpDir . '/.smw.json';
		file_put_contents( $legacyFile, '{invalid: json' );

		$this->getDb()->newDeleteQueryBuilder()
			->deleteFrom( SQLStore::META_TABLE )
			->where( '1=1' )
			->caller( __METHOD__ )
			->execute();
		unset( $GLOBALS[ 'smw.json' ] );
		$this->setMwGlobals( 'smwgConfigFileDir', $tmpDir );

		$vars = $GLOBALS;
		$setupFile = new SetupFile();

		try {
			$this->expectException( RuntimeException::class );
			$this->expectExceptionMessageMatches( '/cannot be decoded as JSON/' );
			$setupFile->loadSchema( $vars );
		} finally {
			@unlink( $legacyFile );
			@rmdir( $tmpDir );
		}
	}

	public function testRemoveDeletesKey(): void {
		$writerVars = $this->makeVars();
		$writer = new SetupFile();
		$writer->loadSchema( $writerVars );
		$writer->set( [ SetupFile::ENTITY_COLLATION => 'identity' ], $writerVars );

		// First fresh instance: confirm the key is visible via the DB.
		$confirmVars = $this->makeVars();
		$confirm = new SetupFile();
		$confirm->loadSchema( $confirmVars );
		$this->assertSame(
			'identity',
			$confirm->get( SetupFile::ENTITY_COLLATION, $confirmVars )
		);

		// Remove via a third instance, then verify a fourth no longer sees it.
		$removerVars = $this->makeVars();
		$remover = new SetupFile();
		$remover->loadSchema( $removerVars );
		$remover->remove( SetupFile::ENTITY_COLLATION, $removerVars );

		$readerVars = $this->makeVars();
		$reader = new SetupFile();
		$reader->loadSchema( $readerVars );
		$this->assertNull(
			$reader->get( SetupFile::ENTITY_COLLATION, $readerVars )
		);
	}

	private function makeVars(): array {
		return [
			'smwgConfigFileDir' => sys_get_temp_dir(),
			// Round-trip tests target the repo directly; `loadSchema` should
			// populate `smw.json` from the database.
		];
	}

}
