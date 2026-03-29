<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\Lookup\UnusedPropertyListLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.2
 *
 * @author mwjames
 */
class UnusedPropertyListLookupTest extends TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( RequestOptions::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UnusedPropertyListLookup::class,
			new UnusedPropertyListLookup( $this->store, $this->propertyStatisticsStore, null )
		);
	}

	public function testListLookupInterfaceMethodAccess() {
		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$this->assertIsString(

			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isFromCache()
		);

		$this->assertStringContainsString(
			'UnusedPropertyListLookup',
			$instance->getHash()
		);
	}

	public function testLookupIdentifierChangedByRequestOptions() {
		$requestOptions = new RequestOptions();

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$lookupIdentifier = $instance->getHash();

		$requestOptions->limit = 100;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$this->assertNotSame(
			$lookupIdentifier,
			$instance->getHash()
		);
	}

	public function testTryTofetchListForMissingOptionsThrowsException() {
		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

		$this->expectException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testfetchListForValidProperty() {
		$row = new stdClass;
		$row->smw_title = 'Foo';
		$row->smw_id = 42;
		$row->smw_sort = 'Foo';

		$connection = $this->createMockConnectionWithQueryBuilder( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->requestOptions->expects( $this->any() )
			->method( 'getCursorAfter' )
			->willReturn( null );

		$this->requestOptions->expects( $this->any() )
			->method( 'getCursorBefore' )
			->willReturn( null );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$property = new Property( 'Foo' );
		$property->id = 42;

		$expected = [
			$property
		];

		$this->assertEquals(
			$expected,
			$result
		);
	}

	public function testfetchListForInvalidProperty() {
		$row = new stdClass;
		$row->smw_title = '-Foo';
		$row->smw_id = 42;
		$row->smw_sort = '-Foo';

		$connection = $this->createMockConnectionWithQueryBuilder( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		// TODO: Illegal dynamic property (#5421)
		$this->requestOptions->limit = 1001;

		$this->requestOptions->expects( $this->any() )
			->method( 'getCursorAfter' )
			->willReturn( null );

		$this->requestOptions->expects( $this->any() )
			->method( 'getCursorBefore' )
			->willReturn( null );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$this->requestOptions
		);

		$result = $instance->fetchList();

		$this->assertIsArray(

			$result
		);

		$this->assertInstanceOf(
			Error::class,
			$result[0]
		);
	}

	public function testFetchListWithCursorAfterSetsCursors() {
		$row = new stdClass;
		$row->smw_title = 'Bar';
		$row->smw_id = 50;
		$row->smw_sort = 'Bar';

		$cursorRow = new stdClass;
		$cursorRow->smw_sort = 'Alpha';

		$queryBuilder = $this->createMockSelectQueryBuilder( [ $row ] );

		$cursorQueryBuilder = $this->createMockSelectQueryBuilder( $cursorRow );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->exactly( 2 ) )
			->method( 'newSelectQueryBuilder' )
			->willReturnOnConsecutiveCalls( $queryBuilder, $cursorQueryBuilder );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'$v'" );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$requestOptions = new RequestOptions();
		$requestOptions->setCursorAfter( 42 );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$result = $instance->fetchList();

		$this->assertCount( 1, $result );
		$this->assertSame( 50, $requestOptions->getFirstCursor() );
		$this->assertSame( 50, $requestOptions->getLastCursor() );
	}

	public function testFetchListWithCursorBeforeReversesResults() {
		$row1 = new stdClass;
		$row1->smw_title = 'Beta';
		$row1->smw_id = 60;
		$row1->smw_sort = 'Beta';

		$row2 = new stdClass;
		$row2->smw_title = 'Alpha';
		$row2->smw_id = 40;
		$row2->smw_sort = 'Alpha';

		$cursorRow = new stdClass;
		$cursorRow->smw_sort = 'Gamma';

		$queryBuilder = $this->createMockSelectQueryBuilder( [ $row1, $row2 ] );

		$cursorQueryBuilder = $this->createMockSelectQueryBuilder( $cursorRow );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->exactly( 2 ) )
			->method( 'newSelectQueryBuilder' )
			->willReturnOnConsecutiveCalls( $queryBuilder, $cursorQueryBuilder );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'$v'" );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$requestOptions = new RequestOptions();
		$requestOptions->setCursorBefore( 70 );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$result = $instance->fetchList();

		// DESC results [Beta(60), Alpha(40)] should be reversed to [Alpha(40), Beta(60)]
		$this->assertCount( 2, $result );
		$this->assertSame( 'Alpha', $result[0]->getKey() );
		$this->assertSame( 'Beta', $result[1]->getKey() );
		$this->assertSame( 40, $requestOptions->getFirstCursor() );
		$this->assertSame( 60, $requestOptions->getLastCursor() );
	}

	public function testFetchListWithEmptyResultSetsNoCursors() {
		$connection = $this->createMockConnectionWithQueryBuilder( [] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$requestOptions = new RequestOptions();

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$result = $instance->fetchList();

		$this->assertSame( [], $result );
		$this->assertNull( $requestOptions->getFirstCursor() );
		$this->assertNull( $requestOptions->getLastCursor() );
	}

	public function testFetchListWithInvalidCursorFallsBackToFirstPage() {
		$row = new stdClass;
		$row->smw_title = 'Foo';
		$row->smw_id = 42;
		$row->smw_sort = 'Foo';

		// cursor lookup returns null (no matching row)
		$cursorQueryBuilder = $this->createMockSelectQueryBuilder( false );

		$queryBuilder = $this->createMockSelectQueryBuilder( [ $row ] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->exactly( 2 ) )
			->method( 'newSelectQueryBuilder' )
			->willReturnOnConsecutiveCalls( $queryBuilder, $cursorQueryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$requestOptions = new RequestOptions();
		$requestOptions->setCursorAfter( 99999 );

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$result = $instance->fetchList();

		// Should still return results (no WHERE cursor clause added)
		$this->assertCount( 1, $result );
		$this->assertSame( 42, $requestOptions->getFirstCursor() );
		$this->assertSame( 42, $requestOptions->getLastCursor() );
	}

	public function testFetchListTrimsExtraRowAndSetsCursorHasMore() {
		$row1 = new stdClass;
		$row1->smw_title = 'Alpha';
		$row1->smw_id = 10;
		$row1->smw_sort = 'Alpha';

		$row2 = new stdClass;
		$row2->smw_title = 'Beta';
		$row2->smw_id = 20;
		$row2->smw_sort = 'Beta';

		$row3 = new stdClass;
		$row3->smw_title = 'Gamma';
		$row3->smw_id = 30;
		$row3->smw_sort = 'Gamma';

		$connection = $this->createMockConnectionWithQueryBuilder( [ $row1, $row2, $row3 ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 2;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$result = $instance->fetchList();

		$this->assertCount( 2, $result );
		$this->assertSame( 'Alpha', $result[0]->getKey() );
		$this->assertSame( 'Beta', $result[1]->getKey() );
		$this->assertTrue( $requestOptions->getCursorHasMore() );
	}

	public function testFetchListDoesNotSetCursorHasMoreWhenExactLimit() {
		$row1 = new stdClass;
		$row1->smw_title = 'Alpha';
		$row1->smw_id = 10;
		$row1->smw_sort = 'Alpha';

		$row2 = new stdClass;
		$row2->smw_title = 'Beta';
		$row2->smw_id = 20;
		$row2->smw_sort = 'Beta';

		$connection = $this->createMockConnectionWithQueryBuilder( [ $row1, $row2 ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 2;

		$instance = new UnusedPropertyListLookup(
			$this->store,
			$this->propertyStatisticsStore,
			$requestOptions
		);

		$result = $instance->fetchList();

		$this->assertCount( 2, $result );
		$this->assertFalse( $requestOptions->getCursorHasMore() );
	}

	/**
	 * Creates a mock SelectQueryBuilder where all chained methods return $this.
	 *
	 * @param array|stdClass|false $result For arrays: wrapped in FakeResultWrapper
	 *   for fetchResultSet(). For stdClass/false: returned by fetchRow().
	 */
	private function createMockSelectQueryBuilder( $result ) {
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$chainMethods = [ 'from', 'fields', 'field', 'join', 'where',
			'andWhere', 'orderBy', 'limit', 'offset', 'caller' ];

		foreach ( $chainMethods as $method ) {
			$queryBuilder->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		if ( is_array( $result ) ) {
			$queryBuilder->expects( $this->any() )
				->method( 'fetchResultSet' )
				->willReturn( new FakeResultWrapper( $result ) );
		} else {
			$queryBuilder->expects( $this->any() )
				->method( 'fetchRow' )
				->willReturn( $result );
		}

		return $queryBuilder;
	}

	/**
	 * Creates a mock Database connection with a single newSelectQueryBuilder
	 * that returns the given rows from fetchResultSet.
	 */
	private function createMockConnectionWithQueryBuilder( array $rows ) {
		$queryBuilder = $this->createMockSelectQueryBuilder( $rows );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		return $connection;
	}

}
