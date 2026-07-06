<?php

namespace SMW\Tests\Unit\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\Query;
use SMW\QueryFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @covers SMW\Query\Processor\QueryCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QueryCreatorTest extends TestCase {

	public function testCanConstruct() {
		$queryFactory = $this->getMockBuilder( QueryFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			QueryCreator::class,
			new QueryCreator( $queryFactory )
		);
	}

	/**
	 * @dataProvider queryStringProvider
	 */
	public function testCreate( $queryString, $params, $expected ) {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( $queryString, $params );

		$this->assertInstanceOf(
			Query::class,
			$query
		);

		$this->assertSame(
			$expected,
			$query->toString()
		);
	}

	public function queryStringProvider() {
		$provider[] = [
			'[[Foo::Bar]]',
			[
				'limit'  => 42,
				'offset' => 12
			],
			'[[Foo::Bar]]|limit=42|offset=12|mainlabel='
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'source'    => 'foobar',
				'mainLabel' => 'Some'
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=Some|source=foobar'
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'sort'  => [ '', 'SomeA', 'SomeB' ],
				'order' => [ 'desc', 'random', 'asc' ]
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=SomeA,SomeB|order=random,asc'
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'sort'  => [ ',' ]
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=,|order=asc'
		];

		return $provider;
	}

	public function testOrderNoneProducesUnsortedQuery(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( '[[Foo::Bar]]', [ 'order' => [ 'none' ] ] );

		$this->assertSame( [], $query->getSortKeys() );
	}

	public function testOrderNoneOverridesAnExplicitSort(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[ 'sort' => [ 'SomeProperty' ], 'order' => [ 'none' ] ]
		);

		$this->assertSame( [], $query->getSortKeys() );
	}

	public function testOrderNoneWithCursorIsRejectedWithError(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of {"v":1,"sort":"Foo","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjoiRm9vIiwiaWQiOjQyfQ';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[ 'order' => [ 'none' ], 'cursor' => $token ]
		);

		$this->assertNotEmpty( $query->getErrors() );
		$this->assertNull( $query->getCursorAfter() );
	}

	public function testOrderNoneInAnySlotDisablesSortingForTheWholeQuery(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// `none` in any order slot makes the whole query unsorted.
		$query = $instance->create(
			'[[Foo::Bar]]',
			[ 'sort' => [ '', 'SomeProperty' ], 'order' => [ 'asc', 'none' ] ]
		);

		$this->assertSame( [], $query->getSortKeys() );
	}

	public function testOrderNoneSetsTheSortDisabledOption(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( '[[Foo::Bar]]', [ 'order' => [ 'none' ] ] );

		$this->assertTrue( $query->getOption( Query::SORT_DISABLED ) );
	}

	public function testCursorParamWithDefaultSortDecodesAndAppliesPayload(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of {"v":1,"sort":"Foo","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjoiRm9vIiwiaWQiOjQyfQ';

		$query = $instance->create( '[[Foo::Bar]]', [ 'cursor' => $token ] );

		$this->assertSame(
			[ 'v' => 1, 'sort' => 'Foo', 'id' => 42 ],
			$query->getCursorAfter()
		);
	}

	public function testCursorParamWithMatchingSortPropIsAccepted(): void {
		// Phase 3a contract: `sort=SomeProperty` + cursor whose
		// `sort_prop` matches is allowed. The cursor anchor stays
		// meaningful under the requested sort.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of {"v":1,"sort":"value","sort_prop":"SomeProperty","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjoidmFsdWUiLCJzb3J0X3Byb3AiOiJTb21lUHJvcGVydHkiLCJpZCI6NDJ9';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'SomeProperty' ],
				'cursor' => $token,
			]
		);

		$payload = $query->getCursorAfter();
		$this->assertIsArray( $payload );
		$this->assertSame( 'SomeProperty', $payload['sort_prop'] );
		$this->assertSame( 'value', $payload['sort'] );
		$this->assertSame( 42, $payload['id'] );
	}

	public function testCursorParamWithMismatchingSortPropIsRejectedWithError(): void {
		// Phase 3a contract: cursor anchored at `sort_prop=A` MUST NOT
		// be applied when the request says `sort=B`. The anchor has no
		// meaning under a different sort.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$token = 'eyJ2IjoxLCJzb3J0IjoidmFsdWUiLCJzb3J0X3Byb3AiOiJTb21lUHJvcGVydHkiLCJpZCI6NDJ9';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'DifferentProperty' ],
				'cursor' => $token,
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'Cursor was minted for'
		);
	}

	public function testCursorParamWithEmptyPayloadIsAcceptedForAnySingleSort(): void {
		// Bootstrap case: a cursor with no `sort_prop` (the
		// `{"v":1}` empty-payload bootstrap from Phase 3 spike)
		// matches any single-property sort.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$emptyToken = 'eyJ2IjoxfQ';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'Modification_date' ],
				'cursor' => $emptyToken,
			]
		);

		$this->assertSame( [ 'v' => 1 ], $query->getCursorAfter() );
	}

	public function testCursorParamWithPagePivotedSortPreservesAnchor(): void {
		// Regression for the `array_filter` key-preservation bug:
		// `sort=,SomeProperty` produces `sortKeys = ['' => 'ASC',
		// 'SomeProperty' => 'ASC']`. Without `array_values` after
		// the filter, `$customSortKeys[0]` is null and any cursor
		// minted on page 1 (which correctly emits
		// `sort_prop=SomeProperty`) is falsely rejected on page 2
		// onward. The cursor walk must work for this very common
		// page-pivoted shape.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// {"v":1,"sort":"value","sort_prop":"SomeProperty","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjoidmFsdWUiLCJzb3J0X3Byb3AiOiJTb21lUHJvcGVydHkiLCJpZCI6NDJ9';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ '', 'SomeProperty' ],
				'cursor' => $token,
			]
		);

		$payload = $query->getCursorAfter();
		$this->assertIsArray( $payload, 'Cursor with page-pivoted sort must NOT be rejected' );
		$this->assertSame( 'SomeProperty', $payload['sort_prop'] );
	}

	public function testCursorParamWithOrderDescIsAccepted(): void {
		// Phase 3b lifts the 3a ASC-only constraint. A bootstrap cursor
		// (no sort_order) is accepted against an `order=desc` request;
		// the engine then mints subsequent cursors with `sort_order=DESC`.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'SomeProperty' ],
				'order'  => [ 'desc' ],
				'cursor' => 'eyJ2IjoxfQ',
			]
		);

		$this->assertSame( [ 'v' => 1 ], $query->getCursorAfter() );
	}

	public function testCursorParamWithImplicitAscPayloadIsRejectedByDescRequest(): void {
		// Phase 3a / spike cursors carry no `sort_order` field; they were
		// minted under ASC. A DESC request must reject them — the
		// predicate would seek in the wrong direction. This is the
		// backward-compat mirror of `testCursorParamWithMismatchedSortOrder`.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// {"v":1,"sort":"value","sort_prop":"SomeProperty","id":42} — no sort_order
		$token = 'eyJ2IjoxLCJzb3J0IjoidmFsdWUiLCJzb3J0X3Byb3AiOiJTb21lUHJvcGVydHkiLCJpZCI6NDJ9';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'SomeProperty' ],
				'order'  => [ 'desc' ],
				'cursor' => $token,
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent( $query->getErrors(), 'wrong direction' );
	}

	public function testCursorParamWithDefaultSortDescIsAccepted(): void {
		// Contract item 8: `sort=` (default page sort) + `order=desc`
		// + bootstrap cursor is a valid combination. The engine then
		// uses `smw_sort` column DESC.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'order'  => [ 'desc' ],
				'cursor' => 'eyJ2IjoxfQ',
			]
		);

		$this->assertSame( [ 'v' => 1 ], $query->getCursorAfter() );
	}

	public function testCursorParamWithMismatchedSortOrderIsRejectedWithError(): void {
		// A cursor minted for DESC carries `sort_order=DESC`. Sending
		// that cursor with an `order=asc` request must be rejected:
		// the predicate would seek in the wrong direction.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// {"v":1,"sort":"value","sort_prop":"SomeProperty","sort_order":"DESC","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjoidmFsdWUiLCJzb3J0X3Byb3AiOiJTb21lUHJvcGVydHkiLCJzb3J0X29yZGVyIjoiREVTQyIsImlkIjo0Mn0';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'SomeProperty' ],
				'order'  => [ 'asc' ],
				'cursor' => $token,
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'wrong direction'
		);
	}

	public function testCursorParamWithOrderRandomIsRejectedWithError(): void {
		// `order=random` has no stable anchor for keyset to seek past.
		// Permanent rejection (no future phase will lift this).
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'SomeProperty' ],
				'order'  => [ 'random' ],
				'cursor' => 'eyJ2IjoxfQ',
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'`order=random`'
		);
	}

	public function testCursorParamWithUniformMultiSortIsAccepted(): void {
		// Phase 3b-ii: `sort=A,B` with a uniform `order=` is accepted.
		// Bootstrap cursor matches because it carries no `sort_prop`.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'cursor' => 'eyJ2IjoxfQ',
			]
		);

		$this->assertSame( [ 'v' => 1 ], $query->getCursorAfter() );
	}

	public function testCursorParamWithMixedOrderMultiSortBootstrapIsAccepted(): void {
		// Phase 3b-iii: mixed per-level directions are allowed. A
		// bootstrap cursor (no anchor) opts into deterministic ordering
		// without seeking past anything, so it is accepted against any
		// direction combination.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'order'  => [ 'asc', 'desc' ],
				'cursor' => 'eyJ2IjoxfQ',
			]
		);

		$this->assertNotNull( $query->getCursorAfter() );
	}

	public function testCursorParamWithMixedOrderMatchingPerLevelArrayIsAccepted(): void {
		// Phase 3b-iii: a cursor minted under `order=asc,desc` carries
		// `sort_order=["ASC","DESC"]`. The request's per-level
		// directions must match element-wise.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of:
		// {"v":1,"sort":["v1","v2"],"sort_prop":["PropertyA","PropertyB"],"sort_order":["ASC","DESC"],"id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjpbInYxIiwidjIiXSwic29ydF9wcm9wIjpbIlByb3BlcnR5QSIsIlByb3BlcnR5QiJdLCJzb3J0X29yZGVyIjpbIkFTQyIsIkRFU0MiXSwiaWQiOjQyfQ';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'order'  => [ 'asc', 'desc' ],
				'cursor' => $token,
			]
		);

		$payload = $query->getCursorAfter();
		$this->assertIsArray( $payload );
		$this->assertSame( [ 'ASC', 'DESC' ], $payload['sort_order'] );
	}

	public function testCursorParamWithMixedOrderMismatchingPerLevelArrayIsRejected(): void {
		// Phase 3b-iii: a cursor minted under one mix of per-level
		// directions cannot be applied against a different mix.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// Cursor minted for ["DESC","ASC"], request asks for ["ASC","DESC"].
		// base64url of:
		// {"v":1,"sort":["v1","v2"],"sort_prop":["PropertyA","PropertyB"],"sort_order":["DESC","ASC"],"id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjpbInYxIiwidjIiXSwic29ydF9wcm9wIjpbIlByb3BlcnR5QSIsIlByb3BlcnR5QiJdLCJzb3J0X29yZGVyIjpbIkRFU0MiLCJBU0MiXSwiaWQiOjQyfQ';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'order'  => [ 'asc', 'desc' ],
				'cursor' => $token,
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'order='
		);
	}

	public function testCursorParamWithUniformDescCursorAgainstMixedRequestIsRejected(): void {
		// A 3b-i/3b-ii uniform-DESC cursor must not be silently accepted
		// against a mixed-direction request. The string `"DESC"` normalises
		// to per-level `["DESC","DESC"]`, which mismatches the request's
		// `["ASC","DESC"]`.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of:
		// {"v":1,"sort":["v1","v2"],"sort_prop":["PropertyA","PropertyB"],"sort_order":"DESC","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjpbInYxIiwidjIiXSwic29ydF9wcm9wIjpbIlByb3BlcnR5QSIsIlByb3BlcnR5QiJdLCJzb3J0X29yZGVyIjoiREVTQyIsImlkIjo0Mn0';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'order'  => [ 'asc', 'desc' ],
				'cursor' => $token,
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'order='
		);
	}

	public function testCursorParamWithMatchingArraySortPropIsAccepted(): void {
		// Phase 3b-ii: a multi-sort cursor carries `sort_prop` as an
		// array of property keys. The request's sort= must match
		// element-wise.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of {"v":1,"sort":["v1","v2"],"sort_prop":["PropertyA","PropertyB"],"id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjpbInYxIiwidjIiXSwic29ydF9wcm9wIjpbIlByb3BlcnR5QSIsIlByb3BlcnR5QiJdLCJpZCI6NDJ9';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'cursor' => $token,
			]
		);

		$payload = $query->getCursorAfter();
		$this->assertIsArray( $payload );
		$this->assertSame( [ 'PropertyA', 'PropertyB' ], $payload['sort_prop'] );
		$this->assertSame( [ 'v1', 'v2' ], $payload['sort'] );
	}

	public function testCursorParamWithShapeMismatchSortPropIsRejectedWithError(): void {
		// A single-sort cursor (scalar `sort_prop`) sent to a multi-sort
		// request (or vice-versa) must be rejected. The anchor has no
		// meaning when the sort field set has changed.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// Single-sort cursor (scalar sort_prop)
		$singleSortToken = 'eyJ2IjoxLCJzb3J0IjoidiIsInNvcnRfcHJvcCI6IlByb3BlcnR5QSIsImlkIjo0Mn0';

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'PropertyA', 'PropertyB' ],
				'cursor' => $singleSortToken,
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'has no meaning'
		);
	}

	public function testCursorParamWithCountModeIsRejectedWithError(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'queryMode' => Query::MODE_COUNT,
				'cursor'    => 'eyJ2IjoxLCJzb3J0IjoiRm9vIiwiaWQiOjQyfQ',
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'`format=count`'
		);
	}

	public function testMalformedCursorTokenIsRejectedWithError(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[ 'cursor' => '!!!not-a-valid-base64-token!!!' ]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'Malformed'
		);
	}

	public function testEmptyCursorParamDoesNotTriggerCursorMode(): void {
		// Default value for `cursor` is empty string. Absence MUST keep
		// the query on the legacy offset path.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( '[[Foo::Bar]]', [ 'cursor' => '' ] );

		$this->assertNull( $query->getCursorAfter() );
	}

	private function assertCursorErrorPresent( array $errors, string $marker ): void {
		foreach ( $errors as $err ) {
			if ( is_string( $err ) && str_contains( $err, $marker ) ) {
				$this->addToAssertionCount( 1 );
				return;
			}
		}
		$this->fail( "Expected cursor error containing '$marker', got: " . var_export( $errors, true ) );
	}

}
