<?php

declare( strict_types = 1 );

namespace SMW\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SMW\DatabaseMetaRepo;
use SMW\Maintenance\AutoRecovery;
use SMW\Site;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\Expression;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;
use Wikimedia\Rdbms\LikeMatch;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\DatabaseMetaRepo
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class DatabaseMetaRepoTest extends TestCase {

	use MockWriteQueryBuilderTrait;

	/**
	 * A per-identifier reserved row key (the auto-recovery checkpoint stores
	 * one row per maintenance script).
	 */
	private const RESERVED_ROW = AutoRecovery::TOPIC_IDENTIFIER . '.rebuildData.php';

	public function testLoadSmwJsonReturnsNullWhenTableMissing(): void {
		// IMaintainableDatabase is needed for the tableExists() fallback;
		// the production code receives one from the load balancer at
		// runtime (concrete Database classes implement it).
		$db = $this->createMock( IMaintainableDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willThrowException(
			// MySQL surfaces the contracted "doesn't exist"; SQLITE
			// surfaces "no such table". `tableExists` is the source
			// of truth, so the exact message text does not matter here.
			new DBQueryError( $db, "Table 'wiki.smw_meta' doesn't exist", 1146, '', '' )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );
		$db->method( 'tableExists' )->willReturn( false );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$this->assertNull( $repo->loadSmwJson( '/tmp' ) );
	}

	public function testLoadSmwJsonRehydratesPerKeyRows(): void {
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willReturn(
			new FakeResultWrapper( [
				(object)[ 'meta_key' => 'upgrade_key', 'meta_value' => '"abc123"' ],
				(object)[ 'meta_key' => 'incomplete_tasks', 'meta_value' => '{"smw-x":true}' ],
			] )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$this->assertSame(
			[
				Site::id() => [
					'upgrade_key' => 'abc123',
					'incomplete_tasks' => [ 'smw-x' => true ],
				],
			],
			$repo->loadSmwJson( '/tmp' )
		);
	}

	public function testSaveSmwJsonUpsertsEachKey(): void {
		$capturedReplaceTables = [];
		$capturedReplaceRows = [];
		$capturedReplaceUniqueIndexFields = [];
		$replaceBuilder = $this->createMockReplaceQueryBuilder(
			$capturedReplaceTables,
			$capturedReplaceRows,
			$capturedReplaceUniqueIndexFields
		);

		$capturedDeleteTables = [];
		$capturedDeleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedDeleteTables,
			$capturedDeleteWheres
		);

		$db = $this->makeExprDatabase();
		$db->expects( $this->exactly( 2 ) )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $replaceBuilder );
		// One sync-delete builder; no per-key null deletes.
		$db->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->saveSmwJson( '/tmp', [
			Site::id() => [
				'upgrade_key' => 'abc123',
				'maintenance_mode' => false,
			],
		] );

		$this->assertSame( [ 'smw_meta', 'smw_meta' ], $capturedReplaceTables );
		$this->assertSame(
			[
				[ 'meta_key' => 'upgrade_key', 'meta_value' => '"abc123"' ],
				[ 'meta_key' => 'maintenance_mode', 'meta_value' => 'false' ],
			],
			$capturedReplaceRows
		);
		$this->assertSame( [ [ 'meta_key' ], [ 'meta_key' ] ], $capturedReplaceUniqueIndexFields );

		// The sync-delete targets `smw_meta` with two AND'd conditions: a
		// NOT-LIKE that exempts reserved rows and a NOT-IN-the-input-keys clause.
		$this->assertSame( [ 'smw_meta' ], $capturedDeleteTables );
		$this->assertCount( 2, $capturedDeleteWheres );
		$this->assertInstanceOf( IExpression::class, $capturedDeleteWheres[0] );
		$this->assertInstanceOf( IExpression::class, $capturedDeleteWheres[1] );
	}

	public function testSaveSmwJsonDeletesNullValues(): void {
		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedTables,
			$capturedWheres
		);

		$db = $this->makeExprDatabase();
		// Two delete builders: one sync-delete, one per-key delete for the null.
		$db->expects( $this->exactly( 2 ) )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );
		$db->expects( $this->never() )
			->method( 'newReplaceQueryBuilder' );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->saveSmwJson( '/tmp', [
			Site::id() => [
				'maintenance_mode' => null,
			],
		] );

		$this->assertSame( [ 'smw_meta', 'smw_meta' ], $capturedTables );
		// Sync-delete contributes two expressions (NOT LIKE + NOT IN); the
		// per-key null delete contributes the meta_key match.
		$this->assertCount( 3, $capturedWheres );
		$this->assertInstanceOf( IExpression::class, $capturedWheres[0] );
		$this->assertInstanceOf( IExpression::class, $capturedWheres[1] );
		$this->assertSame( [ 'meta_key' => 'maintenance_mode' ], $capturedWheres[2] );
	}

	public function testSaveSmwJsonNoopsWhenTableMissing(): void {
		// During `Installer::install`, `setMaintenanceMode(true)` fires
		// before the SMW tables are created. The DB-backed repo silently
		// drops the write and lets the next checkpoint (issued after
		// tables exist) persist the state.
		$db = $this->createMock( IMaintainableDatabase::class );
		$db->method( 'tableExists' )->willReturn( false );
		$db->expects( $this->never() )->method( 'newReplaceQueryBuilder' );
		$db->expects( $this->never() )->method( 'newDeleteQueryBuilder' );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->saveSmwJson( '/tmp', [
			Site::id() => [ 'upgrade_key' => 'abc123' ],
		] );
	}

	public function testLoadSmwJsonPropagatesNonMissingTableErrors(): void {
		$db = $this->createMock( IMaintainableDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willThrowException(
			new DBQueryError( $db, 'connection lost', 2002, '', '' )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );
		// Table exists, so the SELECT failure is a real error and must
		// surface to the caller.
		$db->method( 'tableExists' )->willReturn( true );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$this->expectException( DBQueryError::class );
		$repo->loadSmwJson( '/tmp' );
	}

	public function testLoadSmwJsonExcludesReservedRows(): void {
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willReturn(
			new FakeResultWrapper( [
				(object)[ 'meta_key' => 'upgrade_key', 'meta_value' => '"abc123"' ],
				(object)[ 'meta_key' => self::RESERVED_ROW, 'meta_value' => '{"ar_id":42}' ],
			] )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		// The reserved auto-recovery row is out-of-band; it must not surface
		// as part of the install-state slice.
		$this->assertSame(
			[ Site::id() => [ 'upgrade_key' => 'abc123' ] ],
			$repo->loadSmwJson( '/tmp' )
		);
	}

	public function testLoadSmwJsonReturnsNullWhenOnlyReservedRowsPresent(): void {
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willReturn(
			new FakeResultWrapper( [
				(object)[ 'meta_key' => self::RESERVED_ROW, 'meta_value' => '{"ar_id":1}' ],
			] )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		// Only reserved rows means no install state, indistinguishable from
		// an empty table.
		$this->assertNull( $repo->loadSmwJson( '/tmp' ) );
	}

	public function testSaveSmwJsonExemptsReservedPrefixFromSyncDelete(): void {
		$capturedOps = [];

		$db = $this->makeExprDatabase( $capturedOps );
		$db->method( 'newReplaceQueryBuilder' )->willReturn( $this->createMockReplaceQueryBuilder() );
		$db->method( 'newDeleteQueryBuilder' )->willReturn( $this->createMockDeleteQueryBuilder() );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->saveSmwJson( '/tmp', [
			Site::id() => [ 'upgrade_key' => 'abc123' ],
		] );

		// The sync-delete must protect reserved rows with a NOT LIKE on
		// meta_key, alongside the NOT-IN clause for the install-state keys.
		$notLike = array_filter(
			$capturedOps,
			static fn ( array $e ): bool => $e['field'] === 'meta_key' && $e['op'] === IExpression::NOT_LIKE
		);
		$this->assertCount( 1, $notLike, 'sync-delete must exempt reserved rows via NOT LIKE' );

		$notIn = array_filter(
			$capturedOps,
			static fn ( array $e ): bool => $e['field'] === 'meta_key' && $e['op'] === '!='
		);
		$this->assertCount( 1, $notIn, 'sync-delete must still remove obsolete install-state keys' );
	}

	public function testSaveSmwJsonEmptySliceStillExemptsReservedPrefix(): void {
		$capturedOps = [];

		$db = $this->makeExprDatabase( $capturedOps );
		$db->expects( $this->never() )->method( 'newReplaceQueryBuilder' );
		$db->method( 'newDeleteQueryBuilder' )->willReturn( $this->createMockDeleteQueryBuilder() );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		// A reset (empty slice) must clear install-state rows but still leave
		// reserved rows untouched: only the NOT LIKE clause, no NOT IN.
		$repo->saveSmwJson( '/tmp', [ Site::id() => [] ] );

		$ops = array_column( $capturedOps, 'op' );
		$this->assertContains( IExpression::NOT_LIKE, $ops );
		$this->assertNotContains( '!=', $ops );
	}

	public function testReadValueReturnsDecodedValue(): void {
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchField' )->willReturn( '{"ar_id":42}' );
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$this->assertSame(
			[ 'ar_id' => 42 ],
			$repo->readValue( self::RESERVED_ROW )
		);
	}

	public function testReadValueReturnsNullWhenRowMissing(): void {
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchField' )->willReturn( false );
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$this->assertNull( $repo->readValue( self::RESERVED_ROW ) );
	}

	public function testReadValueReturnsNullWhenTableMissing(): void {
		$db = $this->createMock( IMaintainableDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchField' )->willThrowException(
			new DBQueryError( $db, "Table 'wiki.smw_meta' doesn't exist", 1146, '', '' )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );
		$db->method( 'tableExists' )->willReturn( false );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$this->assertNull( $repo->readValue( self::RESERVED_ROW ) );
	}

	public function testReadValueRejectsNonReservedKey(): void {
		$repo = new DatabaseMetaRepo(
			$this->makeLoadBalancer( $this->createMock( IDatabase::class ) )
		);

		$this->expectException( InvalidArgumentException::class );
		$repo->readValue( 'upgrade_key' );
	}

	public function testWriteValueUpsertsSingleRowWithoutSyncDelete(): void {
		$capturedTables = [];
		$capturedRows = [];
		$capturedUnique = [];
		$replaceBuilder = $this->createMockReplaceQueryBuilder(
			$capturedTables,
			$capturedRows,
			$capturedUnique
		);

		$db = $this->createMock( IMaintainableDatabase::class );
		$db->method( 'tableExists' )->willReturn( true );
		$db->expects( $this->once() )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $replaceBuilder );
		// A single-row write must never issue a full-slice sync-delete.
		$db->expects( $this->never() )->method( 'newDeleteQueryBuilder' );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->writeValue( self::RESERVED_ROW, [ 'ar_id' => 42 ] );

		$this->assertSame( [ 'smw_meta' ], $capturedTables );
		$this->assertSame(
			[ [
				'meta_key' => self::RESERVED_ROW,
				'meta_value' => '{"ar_id":42}',
			] ],
			$capturedRows
		);
		$this->assertSame( [ [ 'meta_key' ] ], $capturedUnique );
	}

	public function testWriteValueNoopsWhenTableMissing(): void {
		$db = $this->createMock( IMaintainableDatabase::class );
		$db->method( 'tableExists' )->willReturn( false );
		$db->expects( $this->never() )->method( 'newReplaceQueryBuilder' );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->writeValue( self::RESERVED_ROW, [ 'ar_id' => 1 ] );
	}

	public function testWriteValueRejectsNonReservedKey(): void {
		$repo = new DatabaseMetaRepo(
			$this->makeLoadBalancer( $this->createMock( IMaintainableDatabase::class ) )
		);

		$this->expectException( InvalidArgumentException::class );
		$repo->writeValue( 'upgrade_key', 'x' );
	}

	/**
	 * A table mock with `tableExists()` true and a real-`Expression`-producing
	 * `expr()`/`anyString()` so `saveSmwJson`'s NOT-LIKE/NOT-IN clauses build.
	 * When `$capturedOps` is supplied, each `expr()` call records its
	 * `[ field, op ]` for assertions.
	 */
	private function makeExprDatabase( array &$capturedOps = [] ): IMaintainableDatabase {
		$db = $this->createMock( IMaintainableDatabase::class );
		$db->method( 'tableExists' )->willReturn( true );
		$db->method( 'anyString' )->willReturn( $this->createMock( LikeMatch::class ) );
		$db->method( 'expr' )->willReturnCallback(
			static function ( string $field, string $op, $value ) use ( &$capturedOps ): Expression {
				$capturedOps[] = [ 'field' => $field, 'op' => $op ];
				return new Expression( $field, $op, $value );
			}
		);
		return $db;
	}

	private function createSelectBuilderMock( IDatabase $db ): SelectQueryBuilder {
		$sqb = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();
		$sqb->method( 'select' )->willReturnSelf();
		$sqb->method( 'from' )->willReturnSelf();
		$sqb->method( 'where' )->willReturnSelf();
		$sqb->method( 'caller' )->willReturnSelf();
		return $sqb;
	}

	private function makeLoadBalancer( IDatabase $db ): ILoadBalancer {
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $db );
		return $lb;
	}

}
