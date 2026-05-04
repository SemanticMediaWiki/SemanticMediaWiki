<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableUpdaterTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $connection;
	private $searchTable;
	private $textSanitizer;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable = $this->getMockBuilder( SearchTable::class )
			->disableOriginalConstructor()
			->getMock();

		$this->textSanitizer = $this->getMockBuilder( TextSanitizer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SearchTableUpdater::class,
			new SearchTableUpdater( $this->connection, $this->searchTable, $this->textSanitizer )
		);
	}

	public function testRead() {
		$row = new stdClass;
		$row->o_text = 'Foo';

		$capturedSelects = [];
		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[ $row ],
			$capturedWheres,
			$capturedSelects
		);

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->read( 12, 42 );

		$this->assertSame( [ [ 'o_text' ] ], $capturedSelects );
		$this->assertSame( [ [ 's_id' => 12, 'p_id' => 42 ] ], $capturedWheres );
	}

	public function testOptimizeOnEnabledType() {
		$this->connection->expects( $this->once() )
			->method( 'isType' )
			->with( 'mysql' )
			->willReturn( true );

		$this->connection->expects( $this->once() )
			->method( 'query' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$this->assertTrue(
			$instance->optimize()
		);
	}

	public function testOptimizeOnDisabledType() {
		$this->connection->expects( $this->once() )
			->method( 'isType' )
			->willReturn( false );

		$this->connection->expects( $this->never() )
			->method( 'query' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$this->assertFalse(
			$instance->optimize()
		);
	}

	public function testUpdateWithText() {
		$this->textSanitizer->expects( $this->once() )
			->method( 'sanitize' )
			->willReturn( 'foo' );

		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$this->connection->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->update( 12, 42, 'foo' );

		$this->assertSame( [ [ 's_id' => 12, 'p_id' => 42 ] ], $capturedWheres );
		$this->assertCount( 1, $capturedSets );
		$this->assertSame( 'foo', $capturedSets[0]['o_text'] );
		$this->assertArrayHasKey( 'o_sort', $capturedSets[0] );
	}

	public function testDeleteOnUpdateWithEmptyText() {
		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedTables,
			$capturedWheres
		);

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->never() )
			->method( 'newUpdateQueryBuilder' );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->update( 12, 42, ' ' );

		$this->assertSame( [ [ 's_id' => 12, 'p_id' => 42 ] ], $capturedWheres );
	}

	public function testInsert() {
		$capturedTables = [];
		$capturedRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder(
			$capturedTables,
			$capturedRows
		);

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->insert( 12, 42 );

		$this->assertSame(
			[ [ 's_id' => 12, 'p_id' => 42, 'o_text' => '' ] ],
			$capturedRows
		);
	}

	public function testDelete() {
		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedTables,
			$capturedWheres
		);

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->delete( 12, 42 );

		$this->assertSame( [ [ 's_id' => 12, 'p_id' => 42 ] ], $capturedWheres );
	}

	public function testFlushTable() {
		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedTables,
			$capturedWheres
		);

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->flushTable();

		$this->assertSame( [ IDatabase::ALL_ROWS ], $capturedWheres );
	}

	public function testExists() {
		$capturedSelects = [];
		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[],
			$capturedWheres,
			$capturedSelects
		);

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new SearchTableUpdater(
			$this->connection,
			$this->searchTable,
			$this->textSanitizer
		);

		$instance->exists( 12, 42 );

		$this->assertSame( [ [ 's_id' ] ], $capturedSelects );
		$this->assertSame( [ [ 's_id' => 12, 'p_id' => 42 ] ], $capturedWheres );
	}

}
