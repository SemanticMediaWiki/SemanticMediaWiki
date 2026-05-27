<?php

declare( strict_types = 1 );

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\DatabaseMetaRepo;
use SMW\Site;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
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
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willThrowException(
			new DBQueryError( $db, '42S02 base table or view not found', 1146, '', '' )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

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
		$capturedTables = [];
		$capturedRows = [];
		$capturedUniqueIndexFields = [];
		$replaceBuilder = $this->createMockReplaceQueryBuilder(
			$capturedTables,
			$capturedRows,
			$capturedUniqueIndexFields
		);

		$db = $this->createMock( IDatabase::class );
		$db->expects( $this->exactly( 2 ) )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $replaceBuilder );
		$db->expects( $this->never() )
			->method( 'newDeleteQueryBuilder' );

		$repo = new DatabaseMetaRepo( $this->makeLoadBalancer( $db ) );

		$repo->saveSmwJson( '/tmp', [
			Site::id() => [
				'upgrade_key' => 'abc123',
				'maintenance_mode' => false,
			],
		] );

		$this->assertSame( [ 'smw_meta', 'smw_meta' ], $capturedTables );
		$this->assertSame(
			[
				[ 'meta_key' => 'upgrade_key', 'meta_value' => '"abc123"' ],
				[ 'meta_key' => 'maintenance_mode', 'meta_value' => 'false' ],
			],
			$capturedRows
		);
		$this->assertSame( [ [ 'meta_key' ], [ 'meta_key' ] ], $capturedUniqueIndexFields );
	}

	public function testSaveSmwJsonDeletesNullValues(): void {
		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedTables,
			$capturedWheres
		);

		$db = $this->createMock( IDatabase::class );
		$db->expects( $this->once() )
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

		$this->assertSame( [ 'smw_meta' ], $capturedTables );
		$this->assertSame( [ [ 'meta_key' => 'maintenance_mode' ] ], $capturedWheres );
	}

	public function testLoadSmwJsonPropagatesNonMissingTableErrors(): void {
		$db = $this->createMock( IDatabase::class );
		$sqb = $this->createSelectBuilderMock( $db );
		$sqb->method( 'fetchResultSet' )->willThrowException(
			new DBQueryError( $db, 'connection lost', 2002, '', '' )
		);
		$db->method( 'newSelectQueryBuilder' )->willReturn( $sqb );

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
