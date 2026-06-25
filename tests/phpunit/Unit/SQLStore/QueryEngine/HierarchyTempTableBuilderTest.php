<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

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
		// tableName() is now called inside the depth loop to build the
		// non-locking frontier SELECT; stub it as an identity passthrough.
		$this->connection->method( 'tableName' )
			->willReturnCallback( static fn ( $table ) => $table );

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

		// The depth-loop frontier is read with a plain, non-locking SELECT
		// (no FOR UPDATE); return one row, then let affectedRows()=0 break the
		// loop after the temp->temp carryback.
		$queryCalls = [];
		$this->connection->method( 'query' )
			->willReturnCallback( static function ( $sql, $fname, $flags ) use ( &$queryCalls ) {
				$queryCalls[] = [ 'sql' => $sql, 'flags' => $flags ];
				return new FakeResultWrapper( [ (object)[ 's_id' => '99' ] ] );
			} );

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

		// Two INSERT IGNORE seed builders ($tablename 'foobar' and $tmpnew
		// 'smw_new'), then the depth-loop frontier INSERT IGNORE into $tmpres
		// ('smw_res') with the id read back from the SELECT.
		$this->assertSame( [ 'foobar', 'smw_new', 'smw_res' ], $insertTables );
		$this->assertSame( [ [ 'id' => 42 ], [ 'id' => 42 ], [ 'id' => 99 ] ], $insertRows );

		// The frontier is read with exactly one plain, non-locking SELECT
		// (QUERY_CHANGE_NONE) joining $smwtable ('bar') and $tmpnew ('smw_new').
		// The exact string also pins the absence of FOR UPDATE.
		$this->assertCount( 1, $queryCalls );
		$this->assertSame( 'SELECT s_id FROM bar,smw_new WHERE o_id=id', $queryCalls[0]['sql'] );
		$this->assertSame( ISQLPlatform::QUERY_CHANGE_NONE, $queryCalls[0]['flags'] );

		// The only insertSelect left in the loop is the temp->temp carryback
		// ($tmpres -> $tablename); affectedRows()=0 then breaks the loop.
		$this->assertCount( 1, $insertSelectCalls );
		$this->assertSame( 'foobar', $insertSelectCalls[0]['dest'] );
		$this->assertSame( 'smw_res', $insertSelectCalls[0]['src'] );
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

	public function testFillTempTableStopsWhenFrontierEmpty() {
		$this->connection->method( 'tableName' )
			->willReturnCallback( static fn ( $table ) => $table );

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

		// Empty frontier: the depth loop must break at the $ids === [] check,
		// before any $tmpres insert or carryback insertSelect.
		$this->connection->method( 'query' )
			->willReturn( new FakeResultWrapper( [] ) );

		$insertSelectCalled = false;
		$this->connection->method( 'insertSelect' )
			->willReturnCallback( static function () use ( &$insertSelectCalled ) {
				$insertSelectCalled = true;
				return true;
			} );

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$instance->setTableDefinitions( [ 'class' => [ 'table' => 'bar', 'depth' => 3 ] ] );
		$instance->fillTempTable( 'class', 'foobar', '(42)' );

		// Only the two seed inserts ran ($tablename 'foobar' and $tmpnew
		// 'smw_new'); the empty frontier breaks the loop before $tmpres is
		// touched and before any carryback.
		$this->assertSame( [ 'foobar', 'smw_new' ], $insertTables );
		$this->assertFalse( $insertSelectCalled, 'no carryback insertSelect when the frontier is empty' );
		$this->assertSame( [ '(42)' => 'foobar' ], $instance->getHierarchyCache() );
	}

	public function testFillTempTableUsesCacheOnRepeatComposite() {
		$this->connection->method( 'tableName' )
			->willReturnCallback( static fn ( $table ) => $table );

		// First fill seeds the cache with insertInto + (frontier SELECT +
		// temp->temp carryback) calls; second fill must hit the cache branch
		// and use insertSelect() to copy rows from the cached table.
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

		// First fill's depth loop reads the frontier with a plain SELECT.
		$this->connection->method( 'query' )
			->willReturnCallback( static function () {
				return new FakeResultWrapper( [ (object)[ 's_id' => '99' ] ] );
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
