<?php

namespace SMW\Tests\Unit\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Browse\ArticleAugmentor;
use SMW\MediaWiki\Api\Browse\ArticleLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ArticleLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	public function testCanConstruct() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$articleAugmentor = $this->getMockBuilder( ArticleAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ArticleLookup::class,
			new ArticleLookup( $connection, $articleAugmentor )
		);
	}

	/**
	 * @dataProvider articleSearchProvider
	 */
	public function testLookup( $search, $row, $condition, $expected ) {
		$articleAugmentor = $this->getMockBuilder( ArticleAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$whereConditions = [];
		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$whereConditions ) {
				return $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );
			} );

		$instance = new ArticleLookup(
			$connection,
			$articleAugmentor
		);

		$parameters = [
			'search' => $search
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);

		$this->assertNotEmpty( $whereConditions );
		$this->assertStringContainsString( $condition, $whereConditions[0][0] );
	}

	public function articleSearchProvider() {
		$row = new stdClass;
		$row->page_title = 'Foo';
		$row->page_id = 42;
		$row->page_namespace = 0;

		$provider[] = [
			'Foo',
			$row,
			'page_title LIKE %Foo% ESCAPE ` OR page_title LIKE %Foo% ESCAPE ` OR page_title LIKE %FOO% ESCAPE ` OR page_title LIKE %foo% ESCAPE `',
			[
				'Foo#0' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo',
					'ns' => 0
				]
			]
		];

		$row = new stdClass;
		$row->page_title = 'Foo';
		$row->page_id = 42;
		$row->page_namespace = 12;

		$provider[] = [
			'Help:Fo o',
			$row,
			'page_namespace=12 AND (page_title LIKE %Fo`_o% ESCAPE ` OR page_title LIKE %Fo`_o% ESCAPE ` OR page_title LIKE %FO`_O% ESCAPE ` OR page_title LIKE %fo`_o% ESCAPE `)',
			[
				'Foo#12' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo',
					'ns' => 12
				]
			]
		];

		return $provider;
	}

	public function testLegacyResponseOmitsContinueCursorField(): void {
		$instance = $this->newInstanceReturningRows( [ $this->newRow( 42, 'Foo', 0 ) ] );

		$res = $instance->lookup( [ 'search' => 'Foo' ] );

		// Legacy clients (no `cursor` opt-in) must see exactly the
		// pre-cursor response shape. Existing JSONScript fixtures depend
		// on a contiguous `"query-continue-offset":0,"version":1`
		// substring; inserting a new field between them would break them.
		$this->assertArrayNotHasKey( 'query-continue-cursor', $res );
		$this->assertArrayHasKey( 'query-continue-offset', $res );
	}

	public function testCursorModeEmitsContinueCursorField(): void {
		$instance = $this->newInstanceReturningRows( [ $this->newRow( 42, 'Foo', 0 ) ] );

		$res = $instance->lookup( [
			'search' => 'Foo',
			'cursor' => 0,
		] );

		$this->assertArrayHasKey( 'query-continue-cursor', $res );
		$this->assertSame( 0, $res['query-continue-cursor'] );
	}

	public function testCursorModeWithLookaheadRowEmitsContinueCursor(): void {
		// 3 rows with limit=2 -> the third row is the lookahead; the
		// loop must not include it in the response and must surface the
		// SECOND row's page_id as the next-page anchor.
		$rows = [
			$this->newRow( 100, 'AAA', 0 ),
			$this->newRow( 101, 'BBB', 0 ),
			$this->newRow( 102, 'CCC', 0 ),
		];
		$instance = $this->newInstanceReturningRows( $rows );

		$res = $instance->lookup( [
			'search' => 'X',
			'cursor' => 0,
			'limit' => 2,
		] );

		$this->assertCount( 2, $res['query'] );
		$this->assertSame( 101, $res['query-continue-cursor'] );
		$this->assertSame( 0, $res['query-continue-offset'] );
	}

	public function testCursorWithNonZeroValueEmitsKeysetPredicate(): void {
		$capturedWheres = [];
		$instance = $this->newInstanceReturningRows(
			[ $this->newRow( 42, 'Anchor_Title', 5 ) ],
			$capturedWheres
		);

		$instance->lookup( [
			'search' => 'X',
			'cursor' => 99,
		] );

		// First captured where must be the anchor resolution.
		$this->assertSame(
			[ 'page_id' => 99 ],
			$capturedWheres[0],
			'cursor>0 must trigger a SELECT to resolve page_id 99 to (page_title, page_namespace)'
		);

		// Second captured where must be the main query with the keyset
		// predicate ANDed onto the search conditions. The mock fetchRow
		// returns the first row (the test row) as the anchor, so the
		// predicate references that row's title/ns.
		$this->assertStringContainsString(
			'page_title > Anchor_Title',
			$capturedWheres[1][0],
			'Cursor mode must emit a > predicate on page_title using the anchor row'
		);
		$this->assertStringContainsString(
			'page_title = Anchor_Title AND page_namespace > 5',
			$capturedWheres[1][0],
			'Cursor mode must emit a tiebreak predicate on page_namespace'
		);
	}

	public function testStaleCursorFallsBackToFirstPageWithoutPredicate(): void {
		// Anchor resolution returns no row (cursor is stale). The mock
		// trait's `fetchRow` returns the first row from the array; pass
		// an empty array so it returns false and the trait reads "stale".
		$articleAugmentor = $this->getMockBuilder( ArticleAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$capturedWheres = [];
		$callIndex = 0;
		$mainRow = $this->newRow( 42, 'Foo', 0 );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $mainRow, &$capturedWheres, &$callIndex ) {
					$rows = $callIndex === 0 ? [] : [ $mainRow ];
					$callIndex++;
					return $this->createMockSelectQueryBuilder( $rows, $capturedWheres );
				}
			);

		$instance = new ArticleLookup( $connection, $articleAugmentor );

		$res = $instance->lookup( [
			'search' => 'Foo',
			'cursor' => 99999999,
		] );

		// Main query (index 1) must NOT contain a keyset predicate.
		$mainWhere = is_string( $capturedWheres[1][0] ?? null ) ? $capturedWheres[1][0] : '';
		$this->assertStringNotContainsString(
			'page_title >',
			$mainWhere,
			'Stale cursor must skip the keyset predicate and serve the first page'
		);
		$this->assertCount( 1, $res['query'] );
	}

	private function newRow( int $id, string $title, int $namespace ): stdClass {
		$row = new stdClass;
		$row->page_id = $id;
		$row->page_title = $title;
		$row->page_namespace = $namespace;
		return $row;
	}

	private function newInstanceReturningRows( array $rows, array &$capturedWheres = [] ): ArticleLookup {
		$articleAugmentor = $this->getMockBuilder( ArticleAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $rows, &$capturedWheres ) {
					return $this->createMockSelectQueryBuilder( $rows, $capturedWheres );
				}
			);

		return new ArticleLookup( $connection, $articleAugmentor );
	}

}
