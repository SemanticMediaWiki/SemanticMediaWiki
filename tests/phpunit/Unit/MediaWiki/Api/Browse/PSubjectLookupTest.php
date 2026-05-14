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
		] );

		$this->assertNotNull( $capturedOptions );
		$this->assertTrue(
			(bool)$capturedOptions->getOption( RequestOptions::CURSOR_MODE ),
			'cursor request param must flip CURSOR_MODE on the options passed to getPropertySubjects'
		);
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
		// `PropertySubjectsLookup` leaves hasMore=false when there is no
		// lookahead row to trim; PSubjectLookup must surface 0 rather than
		// a stale id.
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [ new WikiPage( 'Foo bar', NS_MAIN ) ] );

		$instance = new PSubjectLookup( $this->store );

		$res = $instance->lookup( [
			'search' => 'Foo',
			'property' => 'Bar',
			'cursor' => 0,
		] );

		$this->assertArrayHasKey( 'query-continue-cursor', $res );
		$this->assertSame( 0, $res['query-continue-cursor'] );
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
