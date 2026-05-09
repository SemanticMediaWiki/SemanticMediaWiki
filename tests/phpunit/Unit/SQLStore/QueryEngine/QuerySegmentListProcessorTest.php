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

}
