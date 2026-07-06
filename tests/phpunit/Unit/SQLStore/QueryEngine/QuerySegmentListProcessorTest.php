<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\Query\Query;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\QuerySegmentListProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class QuerySegmentListProcessorTest extends TestCase {

	use MockWriteQueryBuilderTrait;

	/**
	 * Chainable SelectQueryBuilder mock that captures the source-table list,
	 * selected field, WHERE and join conditions and returns the given field
	 * values from fetchFieldValues().
	 */
	private function createMockSelectQueryBuilder( array &$captured, array $fieldValues ): SelectQueryBuilder {
		$builder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$capture = static function ( $key ) use ( $builder, &$captured ) {
			return static function ( $value ) use ( $builder, &$captured, $key ) {
				$captured[$key] = $value;
				return $builder;
			};
		};

		$builder->method( 'rawTables' )->willReturnCallback( $capture( 'rawTables' ) );
		$builder->method( 'select' )->willReturnCallback( $capture( 'select' ) );
		$builder->method( 'where' )->willReturnCallback( $capture( 'where' ) );
		$builder->method( 'joinConds' )->willReturnCallback( $capture( 'joinConds' ) );
		$builder->method( 'caller' )->willReturn( $builder );
		$builder->method( 'fetchFieldValues' )->willReturn( $fieldValues );

		return $builder;
	}

	public function testCanConstruct() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			QuerySegmentListProcessor::class,
			new QuerySegmentListProcessor( $connection, $temporaryTableBuilder, $hierarchyTempTableBuilder )
		);
	}

	public function testTryResolveSegmentForInvalidIdThrowsException() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$this->expectException( 'RuntimeException' );
		$instance->process( 42 );
	}

	public function testProcessDisjunctionDoesNotEmitSelectDistinct() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		QuerySegment::$qnum = 0;

		$subQuery = new QuerySegment();
		$subQuery->type = QuerySegment::Q_TABLE;
		$subQuery->joinTable = 'smw_object_ids';
		$subQuery->joinfield = "{$subQuery->alias}.smw_id";

		$disjunction = new QuerySegment();
		$disjunction->type = QuerySegment::Q_DISJUNCTION;
		$disjunction->components = [ $subQuery->queryNumber => "{$disjunction->alias}.id" ];

		$querySegmentList = [
			$disjunction->queryNumber => $disjunction,
			$subQuery->queryNumber => $subQuery,
		];

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$instance->setQueryMode( Query::MODE_NONE );
		$instance->setQuerySegmentList( $querySegmentList );
		$instance->process( $disjunction->queryNumber );

		$executedQueries = $instance->getExecutedQueries();
		$allSQL = '';

		foreach ( $executedQueries as $queries ) {
			$allSQL .= implode( "\n", $queries ) . "\n";
		}

		$this->assertNotEmpty( $allSQL, 'Expected at least one disjunction SQL statement to be recorded' );
		$this->assertStringContainsString( 'INSERT IGNORE INTO', $allSQL );
		$this->assertStringNotContainsString( 'SELECT DISTINCT', $allSQL );
	}

	public function testProcessDisjunctionMaterialisesSubqueryWithNonLockingPrimaryRead() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// The disjunct's ids must be read with a plain, non-locking SELECT on
		// the primary connection (newPrimarySelectQueryBuilder) and re-inserted
		// as literals, never through a locking insertSelect() that appends
		// FOR UPDATE to the persistent source table (#7007).
		$captured = [];
		$connection->expects( $this->once() )
			->method( 'newPrimarySelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $captured, [ '10', '10', '20' ] ) );

		$insertTables = [];
		$insertRows = [];
		$connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		// It must not take the locking insertSelect() path, run a raw SQL
		// string, or read from a replica (the temp table is primary-only).
		$connection->expects( $this->never() )->method( 'insertSelect' );
		$connection->expects( $this->never() )->method( 'query' );
		$connection->expects( $this->never() )->method( 'newSelectQueryBuilder' );

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		QuerySegment::$qnum = 0;

		$subQuery = new QuerySegment();
		$subQuery->type = QuerySegment::Q_TABLE;
		$subQuery->joinTable = 'smw_object_ids';
		$subQuery->joinfield = "{$subQuery->alias}.smw_id";

		$disjunction = new QuerySegment();
		$disjunction->type = QuerySegment::Q_DISJUNCTION;
		$disjunction->components = [ $subQuery->queryNumber => "{$disjunction->alias}.id" ];

		$querySegmentList = [
			$disjunction->queryNumber => $disjunction,
			$subQuery->queryNumber => $subQuery,
		];

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$instance->setQueryMode( Query::MODE_INSTANCES );
		$instance->setQuerySegmentList( $querySegmentList );
		$instance->process( $disjunction->queryNumber );

		// Source list = anchor table/alias only; a single id field; no
		// WHERE/join.
		$this->assertSame( [ 't0' => 'smw_object_ids' ], $captured['rawTables'] );
		$this->assertSame( 't0.smw_id', $captured['select'] );
		$this->assertSame( [], $captured['where'] );
		$this->assertSame( [], $captured['joinConds'] );

		// The ids are re-inserted as deduped, int-cast literals into the
		// disjunction temp table ('t1').
		$this->assertSame( [ 't1' ], $insertTables );
		$this->assertSame( [ [ 'id' => 10 ], [ 'id' => 20 ] ], $insertRows );
	}

	public function testProcessDisjunctionBatchesLargeIdListIntoBoundedInserts() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// A disjunct matching many distinct ids must be re-inserted in bounded
		// batches (chunks of 1000), never as one oversized multi-row INSERT.
		// Core does not batch multi-row inserts internally, so this chunking is
		// the only guard against a max_allowed_packet failure under load.
		$ids = array_map( 'strval', range( 1, 2500 ) );

		$captured = [];
		$connection->expects( $this->once() )
			->method( 'newPrimarySelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $captured, $ids ) );

		$insertTables = [];
		$insertRows = [];
		$connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		$connection->expects( $this->never() )->method( 'insertSelect' );

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		QuerySegment::$qnum = 0;

		$subQuery = new QuerySegment();
		$subQuery->type = QuerySegment::Q_TABLE;
		$subQuery->joinTable = 'smw_object_ids';
		$subQuery->joinfield = "{$subQuery->alias}.smw_id";

		$disjunction = new QuerySegment();
		$disjunction->type = QuerySegment::Q_DISJUNCTION;
		$disjunction->components = [ $subQuery->queryNumber => "{$disjunction->alias}.id" ];

		$querySegmentList = [
			$disjunction->queryNumber => $disjunction,
			$subQuery->queryNumber => $subQuery,
		];

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$instance->setQueryMode( Query::MODE_INSTANCES );
		$instance->setQuerySegmentList( $querySegmentList );
		$instance->process( $disjunction->queryNumber );

		// 2500 ids split at the 1000 boundary -> three INSERT builders, each
		// targeting the temp table 't1'; every id inserted once as an int.
		$this->assertSame( [ 't1', 't1', 't1' ], $insertTables );
		$this->assertCount( 2500, $insertRows );
		$this->assertSame( [ 'id' => 1 ], $insertRows[0] );
		$this->assertSame( [ 'id' => 2500 ], $insertRows[2499] );
	}

	public function testProcessDisjunctionForwardsStructuredJoinFragmentsToNonLockingRead() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// A disjunct whose subquery carries a non-empty WHERE plus a join: the
		// non-locking read must receive the structured equivalents
		// ($fromTables -> source tables, $joinConditions -> joinConds,
		// $where -> conds), mirroring how QueryEngine assembles its final
		// SELECT and keeping the SQL portable (#6987). rawTables() reproduces
		// insertSelect()'s handling of the anchor-plus-fromTables array.
		$captured = [];
		$connection->expects( $this->once() )
			->method( 'newPrimarySelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $captured, [ '7' ] ) );

		$insertTables = [];
		$insertRows = [];
		$connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		$connection->expects( $this->never() )->method( 'insertSelect' );
		$connection->expects( $this->never() )->method( 'query' );
		$connection->expects( $this->never() )->method( 'newSelectQueryBuilder' );

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		QuerySegment::$qnum = 0;

		$subQuery = new QuerySegment();
		$subQuery->type = QuerySegment::Q_TABLE;
		$subQuery->joinTable = 'smw_di_wikipage';
		$subQuery->joinfield = "{$subQuery->alias}.s_id";
		$subQuery->where = "{$subQuery->alias}.p_id=42";
		$subQuery->fromTables = [ 'ids' . $subQuery->alias => 'smw_object_ids' ];
		$subQuery->joinConditions = [
			'ids' . $subQuery->alias => [ 'INNER JOIN', "ids{$subQuery->alias}.smw_id={$subQuery->alias}.o_id" ]
		];

		$disjunction = new QuerySegment();
		$disjunction->type = QuerySegment::Q_DISJUNCTION;
		$disjunction->components = [ $subQuery->queryNumber => "{$disjunction->alias}.id" ];

		$querySegmentList = [
			$disjunction->queryNumber => $disjunction,
			$subQuery->queryNumber => $subQuery,
		];

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$instance->setQueryMode( Query::MODE_INSTANCES );
		$instance->setQuerySegmentList( $querySegmentList );
		$instance->process( $disjunction->queryNumber );

		$this->assertSame(
			[ 't0' => 'smw_di_wikipage', 'idst0' => 'smw_object_ids' ],
			$captured['rawTables']
		);
		$this->assertSame( 't0.s_id', $captured['select'] );
		$this->assertSame( 't0.p_id=42', $captured['where'] );
		$this->assertSame(
			[ 'idst0' => [ 'INNER JOIN', 'idst0.smw_id=t0.o_id' ] ],
			$captured['joinConds']
		);

		$this->assertSame( [ 't1' ], $insertTables );
		$this->assertSame( [ [ 'id' => 7 ] ], $insertRows );
	}

	public function testProcessDisjunctionForwardsNestedJoinGroupsToNonLockingRead() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// A disjunct whose subquery carries a nested join (table() stores such
		// a group as an *array-valued* fromTables entry, see its $subQuery->from
		// branch). rawTables() interprets nested arrays as join groups, so the
		// source list must reach the read builder with that nesting intact,
		// never flattened or dropped.
		$captured = [];
		$connection->expects( $this->once() )
			->method( 'newPrimarySelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $captured, [ '9' ] ) );

		$insertTables = [];
		$insertRows = [];
		$connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		$connection->expects( $this->never() )->method( 'insertSelect' );
		$connection->expects( $this->never() )->method( 'query' );
		$connection->expects( $this->never() )->method( 'newSelectQueryBuilder' );

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		QuerySegment::$qnum = 0;

		$subQuery = new QuerySegment();
		$subQuery->type = QuerySegment::Q_TABLE;
		$subQuery->joinTable = 'smw_di_blob';
		$subQuery->joinfield = "{$subQuery->alias}.s_id";
		$subQuery->fromTables = [
			'grp' => [ 'inner0' => 'smw_di_wikipage', 'inner1' => 'smw_object_ids' ],
		];
		$subQuery->joinConditions = [
			'grp' => [ 'INNER JOIN', 'inner0.s_id=t0.s_id' ],
		];

		$disjunction = new QuerySegment();
		$disjunction->type = QuerySegment::Q_DISJUNCTION;
		$disjunction->components = [ $subQuery->queryNumber => "{$disjunction->alias}.id" ];

		$querySegmentList = [
			$disjunction->queryNumber => $disjunction,
			$subQuery->queryNumber => $subQuery,
		];

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$instance->setQueryMode( Query::MODE_INSTANCES );
		$instance->setQuerySegmentList( $querySegmentList );
		$instance->process( $disjunction->queryNumber );

		// The anchor table plus the nested join group, forwarded verbatim.
		$this->assertSame(
			[
				't0' => 'smw_di_blob',
				'grp' => [ 'inner0' => 'smw_di_wikipage', 'inner1' => 'smw_object_ids' ],
			],
			$captured['rawTables']
		);
		$this->assertSame( 't0.s_id', $captured['select'] );
		$this->assertSame(
			[ 'grp' => [ 'INNER JOIN', 'inner0.s_id=t0.s_id' ] ],
			$captured['joinConds']
		);
		$this->assertSame( [ 't1' ], $insertTables );
		$this->assertSame( [ [ 'id' => 9 ] ], $insertRows );
	}

	public function testProcessDisjunctionTreatsNullWhereAsNoCondition() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// `where` is an untyped, nullable property in the planner flow (see the
		// `=== null` guards in table()). A null `where` must fall back to an
		// empty condition ('all rows') and never reach the read builder as
		// `[ null ]`.
		$captured = [];
		$connection->expects( $this->once() )
			->method( 'newPrimarySelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $captured, [ '5' ] ) );

		$insertTables = [];
		$insertRows = [];
		$connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( function () use ( &$insertTables, &$insertRows ) {
				return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
			} );

		$connection->expects( $this->never() )->method( 'insertSelect' );
		$connection->expects( $this->never() )->method( 'query' );
		$connection->expects( $this->never() )->method( 'newSelectQueryBuilder' );

		$temporaryTableBuilder = $this->getMockBuilder( TemporaryTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyTempTableBuilder = $this->getMockBuilder( HierarchyTempTableBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		QuerySegment::$qnum = 0;

		$subQuery = new QuerySegment();
		$subQuery->type = QuerySegment::Q_TABLE;
		$subQuery->joinTable = 'smw_ft_search';
		$subQuery->joinfield = "{$subQuery->alias}.s_id";
		$subQuery->where = null;

		$disjunction = new QuerySegment();
		$disjunction->type = QuerySegment::Q_DISJUNCTION;
		$disjunction->components = [ $subQuery->queryNumber => "{$disjunction->alias}.id" ];

		$querySegmentList = [
			$disjunction->queryNumber => $disjunction,
			$subQuery->queryNumber => $subQuery,
		];

		$instance = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		$instance->setQueryMode( Query::MODE_INSTANCES );
		$instance->setQuerySegmentList( $querySegmentList );
		$instance->process( $disjunction->queryNumber );

		$this->assertSame( [ 't0' => 'smw_ft_search' ], $captured['rawTables'] );
		$this->assertSame( 't0.s_id', $captured['select'] );
		$this->assertSame( [], $captured['where'] );
		$this->assertSame( [], $captured['joinConds'] );
		$this->assertSame( [ 't1' ], $insertTables );
		$this->assertSame( [ [ 'id' => 5 ] ], $insertRows );
	}

}
