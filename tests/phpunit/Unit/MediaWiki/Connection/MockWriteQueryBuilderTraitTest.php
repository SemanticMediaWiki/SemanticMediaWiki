<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;

/**
 * @covers \SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MockWriteQueryBuilderTraitTest extends TestCase {

	use MockWriteQueryBuilderTrait;

	public function testInsertCapturesTableRowsAndUniqueIndex(): void {
		$tables = $rows = $sets = $uniqueIndexFields = [];

		$builder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$builder
			->insertInto( 'smw_test' )
			->row( [ 'a' => 1 ] )
			->onDuplicateKeyUpdate()
			->uniqueIndexFields( [ 'a' ] )
			->set( [ 'a' => 2 ] )
			->caller( __METHOD__ )
			->execute();

		$this->assertSame( [ 'smw_test' ], $tables );
		$this->assertSame( [ [ 'a' => 1 ] ], $rows );
		$this->assertSame( [ [ 'a' => 2 ] ], $sets );
		$this->assertSame( [ [ 'a' ] ], $uniqueIndexFields );
	}

	public function testInsertAppendsAcrossMultipleCalls(): void {
		$tables = $rows = $sets = $uniqueIndexFields = [];

		$builder = $this->createMockInsertQueryBuilder( $tables, $rows, $sets, $uniqueIndexFields );

		$builder
			->insertInto( 'smw_test' )
			->row( [ 'a' => 1 ] )
			->row( [ 'a' => 2 ] )
			->rows( [ [ 'a' => 3 ], [ 'a' => 4 ] ] )
			->caller( __METHOD__ )
			->execute();

		$this->assertSame( [ 'smw_test' ], $tables );
		$this->assertSame(
			[ [ 'a' => 1 ], [ 'a' => 2 ], [ [ 'a' => 3 ], [ 'a' => 4 ] ] ],
			$rows
		);
	}

	public function testUpdateCapturesTableSetAndWhere(): void {
		$tables = $sets = $wheres = [];

		$builder = $this->createMockUpdateQueryBuilder( $tables, $sets, $wheres );

		$builder
			->update( 'smw_test' )
			->set( [ 'a' => 2 ] )
			->where( [ 'id' => 1 ] )
			->caller( __METHOD__ )
			->execute();

		$this->assertSame( [ 'smw_test' ], $tables );
		$this->assertSame( [ [ 'a' => 2 ] ], $sets );
		$this->assertSame( [ [ 'id' => 1 ] ], $wheres );
	}

	public function testDeleteCapturesTableAndWhere(): void {
		$tables = $wheres = [];

		$builder = $this->createMockDeleteQueryBuilder( $tables, $wheres );

		$builder
			->deleteFrom( 'smw_test' )
			->where( [ 'id' => 1 ] )
			->caller( __METHOD__ )
			->execute();

		$this->assertSame( [ 'smw_test' ], $tables );
		$this->assertSame( [ [ 'id' => 1 ] ], $wheres );
	}

	public function testReplaceCapturesTableRowsAndUniqueIndex(): void {
		$tables = $rows = $uniqueIndexFields = [];

		$builder = $this->createMockReplaceQueryBuilder( $tables, $rows, $uniqueIndexFields );

		$builder
			->replaceInto( 'smw_test' )
			->uniqueIndexFields( [ 'id' ] )
			->row( [ 'id' => 1 ] )
			->caller( __METHOD__ )
			->execute();

		$this->assertSame( [ 'smw_test' ], $tables );
		$this->assertSame( [ [ 'id' => 1 ] ], $rows );
		$this->assertSame( [ [ 'id' ] ], $uniqueIndexFields );
	}
}
