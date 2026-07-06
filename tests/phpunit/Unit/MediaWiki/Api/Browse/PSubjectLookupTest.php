<?php

namespace SMW\Tests\Unit\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Api\Browse\PSubjectLookup;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\MediaWiki\Api\Browse\PSubjectLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PSubjectLookupTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PSubjectLookup::class,
			new PSubjectLookup( $this->store )
		);
	}

	/**
	 * @dataProvider lookupProvider
	 */
	public function testLookup( $subject, $parameters, $expected ) {
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [ $subject ] );

		$instance = new PSubjectLookup(
			$this->store
		);

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

	public function lookupProvider() {
		yield [
			new WikiPage( 'Foo bar', NS_MAIN ),
			[
				'search' => 'Foo',
				'property' => 'Bar'
			],
			[
				'Foo bar'
			]
		];

		yield [
			new WikiPage( 'Foo bar', NS_HELP ),
			[
				'search' => 'Foo',
				'property' => 'Bar',
				'title-prefix' => false
			],
			[
				'Foo bar'
			]
		];
	}

	public function testLegacyResponseOmitsContinueCursorField(): void {
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [ new WikiPage( 'Foo bar', NS_MAIN ) ] );

		$instance = new PSubjectLookup( $this->store );

		$res = $instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
		] );

		// Legacy clients (no `cursor` opt-in) must see exactly the
		// pre-cursor response shape. Existing JSONScript fixtures depend
		// on a contiguous `"query-continue-offset":0,"version":1`
		// substring; inserting a new field between them would break them.
		$this->assertArrayNotHasKey( 'query-continue-cursor', $res );
		$this->assertArrayHasKey( 'query-continue-offset', $res );
	}

	public function testCursorModeOptInSetsCursorModeOptionOnRequestOptions(): void {
		$capturedOptions = null;

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback(
				static function ( $property, $value, $options ) use ( &$capturedOptions ) {
					$capturedOptions = $options;
					return [ new WikiPage( 'Foo bar', NS_MAIN ) ];
				}
			);

		$instance = new PSubjectLookup( $this->store );

		$instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'cursor' => 0,
			'limit' => 20,
		] );

		$this->assertNotNull( $capturedOptions );
		$this->assertTrue(
			(bool)$capturedOptions->getOption( RequestOptions::CURSOR_MODE ),
			'cursor request param must flip CURSOR_MODE on the options passed to getPropertySubjects'
		);

		// Cursor mode caller passes the plain page size. `PropertySubjectsLookup`
		// adds its own LIMIT+1 lookahead internally. A regression that pre-adds +1
		// here would silently corrupt the trim threshold inside the lookup
		// (SQL ends up with LIMIT+2 and the wrong row gets popped).
		$this->assertSame(
			20,
			$capturedOptions->getLimit(),
			'Cursor mode must pass plain $limit (PropertySubjectsLookup adds its own +1)'
		);
	}

	public function testCursorModeWithCoSentOffsetIgnoresOffset(): void {
		$capturedOptions = null;

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback(
				static function ( $property, $value, $options ) use ( &$capturedOptions ) {
					$capturedOptions = $options;
					return [ new WikiPage( 'Foo bar', NS_MAIN ) ];
				}
			);

		$instance = new PSubjectLookup( $this->store );

		$instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'cursor' => 0,
			'offset' => 999,
		] );

		// Cursor mode is authoritative. Co-sent `offset` MUST be ignored so the
		// response doesn't seek past the cursor by the legacy offset amount.
		$this->assertSame( 0, $capturedOptions->getOffset() );
	}

	public function testLegacyModePassesLimitPlusOneForManualLookahead(): void {
		$capturedOptions = null;

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback(
				static function ( $property, $value, $options ) use ( &$capturedOptions ) {
					$capturedOptions = $options;
					return [ new WikiPage( 'Foo bar', NS_MAIN ) ];
				}
			);

		$instance = new PSubjectLookup( $this->store );

		$instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'limit' => 20,
		] );

		// Legacy path manually trims the lookahead row in `findPropertySubjects`,
		// so the caller asks the lookup for $limit + 1.
		$this->assertSame( 21, $capturedOptions->getLimit() );
	}

	public function testCursorWithNonZeroValueSetsCursorAfter(): void {
		$capturedOptions = null;

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback(
				static function ( $property, $value, $options ) use ( &$capturedOptions ) {
					$capturedOptions = $options;
					return [ new WikiPage( 'Foo bar', NS_MAIN ) ];
				}
			);

		$instance = new PSubjectLookup( $this->store );

		$instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'cursor' => 12345,
		] );

		$this->assertSame( 12345, $capturedOptions->getCursorAfter() );
	}

	public function testCursorModeSurfacesLastCursorFromRequestOptions(): void {
		// Simulate `PropertySubjectsLookup::postProcessCursorResult()` writing
		// hasMore + lastCursor back onto the caller's RequestOptions.
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback(
				static function ( $property, $value, $options ) {
					$options->setCursorHasMore( true );
					$options->setLastCursor( 777 );
					return [
						new WikiPage( 'Foo bar', NS_MAIN ),
						new WikiPage( 'Foo baz', NS_MAIN ),
					];
				}
			);

		$instance = new PSubjectLookup( $this->store );

		$res = $instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'cursor' => 0,
		] );

		$this->assertSame( 777, $res['query-continue-cursor'] );
		$this->assertSame( 0, $res['query-continue-offset'] );
	}

	public function testCursorModeWithNoFurtherRowsEmitsZeroCursor(): void {
		// `PropertySubjectsLookup` always writes `lastCursor` when results are
		// non-empty but only writes `hasMore=true` when there is a lookahead
		// row to trim. PSubjectLookup must surface 0 when hasMore is false
		// even if a `lastCursor` happens to be set — otherwise a final-page
		// response would emit an id that, if followed, would loop the client
		// back through already-seen rows.
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback(
				static function ( $property, $value, $options ) {
					// hasMore deliberately NOT set; lastCursor deliberately IS set.
					$options->setLastCursor( 555 );
					return [ new WikiPage( 'Foo bar', NS_MAIN ) ];
				}
			);

		$instance = new PSubjectLookup( $this->store );

		$res = $instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'cursor' => 0,
		] );

		$this->assertArrayHasKey( 'query-continue-cursor', $res );
		$this->assertSame(
			0,
			$res['query-continue-cursor'],
			'When hasMore is false, continueCursor must be 0 even if lastCursor is set'
		);
	}

	public function testShouldUseCursorModeRespectsPresenceNotTruthiness(): void {
		$this->assertTrue( PSubjectLookup::shouldUseCursorMode( [ 'cursor' => 0 ] ) );
		$this->assertTrue( PSubjectLookup::shouldUseCursorMode( [ 'cursor' => '' ] ) );
		$this->assertTrue( PSubjectLookup::shouldUseCursorMode( [ 'cursor' => null ] ) );
		$this->assertTrue( PSubjectLookup::shouldUseCursorMode( [ 'cursor' => 12345 ] ) );
		$this->assertFalse( PSubjectLookup::shouldUseCursorMode( [] ) );
		$this->assertFalse( PSubjectLookup::shouldUseCursorMode( [ 'offset' => 50 ] ) );
	}

}
