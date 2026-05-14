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

	public function testLegacyResponseAlwaysIncludesContinueCursorZero(): void {
		$instance = $this->newInstanceWithRows( [ $this->newRow( 42, 'Foo' ) ] );

		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
		] );

		$this->assertArrayHasKey( 'query-continue-cursor', $res );
		$this->assertSame( 0, $res['query-continue-cursor'] );
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

		$this->assertSame( 101, $res['query-continue-cursor'] );
		$this->assertSame( 0, $res['query-continue-offset'] );
		$this->assertCount( 2, $res['query'] );
	}

	public function testCursorModeWithNoFurtherRowsReturnsZeroCursor(): void {
		$instance = $this->newInstanceWithRows( [ $this->newRow( 42, 'Foo' ) ] );

		$res = $instance->lookup( [
			'ns' => SMW_NS_PROPERTY,
			'search' => 'Foo',
			'cursor' => 0,
			'limit' => 50,
		] );

		$this->assertSame( 0, $res['query-continue-cursor'] );
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
		$this->assertSame( 0, $res['query-continue-cursor'] );
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
