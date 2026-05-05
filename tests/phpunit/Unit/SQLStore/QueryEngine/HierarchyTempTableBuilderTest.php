<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyTempTableBuilderTest extends TestCase {

	use MockWriteQueryBuilderTrait;

	private $connection;
	private $temporaryTableBuilder;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HierarchyTempTableBuilder::class,
			new HierarchyTempTableBuilder( $this->connection, $this->temporaryTableBuilder )
		);
	}

	public function testGetHierarchyTableDefinitionForType() {
		$this->connection->expects( $this->never() )
			->method( 'tableName' );

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$instance->setTableDefinitions( [ 'property' => [ 'table' => 'bar', 'depth' => 3 ] ] );

		$this->assertEquals(
			[ 'bar', 3 ],
			$instance->getTableDefinitionByType( 'property' )
		);
	}

	public function testTryToGetHierarchyTableDefinitionForUnregisteredTypeThrowsException() {
		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$this->expectException( 'RuntimeException' );
		$instance->getTableDefinitionByType( 'foo' );
	}

	public function testFillTempTable() {
		$this->connection->expects( $this->never() )
			->method( 'tableName' );

		$insertTables = [];
		$insertRows = [];
		$this->connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		$deleteTables = [];
		$this->connection->method( 'newDeleteQueryBuilder' )
			->willReturnCallback( function () use ( &$deleteTables ) {
				return $this->createMockDeleteQueryBuilder( $deleteTables );
			} );

		// affectedRows() returns 0 by default — the depth loop exits at the
		// first iteration, so insertSelect() is invoked once (for $tmpres)
		// before the loop breaks.
		$this->connection->method( 'affectedRows' )->willReturn( 0 );

		$insertSelectCalls = [];
		$this->connection->method( 'insertSelect' )
			->willReturnCallback( static function ( $dest, $src, $varMap, $conds, $fname, $insertOptions ) use ( &$insertSelectCalls ) {
				$insertSelectCalls[] = [
					'dest' => $dest,
					'src' => $src,
					'insertOptions' => $insertOptions,
				];
				return true;
			} );

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$instance->setTableDefinitions( [ 'class' => [ 'table' => 'bar', 'depth' => 3 ] ] );

		$instance->fillTempTable( 'class', 'foobar', '(42)' );

		// Two INSERT IGNORE seed builders: one targeting $tablename ('foobar'),
		// one targeting $tmpnew ('smw_new'). Each receives a single row.
		$this->assertSame( [ 'foobar', 'smw_new' ], $insertTables );
		$this->assertSame( [ [ 'id' => 42 ], [ 'id' => 42 ] ], $insertRows );

		// Pin the depth-loop's insertSelect count: one carryback INSERT into
		// $tmpres, then affectedRows()=0 breaks the loop before the next call.
		$this->assertCount( 1, $insertSelectCalls );
		$this->assertSame( 'smw_res', $insertSelectCalls[0]['dest'] );
		$this->assertSame( [ 'bar', 'smw_new' ], $insertSelectCalls[0]['src'] );
		$this->assertSame( [ 'IGNORE' ], $insertSelectCalls[0]['insertOptions'] );

		$expected = [
			'(42)' => 'foobar'
		];

		$this->assertEquals(
			$expected,
			$instance->getHierarchyCache()
		);

		$instance->emptyHierarchyCache();

		$this->assertEmpty(
			$instance->getHierarchyCache()
		);
	}

	public function testFillTempTableUsesCacheOnRepeatComposite() {
		$this->connection->expects( $this->never() )
			->method( 'tableName' );

		// First fill seeds the cache with insertInto + insertSelect calls;
		// second fill must hit the cache branch and use insertSelect() to
		// copy rows from the cached table.
		$insertTables = [];
		$insertRows = [];
		$this->connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		$this->connection->method( 'newDeleteQueryBuilder' )
			->willReturnCallback( function () {
				return $this->createMockDeleteQueryBuilder();
			} );

		$this->connection->method( 'affectedRows' )->willReturn( 0 );

		$insertSelectCalls = [];
		$this->connection->method( 'insertSelect' )
			->willReturnCallback( static function ( $dest, $src, $varMap, $conds, $fname, $insertOptions ) use ( &$insertSelectCalls ) {
				$insertSelectCalls[] = [
					'dest' => $dest,
					'src' => $src,
					'varMap' => $varMap,
					'conds' => $conds,
					'insertOptions' => $insertOptions,
				];
				return true;
			} );

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$instance->setTableDefinitions( [ 'class' => [ 'table' => 'bar', 'depth' => 3 ] ] );

		// First call: builds the temp table (no cache hit).
		$instance->fillTempTable( 'class', 'firstTable', '(42)' );

		// Second call with same composite: must use cache branch.
		$instance->fillTempTable( 'class', 'secondTable', '(42)' );

		// Find the cache-copy invocation (no IGNORE option, dest=secondTable).
		$cacheCopy = null;
		foreach ( $insertSelectCalls as $call ) {
			if ( $call['dest'] === 'secondTable' ) {
				$cacheCopy = $call;
				break;
			}
		}

		$this->assertNotNull( $cacheCopy, 'Cache-copy insertSelect must be issued on second fill' );
		$this->assertSame( 'firstTable', $cacheCopy['src'] );
		$this->assertSame( [ 'id' => 'id' ], $cacheCopy['varMap'] );
		$this->assertSame( '*', $cacheCopy['conds'] );
		$this->assertSame( [], $cacheCopy['insertOptions'] );

		// First fill issues 1 insertSelect (depth-loop carryback, then breaks
		// because affectedRows()=0); second fill issues 1 insertSelect (cache
		// copy). Pin the total so silent regressions in either branch fail.
		$this->assertCount( 2, $insertSelectCalls );
	}

}
