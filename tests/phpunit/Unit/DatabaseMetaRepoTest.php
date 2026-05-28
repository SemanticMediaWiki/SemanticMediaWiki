<?php

declare( strict_types = 1 );

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\DatabaseMetaRepo;
use SMW\Site;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\Expression;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;
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

		$db = $this->createMock( IMaintainableDatabase::class );
		$db->method( 'tableExists' )->willReturn( true );
		$db->method( 'expr' )->willReturnCallback(
			static fn ( string $field, string $op, $value ): Expression => new Expression( $field, $op, $value )
		);
		$db->expects( $this->exactly( 2 ) )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $replaceBuilder );
		// One sync-delete (keys NOT IN input); no per-key null deletes.
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

		// Sync delete targets `smw_meta` with a NOT-IN-the-input-keys expression.
		$this->assertSame( [ 'smw_meta' ], $capturedDeleteTables );
		$this->assertCount( 1, $capturedDeleteWheres );
		$this->assertInstanceOf( IExpression::class, $capturedDeleteWheres[0] );
	}

	public function testSaveSmwJsonDeletesNullValues(): void {
		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedTables,
			$capturedWheres
		);

		$db = $this->createMock( IMaintainableDatabase::class );
		$db->method( 'tableExists' )->willReturn( true );
		$db->method( 'expr' )->willReturnCallback(
			static fn ( string $field, string $op, $value ): Expression => new Expression( $field, $op, $value )
		);
		// Two delete calls: one sync-delete (keys NOT IN input), one per-key
		// delete for the null value.
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
		$this->assertCount( 2, $capturedWheres );
		// First where = sync-delete NOT-IN expression.
		$this->assertInstanceOf( IExpression::class, $capturedWheres[0] );
		// Second where = per-key delete by meta_key.
		$this->assertSame( [ 'meta_key' => 'maintenance_mode' ], $capturedWheres[1] );
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

	private function createSelectBuilderMock( IDatabase $db ): SelectQueryBuilder {
		$sqb = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();
		$sqb->method( 'select' )->willReturnSelf();
		$sqb->method( 'from' )->willReturnSelf();
		$sqb->method( 'caller' )->willReturnSelf();
		return $sqb;
	}

	private function makeLoadBalancer( IDatabase $db ): ILoadBalancer {
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $db );
		return $lb;
	}

}
