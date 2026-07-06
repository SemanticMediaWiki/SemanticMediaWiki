<?php

namespace SMW\Tests\Unit\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Browse\ListAugmentor;
use SMW\MediaWiki\Api\Browse\ListLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ListLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ListLookup::class,
			new ListLookup( $store, $listAugmentor )
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testLookup( $ns, $title, $expected ) {
		$row = new stdClass;
		$row->smw_title = $title;
		$row->smw_id = 42;

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ $row ] ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListLookup(
			$store,
			$listAugmentor
		);

		$parameters = [
			'ns' => $ns,
			'search' => 'Foo',
			'sort' => true
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

	public function namespaceProvider() {
		$provider[] = [
			SMW_NS_PROPERTY,
			'Foo',
			[
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			]
		];

		$provider[] = [
			NS_CATEGORY,
			'Foo',
			[
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			]
		];

		$provider[] = [
			SMW_NS_CONCEPT,
			'Foo',
			[
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			]
		];

		return $provider;
	}

	public function testLegacyResponseOmitsContinueCursorField(): void {
		$instance = $this->newInstanceWithRows( [ $this->newRow( 42, 'Foo' ) ] );

		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
		] );

		// Legacy clients (no `cursor` opt-in) must see exactly the
		// pre-cursor response shape: `query-continue-cursor` MUST NOT
		// appear in the array. Existing JSONScript fixtures depend on
		// the contiguous `"query-continue-offset":0,"version":1`
		// substring; inserting a new field between them breaks them.
		$this->assertArrayNotHasKey( 'query-continue-cursor', $res );
		$this->assertArrayHasKey( 'query-continue-offset', $res );
	}

	public function testCursorParamPresentOptsIntoCursorMode(): void {
		// 3 rows with limit=2 -> 2 displayed + 1 lookahead row triggers
		// the continueCursor population from the last displayed row's id.
		$rows = [
			$this->newRow( 100, 'AAA' ),
			$this->newRow( 101, 'BBB' ),
			$this->newRow( 102, 'CCC' ),
		];

		$instance = $this->newInstanceWithRows( $rows );

		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 0,
			'limit' => 2,
		] );

		$this->assertArrayHasKey( 'query-continue-cursor', $res );
		$this->assertSame( 101, $res['query-continue-cursor'] );
		$this->assertSame( 0, $res['query-continue-offset'] );
		$this->assertCount( 2, $res['query'] );
	}

	public function testCursorModeWithNoFurtherRowsEmitsZeroCursor(): void {
		$instance = $this->newInstanceWithRows( [ $this->newRow( 42, 'Foo' ) ] );

		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 0,
			'limit' => 50,
		] );

		$this->assertArrayHasKey( 'query-continue-cursor', $res );
		$this->assertSame( 0, $res['query-continue-cursor'] );
	}

	/**
	 * Locks the cursor-branch wiring: `getSQLOptions` is the legacy
	 * offset-path entry point; it must NOT be touched when the request
	 * opts into cursor mode. A regression that accidentally routes cursor
	 * requests back through the legacy options array would fail here.
	 */
	public function testCursorModeNeverCallsGetSQLOptions(): void {
		$row = $this->newRow( 42, 'Foo' );

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ $row ] ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->never() )
			->method( 'getSQLOptions' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListLookup( $store, $listAugmentor );

		$instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 0,
			'sort' => 'asc',
		] );
	}

	/**
	 * Locks the cursor-branch wiring on the other side: when a
	 * non-zero `cursor` is supplied, the trait's keyset WHERE predicate
	 * must reach the query builder via `andWhere()`. A regression that
	 * drops the predicate would silently turn cursor=N requests into
	 * first-page-of-everything reads.
	 */
	public function testCursorWithNonZeroValueEmitsKeysetPredicate(): void {
		$row = $this->newRow( 42, 'Foo' );

		$capturedWheres = [];

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'$v'" );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $row, &$capturedWheres ) {
					return $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );
				}
			);

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListLookup( $store, $listAugmentor );

		$instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 12345,
		] );

		$predicate = $this->findKeysetPredicate( $capturedWheres, '12345' );
		$this->assertNotNull(
			$predicate,
			'Cursor mode with cursor>0 must emit the keyset WHERE predicate against smw_sort and the cursor id'
		);

		// ASC mode (default): predicate uses `>` to step forward in
		// alphabetical order.
		$this->assertStringContainsString(
			'smw_sort > ',
			$predicate,
			'ASC cursor predicate must use > operator'
		);
	}

	/**
	 * Locks the DESC cursor predicate. Without this test, a regression
	 * that ignores `$requestOptions->ascending` in the trait would
	 * silently return ascending results for a `sort=desc&cursor=N`
	 * request.
	 */
	public function testCursorWithSortDescEmitsLessThanPredicate(): void {
		$row = $this->newRow( 42, 'Foo' );

		$capturedWheres = [];

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'$v'" );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $row, &$capturedWheres ) {
					return $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );
				}
			);

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListLookup( $store, $listAugmentor );

		$instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 12345,
			'sort' => 'desc',
		] );

		$predicate = $this->findKeysetPredicate( $capturedWheres, '12345' );
		$this->assertNotNull(
			$predicate,
			'DESC cursor mode must still emit the keyset WHERE predicate'
		);

		// DESC mode: predicate must use `<` to step forward in reverse
		// alphabetical order. `>` would mean "next page in ASC order"
		// which is the wrong direction.
		$this->assertStringContainsString(
			'smw_sort < ',
			$predicate,
			'DESC cursor predicate must use < operator'
		);
		$this->assertStringNotContainsString(
			'smw_sort > ',
			$predicate,
			'DESC cursor predicate must not use > operator'
		);
	}

	private function findKeysetPredicate( array $capturedWheres, string $cursorIdLiteral ): ?string {
		foreach ( $capturedWheres as $where ) {
			if (
				is_string( $where )
				&& str_contains( $where, $cursorIdLiteral )
				&& str_contains( $where, 'smw_sort' )
			) {
				return $where;
			}
		}
		return null;
	}

	/**
	 * Stale cursor (id that no longer exists in `smw_object_ids`)
	 * inherits the trait-level behaviour: `resolveCursorSort` returns null,
	 * the WHERE predicate is silently skipped, and the client receives the
	 * first page anchored at its own ids. Bot clients that store the new
	 * cursor and advance forward will not infinite-loop. Locked here so a
	 * future trait change cannot regress the API behaviour without
	 * updating this test.
	 *
	 * Unit-level reach: this asserts the response shape; the underlying
	 * trait fallback is covered end-to-end by the keyset integration
	 * tests in `tests/phpunit/Integration/SQLStore/Lookup/`.
	 */
	public function testStaleCursorReturnsResultsWithoutInfiniteLoopRisk(): void {
		$row = $this->newRow( 42, 'Foo' );

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'$v'" );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ $row ] ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListLookup( $store, $listAugmentor );

		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 99999999,
		] );

		// Continue cursor must reflect the first-page result (the only row's
		// id), not the stale input id — otherwise a bot would loop.
		$this->assertNotSame( 99999999, $res['query-continue-cursor'] );
		$this->assertCount( 1, $res['query'] );
	}

	public function testShouldUseCursorModeRespectsPresenceNotTruthiness(): void {
		$this->assertTrue( ListLookup::shouldUseCursorMode( [ 'cursor' => 0 ] ) );
		$this->assertTrue( ListLookup::shouldUseCursorMode( [ 'cursor' => '' ] ) );
		$this->assertTrue( ListLookup::shouldUseCursorMode( [ 'cursor' => null ] ) );
		$this->assertTrue( ListLookup::shouldUseCursorMode( [ 'cursor' => 12345 ] ) );
		$this->assertFalse( ListLookup::shouldUseCursorMode( [] ) );
		$this->assertFalse( ListLookup::shouldUseCursorMode( [ 'offset' => 50 ] ) );
	}

	public function testLegacyModeStillEmitsContinueOffsetWhenMoreAvailable(): void {
		$rows = [
			$this->newRow( 100, 'AAA' ),
			$this->newRow( 101, 'BBB' ),
			$this->newRow( 102, 'CCC' ),
		];

		$instance = $this->newInstanceWithRows( $rows, true );

		// `sort` triggers the legacy options branch (which calls
		// `getSQLOptions`); the absence of `cursor` keeps the lookup on the
		// offset path.
		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'sort' => 'asc',
			'limit' => 2,
		] );

		$this->assertSame( 2, $res['query-continue-offset'] );
		$this->assertArrayNotHasKey( 'query-continue-cursor', $res );
	}

	private function newRow( int $id, string $title ): stdClass {
		$row = new stdClass;
		$row->smw_id = $id;
		$row->smw_title = $title;
		$row->smw_sort = $title;
		return $row;
	}

	private function newInstanceWithRows( array $rows, bool $expectGetSQLOptions = false ): ListLookup {
		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( $rows ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		// `getSQLOptions` is only consulted on the legacy offset path.
		if ( $expectGetSQLOptions ) {
			$store->expects( $this->atLeastOnce() )
				->method( 'getSQLOptions' )
				->willReturn( [] );
		}

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		return new ListLookup(
			$store,
			$listAugmentor
		);
	}

}
