<?php

declare( strict_types = 1 );

namespace SMW\Tests\Integration;

use SMW\SetupFile;
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
