<?php

declare( strict_types = 1 );

namespace SMW\Tests\Integration\Maintenance;

use SMW\Maintenance\AutoRecovery;
use SMW\SetupFile;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\LikeValue;

/**
 * Exercises {@see \SMW\Maintenance\AutoRecovery} end-to-end against the real
 * `smw_meta` table, verifying the #7030 fix: the `--auto-recovery` checkpoint
 * persists in the database (no `.smw.json` / writable `$smwgConfigFileDir`
 * dependency), the read path never writes, the checkpoint survives an
 * install-state (`SetupFile`) write, never leaks into the install-state slice,
 * and is isolated per script so concurrent runs cannot clobber each other.
 *
 * @covers \SMW\Maintenance\AutoRecovery
 * @covers \SMW\DatabaseMetaRepo
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class AutoRecoveryDatabaseIntegrationTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->deleteCheckpointRows();
	}

	protected function tearDown(): void {
		$this->deleteCheckpointRows();
		parent::tearDown();
	}

	public function testCheckpointRoundTripsThroughDatabase(): void {
		$writer = new AutoRecovery( 'rebuildData.php' );
		$writer->enable( true );
		$writer->set( 'ar_id', 1234 );

		// A fresh instance must read the checkpoint back from `smw_meta`,
		// with no dependency on a writable extension directory.
		$reader = new AutoRecovery( 'rebuildData.php' );
		$reader->enable( true );

		$this->assertTrue( $reader->has( 'ar_id' ) );
		$this->assertSame( 1234, $reader->get( 'ar_id' ) );
	}

	public function testDistinctIdentifiersDoNotCollide(): void {
		$data = new AutoRecovery( 'rebuildData.php' );
		$data->enable( true );
		$data->set( 'ar_id', 11 );

		$elastic = new AutoRecovery( 'rebuildElasticIndex.php' );
		$elastic->enable( true );
		$elastic->set( 'ar_id', 22 );

		$dataReader = new AutoRecovery( 'rebuildData.php' );
		$dataReader->enable( true );

		$this->assertSame( 11, $dataReader->get( 'ar_id' ) );
	}

	public function testConcurrentWritersPreserveEachOthersCheckpoint(): void {
		// Simulate two maintenance scripts that both loaded their (absent)
		// checkpoint before either wrote. With a single shared row the later
		// write, built from a stale snapshot, would clobber the first script's
		// checkpoint. Per-identifier rows keep them isolated.
		$data = new AutoRecovery( 'rebuildData.php' );
		$data->enable( true );
		$elastic = new AutoRecovery( 'rebuildElasticIndex.php' );
		$elastic->enable( true );

		// Both load their (empty) state first.
		$this->assertFalse( $data->has( 'ar_id' ) );
		$this->assertFalse( $elastic->has( 'ar_id' ) );

		// Then both persist.
		$data->set( 'ar_id', 100 );
		$elastic->set( 'ar_id', 5 );

		// Both checkpoints must survive.
		$dataReader = new AutoRecovery( 'rebuildData.php' );
		$dataReader->enable( true );
		$elasticReader = new AutoRecovery( 'rebuildElasticIndex.php' );
		$elasticReader->enable( true );

		$this->assertSame( 100, $dataReader->get( 'ar_id' ) );
		$this->assertSame( 5, $elasticReader->get( 'ar_id' ) );
	}

	public function testReadPathDoesNotCreateRow(): void {
		$reader = new AutoRecovery( 'rebuildData.php' );
		$reader->enable( true );

		// The pre-run abort check calls has(); the #7030 crash was a write
		// triggered from this read path. It must not create a row.
		$this->assertFalse( $reader->has( 'ar_id' ) );

		$this->assertFalse(
			$this->getDb()->newSelectQueryBuilder()
				->select( 'meta_value' )
				->from( SQLStore::META_TABLE )
				->where( [ 'meta_key' => AutoRecovery::TOPIC_IDENTIFIER . '.rebuildData.php' ] )
				->caller( __METHOD__ )
				->fetchField(),
			'has() must not create the auto-recovery row'
		);
	}

	public function testCheckpointDecoupledFromInstallStateSlice(): void {
		$writer = new AutoRecovery( 'rebuildData.php' );
		$writer->enable( true );
		$writer->set( 'ar_id', 1234 );

		// A full-slice SetupFile write must neither delete the checkpoint row
		// (its sync-delete exempts reserved rows) nor surface it as install
		// state.
		$vars = [ 'smwgConfigFileDir' => sys_get_temp_dir() ];
		$setupFile = new SetupFile();
		$setupFile->loadSchema( $vars );
		$setupFile->set( [ SetupFile::LATEST_VERSION => '7.2.0' ], $vars );

		$reader = new AutoRecovery( 'rebuildData.php' );
		$reader->enable( true );
		$this->assertSame(
			1234,
			$reader->get( 'ar_id' ),
			'checkpoint must survive an install-state write'
		);

		$readerVars = [ 'smwgConfigFileDir' => sys_get_temp_dir() ];
		$freshSetupFile = new SetupFile();
		$freshSetupFile->loadSchema( $readerVars );
		$this->assertNull(
			$freshSetupFile->get( AutoRecovery::TOPIC_IDENTIFIER . '.rebuildData.php', $readerVars ),
			'auto-recovery row must not appear in the install-state slice'
		);
	}

	private function deleteCheckpointRows(): void {
		$db = $this->getDb();
		$db->newDeleteQueryBuilder()
			->deleteFrom( SQLStore::META_TABLE )
			->where(
				$db->expr(
					'meta_key',
					IExpression::LIKE,
					new LikeValue( AutoRecovery::TOPIC_IDENTIFIER, $db->anyString() )
				)
			)
			->caller( __METHOD__ )
			->execute();
	}

}
