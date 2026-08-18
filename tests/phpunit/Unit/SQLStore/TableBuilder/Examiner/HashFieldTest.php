<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\Maintenance\populateHashField;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\HashField
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashFieldTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $spyMessageReporter;
	private $store;
	private $populateHashField;
	private $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->populateHashField = $this->getMockBuilder( populateHashField::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HashField::class,
			new HashField( $this->store )
		);
	}

	public function testCheck_Populate() {
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( HashField::threshold() - 1 );

		$this->populateHashField->expects( $this->atLeastOnce() )
			->method( 'populate' );

		$this->populateHashField->expects( $this->once() )
			->method( 'fetchRows' )
			->willReturn( $resultWrapper );

		$instance = new HashField(
			$this->store,
			$this->populateHashField
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertStringContainsString(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	/**
	 * Builds a Database mock for the migration.
	 *
	 * Every read the migration makes goes to the primary: first the hex-row
	 * count and the id range it spans, then any per-batch reads, then the
	 * same query again to confirm nothing is left. That last one reports
	 * zero, which is what the check sees once the batches have run; the queue
	 * keeps answering zero after it is drained, so adding a read to the
	 * production code degrades an assertion instead of fataling on a null
	 * builder.
	 */
	private function newConnectionForMigration(
		int $hexCount,
		int $lastId,
		string $type = 'mysql',
		array $selectQueue = [],
		?array &$idBounds = null
	): Database {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$exhausted = $this->createMockSelectQueryBuilder(
			[ (object)[ 'rows' => 0, 'first' => null, 'last' => null ] ]
		);

		$queue = array_merge(
			[ $this->createMockSelectQueryBuilder(
				[ (object)[ 'rows' => $hexCount, 'first' => 1, 'last' => $lastId ] ]
			) ],
			$selectQueue,
			[ $exhausted ]
		);

		$connection->method( 'newPrimarySelectQueryBuilder' )
			->willReturnCallback(
				static function () use ( &$queue, $exhausted ) {
					return array_shift( $queue ) ?? $exhausted;
				}
			);

		// Nothing in the migration reads a replica; a stray call must not
		// consume a queued answer.
		$connection->method( 'newSelectQueryBuilder' )
			->willReturn( $exhausted );

		$connection->method( 'getType' )
			->willReturn( $type );

		// Record every `expr()` call as a `[ field, operator, value ]` triple
		// so a test can reconstruct the `smw_id` windows that were asked for.
		$connection->method( 'expr' )
			->willReturnCallback(
				static function ( $field, $operator, $value ) use ( &$idBounds ) {
					$idBounds[] = [ $field, $operator, $value ];
					return "$field $operator $value";
				}
			);

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getConnection' )
			->willReturn( $connection );

		return $connection;
	}

	/**
	 * Turns recorded bounds into the `[ start, end ]` windows they describe.
	 */
	private function idWindowsFrom( array $bounds ): array {
		return array_map(
			static function ( array $pair ) {
				return [ $pair[0][2], $pair[1][2] ];
			},
			array_chunk( $bounds, 2 )
		);
	}

	private function newInstance(): HashField {
		$instance = new HashField( $this->store );
		$instance->setMessageReporter( $this->spyMessageReporter );

		return $instance;
	}

	public function testMigrateHexHashes_RunsWhenCountExceedsThreshold() {
		// Regression test for issue #6715: when more than threshold hex
		// hashes exist, the SQL conversion must still run — otherwise the
		// subsequent ALTER TABLE BINARY(20) truncates the 40-byte values
		// and the upgrade fails.
		$connection = $this->newConnectionForMigration( HashField::threshold() + 1, 1 );

		$updateTables = [];
		$updateSets = [];
		$updateWheres = [];

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () use ( &$updateTables, &$updateSets, &$updateWheres ) {
					return $this->createMockUpdateQueryBuilder( $updateTables, $updateSets, $updateWheres );
				}
			);

		$this->newInstance()->migrateHexHashes();

		$this->assertSame( [ SQLStore::ID_TABLE ], $updateTables );
		$this->assertCount( 1, $updateSets );
		$this->assertSame( 'UNHEX(smw_hash)', $updateSets[0]['smw_hash']->toSql() );
		$this->assertSame(
			[ [ 'LENGTH(smw_hash) = 40' ], 'smw_id >= 1', 'smw_id <= 1' ],
			$updateWheres
		);

		$this->assertStringContainsString(
			'converting hex hashes to binary',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testMigrateHexHashes_SplitsConversionIntoBoundedIdRanges() {
		// Issue #7091: a single whole-table UPDATE runs for minutes on a
		// large wiki, so a dropped connection rolls back all of it. Convert
		// in bounded `smw_id` windows instead: consecutive, batch-sized, and
		// spanning exactly the ids that still hold a hex hash.
		$lastId = 2 * HashField::CONVERSION_BATCH_SIZE + 1;
		$bounds = [];
		$connection = $this->newConnectionForMigration( $lastId, $lastId, idBounds: $bounds );

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () {
					return $this->createMockUpdateQueryBuilder();
				}
			);

		$this->newInstance()->migrateHexHashes();

		$windows = $this->idWindowsFrom( $bounds );
		$starts = array_column( $windows, 0 );
		$ends = array_column( $windows, 1 );

		$this->assertSame( 1, $starts[0], 'walk starts at the first row needing conversion' );
		$this->assertSame( $lastId, end( $ends ), 'walk stops at the last one, without overshooting it' );

		$this->assertSame(
			array_fill( 0, count( $windows ) - 1, HashField::CONVERSION_BATCH_SIZE ),
			array_map( static fn ( array $w ) => $w[1] - $w[0] + 1, array_slice( $windows, 0, -1 ) ),
			'every window but the last covers a full batch'
		);

		$this->assertSame(
			array_slice( array_map( static fn ( int $e ) => $e + 1, $ends ), 0, -1 ),
			array_slice( $starts, 1 ),
			'windows are consecutive, so no id is skipped'
		);
	}

	public function testMigrateHexHashes_CommitsAfterEveryBatch() {
		// Without a commit per batch the whole conversion stays one
		// transaction, which is what makes a mid-migration disconnect
		// discard every converted row.
		$lastId = 2 * HashField::CONVERSION_BATCH_SIZE;
		$connection = $this->newConnectionForMigration( $lastId, $lastId );

		$connection->method( 'getEmptyTransactionTicket' )
			->willReturn( 42 );

		// The update builder records the table it targets, so sharing one
		// array with the commit callback yields the interleaving.
		$callLog = [];

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () use ( &$callLog ) {
					return $this->createMockUpdateQueryBuilder( $callLog );
				}
			);

		$connection->method( 'commitAndWaitForReplication' )
			->willReturnCallback(
				static function () use ( &$callLog ) {
					$callLog[] = 'commit';
				}
			);

		$this->newInstance()->migrateHexHashes();

		$this->assertSame(
			[ SQLStore::ID_TABLE, 'commit', SQLStore::ID_TABLE, 'commit' ],
			$callLog,
			'each batch is committed before the next one starts'
		);
	}

	public function testMigrateHexHashes_WarnsWhenBatchesCannotBeCommitted() {
		// A null ticket makes commitAndWaitForReplication a no-op, which
		// silently restores the single-transaction behaviour being fixed.
		$connection = $this->newConnectionForMigration( 1, 1 );

		$connection->method( 'getEmptyTransactionTicket' )
			->willReturn( null );

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () {
					return $this->createMockUpdateQueryBuilder();
				}
			);

		$this->newInstance()->migrateHexHashes();

		$this->assertStringContainsString(
			'cannot commit per batch',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testMigrateHexHashes_StopsBeforeSchemaChangeWhenHexRowsSurvive() {
		// The caller narrows smw_hash to BINARY(20) right after this runs,
		// which would truncate anything the walk missed.
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// The count stays non-zero after the conversion, as if the walk had
		// missed rows.
		$connection->method( 'newPrimarySelectQueryBuilder' )
			->willReturnCallback(
				fn () => $this->createMockSelectQueryBuilder(
					[ (object)[ 'rows' => 3, 'first' => 1, 'last' => 1 ] ]
				)
			);

		$connection->method( 'getType' )->willReturn( 'mysql' );
		$connection->method( 'expr' )
			->willReturnCallback( static fn ( $f, $o, $v ) => "$f $o $v" );

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () {
					return $this->createMockUpdateQueryBuilder();
				}
			);

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getConnection' )->willReturn( $connection );

		$instance = $this->newInstance();

		$this->expectException( RuntimeException::class );
		$instance->migrateHexHashes();
	}

	public function testMigrateHexHashes_UsesPostgresDecode() {
		$connection = $this->newConnectionForMigration( 1, 1, 'postgres' );

		$updateSets = [];

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () use ( &$updateSets ) {
					return $this->createMockUpdateQueryBuilder( sets: $updateSets );
				}
			);

		$this->newInstance()->migrateHexHashes();

		$this->assertSame( "decode(smw_hash, 'hex')", $updateSets[0]['smw_hash']->toSql() );
	}

	public function testMigrateHexHashes_ConvertsRowsInPhpOnSqlite() {
		// SQLite gained unhex() only in 3.38, so the conversion happens in
		// PHP there — one bounded read per window, one update per row.
		$connection = $this->newConnectionForMigration(
			1,
			1,
			'sqlite',
			[ $this->createMockSelectQueryBuilder( [ (object)[ 'smw_id' => 7, 'smw_hash' => str_repeat( 'ab', 20 ) ] ] ) ]
		);

		$updateSets = [];
		$updateWheres = [];

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback(
				function () use ( &$updateSets, &$updateWheres ) {
					return $this->createMockUpdateQueryBuilder( sets: $updateSets, wheres: $updateWheres );
				}
			);

		$this->newInstance()->migrateHexHashes();

		$this->assertSame( hex2bin( str_repeat( 'ab', 20 ) ), $updateSets[0]['smw_hash'] );
		$this->assertSame( [ [ 'smw_id' => 7 ] ], $updateWheres );
	}

	public function testMigrateHexHashes_EmitsRecoveryHintOnDBError() {
		$connection = $this->newConnectionForMigration( 1, 1 );

		$dbError = $this->getMockBuilder( DBError::class )
			->disableOriginalConstructor()
			->getMock();

		$updateBuilder = $this->createMockUpdateQueryBuilder();
		$updateBuilder->method( 'execute' )
			->willThrowException( $dbError );

		$connection->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$instance = $this->newInstance();

		try {
			$instance->migrateHexHashes();
			$this->fail( 'Expected DBError to be re-thrown' );
		} catch ( DBError $e ) {
			// expected
		}

		$messages = $this->spyMessageReporter->getMessagesAsString();
		$this->assertStringContainsString( 'hex-to-binary conversion failed', $messages );
		$this->assertStringContainsString( 'resumes where it stopped', $messages );
	}

	public function testMigrateHexHashes_NoOpWhenCountIsZero() {
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[ (object)[ 'rows' => 0, 'first' => null, 'last' => null ] ]
		);

		$this->connection->method( 'newPrimarySelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$this->connection->expects( $this->never() )
			->method( 'newUpdateQueryBuilder' );

		$this->newInstance()->migrateHexHashes();
	}

	public function testCheck_Incomplete() {
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( HashField::threshold() + 1 );

		$this->populateHashField->expects( $this->atLeastOnce() )
			->method( 'setComplete' );

		$this->populateHashField->expects( $this->once() )
			->method( 'fetchRows' )
			->willReturn( $resultWrapper );

		$instance = new HashField(
			$this->store,
			$this->populateHashField
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertStringContainsString(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
