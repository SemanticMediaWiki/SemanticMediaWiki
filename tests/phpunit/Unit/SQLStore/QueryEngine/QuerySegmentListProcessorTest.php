<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\Query\Query;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;

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

	public function testProcessDisjunctionMaterialisesSubqueryViaPortableInsertSelect() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// The disjunction subquery must be materialised through
		// insertSelect( ..., [ 'IGNORE' ] ), which emits the platform-correct
		// ignore verb (INSERT IGNORE / INSERT OR IGNORE / ON CONFLICT DO
		// NOTHING). Hand-building an "INSERT IGNORE ... SELECT" string broke
		// SQLite and PostgreSQL (#6979).
		$connection->expects( $this->once() )
			->method( 'insertSelect' )
			->with(
				't1',
				[ 't0' => 'smw_object_ids' ],
				[ 'id' => 't0.smw_id' ],
				'*',
				$this->anything(),
				[ 'IGNORE' ],
				[],
				[]
			);

		// It must not fall back to executing a raw, non-portable SQL string.
		$connection->expects( $this->never() )
			->method( 'query' );

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
	}

	public function testProcessDisjunctionForwardsStructuredJoinFragmentsToInsertSelect() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// A disjunct whose subquery carries a non-empty WHERE plus a join: the
		// raw $from string is non-portable, so insertSelect() must receive the
		// structured equivalents ($fromTables -> source tables,
		// $joinConditions -> selectJoinConds, $where -> conds), mirroring how
		// QueryEngine assembles its final SELECT.
		$connection->expects( $this->once() )
			->method( 'insertSelect' )
			->with(
				't1',
				[ 't0' => 'smw_di_wikipage', 'idst0' => 'smw_object_ids' ],
				[ 'id' => 't0.s_id' ],
				[ 't0.p_id=42' ],
				$this->anything(),
				[ 'IGNORE' ],
				[],
				[ 'idst0' => [ 'INNER JOIN', 'idst0.smw_id=t0.o_id' ] ]
			);

		$connection->expects( $this->never() )
			->method( 'query' );

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
	}

	public function testProcessDisjunctionTreatsNullWhereAsAllRows() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'tableName' )
			->willReturnArgument( 0 );

		// `where` is an untyped, nullable property in the planner flow (see the
		// `=== null` guards in table()). A null `where` must fall back to
		// ALL_ROWS ('*') and never reach insertSelect() as `[ null ]`.
		$connection->expects( $this->once() )
			->method( 'insertSelect' )
			->with(
				't1',
				[ 't0' => 'smw_ft_search' ],
				[ 'id' => 't0.s_id' ],
				'*',
				$this->anything(),
				[ 'IGNORE' ],
				[],
				[]
			);

		$connection->expects( $this->never() )
			->method( 'query' );

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
	}

}
